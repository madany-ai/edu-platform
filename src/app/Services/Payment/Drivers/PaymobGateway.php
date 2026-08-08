<?php

namespace App\Services\Payment\Drivers;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\CheckoutResult;
use App\Services\Payment\DTOs\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobGateway implements PaymentGatewayInterface
{
    public function createCheckout(Order $order): CheckoutResult
    {
        $baseUrl = rtrim(config('services.paymob.base_url', 'https://accept.paymob.com'), '/');
        $apiKey = config('services.paymob.api_key', env('PAYMOB_API_KEY'));
        $integrationId = config('services.paymob.integration_id', env('PAYMOB_INTEGRATION_ID'));
        $iframeId = config('services.paymob.iframe_id', env('PAYMOB_IFRAME_ID'));

        // 1. Authentication Request
        $authResponse = Http::post("{$baseUrl}/api/auth/tokens", [
            'api_key' => $apiKey,
        ]);

        if ($authResponse->failed()) {
            Log::error('Paymob Auth Failed', ['response' => $authResponse->body()]);
            throw new \RuntimeException('فشل الاتصال ببوابة الدفع (Paymob Auth).');
        }

        $authToken = $authResponse->json('token');

        // 2. Order Registration
        $orderResponse = Http::post("{$baseUrl}/api/ecommerce/orders", [
            'auth_token' => $authToken,
            'delivery_needed' => false,
            'amount_cents' => $order->amount_cents,
            'currency' => $order->currency ?? 'EGP',
            'merchant_order_id' => $order->id . '_' . uniqid(),
            'items' => [],
        ]);

        if ($orderResponse->failed()) {
            Log::error('Paymob Order Registration Failed', ['response' => $orderResponse->body()]);
            throw new \RuntimeException('فشل إنشاء الطلب بداخل بوابة الدفع (Paymob Order).');
        }

        $paymobOrderId = (string) $orderResponse->json('id');

        // 3. Payment Key Request
        $student = $order->student;
        $user = $student?->user;

        $phone = preg_replace('/[^0-9]/', '', (string) ($student?->phone ?? '')) ?: '01000000000';
        $firstName = !empty($student?->first_name) ? $student->first_name : (!empty($user?->name) ? explode(' ', $user->name)[0] : 'Student');
        $lastName = !empty($student?->last_name) ? $student->last_name : 'User';
        $email = !empty($user?->email) ? $user->email : 'student@example.com';

        $paymentKeyResponse = Http::post("{$baseUrl}/api/acceptance/payment_keys", [
            'auth_token' => $authToken,
            'amount_cents' => $order->amount_cents,
            'expiration' => 3600, // 1 hour
            'order_id' => $paymobOrderId,
            'billing_data' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => $phone,
                'apartment' => 'NA',
                'floor' => 'NA',
                'building' => 'NA',
                'street' => 'NA',
                'city' => 'Cairo',
                'country' => 'EG',
                'state' => 'Cairo',
                'postal_code' => 'NA',
                'shipping_method' => 'NA',
            ],
            'currency' => $order->currency ?? 'EGP',
            'integration_id' => (int) $integrationId,
        ]);

        if ($paymentKeyResponse->failed()) {
            Log::error('Paymob Payment Key Failed', ['response' => $paymentKeyResponse->body()]);
            throw new \RuntimeException('فشل إعداد مفتاح الدفع (Paymob Payment Key).');
        }

        $paymentToken = $paymentKeyResponse->json('token');
        $paymentUrl = "{$baseUrl}/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}";

        return new CheckoutResult(
            paymentUrl: $paymentUrl,
            checkoutId: $paymentToken,
            gatewayReference: $paymobOrderId
        );
    }

    public function verifyWebhook(Request $request): WebhookPayload
    {
        $hmacSecret = config('services.paymob.hmac', env('PAYMOB_HMAC', env('PAYMOB_HMAC_SECRET')));
        $receivedHmac = $request->query('hmac') ?? $request->input('hmac') ?? $request->header('hmac');

        $obj = $request->input('obj');
        if (is_string($obj)) {
            $obj = json_decode($obj, true) ?? [];
        }
        if (empty($obj) || !is_array($obj)) {
            $obj = $request->all();
        }

        // Standard Paymob HMAC concatenated fields list
        $keys = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order.id',
            'owner',
            'pending',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
            'success',
        ];

        $concatenated = '';
        foreach ($keys as $key) {
            $val = data_get($obj, $key);
            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            }
            $concatenated .= $val;
        }

        $calculatedHmac = hash_hmac('sha512', $concatenated, $hmacSecret);

        if (!$receivedHmac || !hash_equals($calculatedHmac, $receivedHmac)) {
            Log::warning('Paymob Webhook HMAC Verification Failed', [
                'received' => $receivedHmac,
                'calculated' => $calculatedHmac,
            ]);
            throw new \InvalidArgumentException('توقيع التنبيه غير صالح (Invalid HMAC).');
        }

        $success = data_get($obj, 'success') === true || data_get($obj, 'success') === 'true';
        $pending = data_get($obj, 'pending') === true || data_get($obj, 'pending') === 'true';
        $isRefunded = data_get($obj, 'is_refunded') === true || data_get($obj, 'is_refunded') === 'true';

        $status = 'failed';
        if ($success && !$pending) {
            $status = 'paid';
        } elseif ($isRefunded) {
            $status = 'refunded';
        }

        $paymobOrderId = (string) data_get($obj, 'order.id');
        $transactionId = (string) data_get($obj, 'id');
        $amountCents = (int) data_get($obj, 'amount_cents');

        return new WebhookPayload(
            gatewayReference: $paymobOrderId,
            status: $status,
            amountCents: $amountCents,
            transactionId: $transactionId,
            rawPayload: $request->all()
        );
    }
}
