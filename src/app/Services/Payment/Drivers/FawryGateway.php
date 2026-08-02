<?php

namespace App\Services\Payment\Drivers;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\CheckoutResult;
use App\Services\Payment\DTOs\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FawryGateway implements PaymentGatewayInterface
{
    public function createCheckout(Order $order): CheckoutResult
    {
        $merchantCode = config('services.fawry.merchant_code', env('FAWRY_MERCHANT_CODE', 'SANDBOX_MERCHANT'));
        $securityKey = config('services.fawry.security_key', env('FAWRY_SECURITY_KEY', 'SANDBOX_KEY'));
        $merchantRefNum = $order->id;

        $amountFormatted = number_format($order->amount_cents / 100, 2, '.', '');
        $signatureString = $merchantCode . $merchantRefNum . $order->student_id . $amountFormatted . $securityKey;
        $signature = hash('sha256', $signatureString);

        // Fawry charge payload
        $payload = [
            'merchantCode' => $merchantCode,
            'merchantRefNum' => $merchantRefNum,
            'customerProfileId' => $order->student_id,
            'amount' => $amountFormatted,
            'paymentExpiry' => now()->addDays(3)->timestamp * 1000,
            'currencyCode' => 'EGP',
            'signature' => $signature,
            'description' => "Order #{$order->id}",
        ];

        // Simulation / Sandbox URL
        $baseUrl = config('services.fawry.sandbox', true)
            ? 'https://atfawry.fawry.com/ECommerceWeb/api/payments/charge'
            : 'https://atfawry.fawry.com/ECommerceWeb/api/payments/charge';

        $response = Http::post($baseUrl, $payload);
        
        $paymentUrl = "https://atfawry.fawry.com/atfawry/plugin/atfawry.js?merchant=" . urlencode($merchantCode) . "&ref=" . urlencode($merchantRefNum);

        return new CheckoutResult(
            paymentUrl: $paymentUrl,
            checkoutId: $merchantRefNum,
            gatewayReference: $merchantRefNum
        );
    }

    public function verifyWebhook(Request $request): WebhookPayload
    {
        $securityKey = config('services.fawry.security_key', env('FAWRY_SECURITY_KEY', 'SANDBOX_KEY'));

        $merchantRefNum = $request->input('merchantRefNum');
        $fawryRefNumber = $request->input('fawryRefNumber');
        $orderAmount = number_format((float) $request->input('orderAmount', 0), 2, '.', '');
        $orderStatus = $request->input('orderStatus'); // PAID, CANCELLED, FAILED, REFUNDED
        $receivedSignature = $request->input('messageSignature');

        $concatenated = $fawryRefNumber . $merchantRefNum . $orderAmount . $orderStatus . $securityKey;
        $calculatedSignature = hash('sha256', $concatenated);

        if ($receivedSignature && !hash_equals(strtolower($calculatedSignature), strtolower($receivedSignature))) {
            Log::warning('Fawry Webhook Signature Verification Failed', [
                'received' => $receivedSignature,
                'calculated' => $calculatedSignature,
            ]);
            throw new \InvalidArgumentException('توقيع فوري غير صالح (Invalid Fawry Signature).');
        }

        $status = match (strtoupper((string) $orderStatus)) {
            'PAID', 'SUCCESS' => 'paid',
            'REFUNDED' => 'refunded',
            default => 'failed',
        };

        $amountCents = (int) round(((float) $orderAmount) * 100);

        return new WebhookPayload(
            gatewayReference: (string) $merchantRefNum,
            status: $status,
            amountCents: $amountCents,
            transactionId: (string) $fawryRefNumber,
            rawPayload: $request->all()
        );
    }
}
