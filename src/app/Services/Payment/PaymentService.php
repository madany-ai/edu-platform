<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\GrantEntitlementService;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\CheckoutResult;
use App\Services\Payment\Drivers\FawryGateway;
use App\Services\Payment\Drivers\PaymobGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private GrantEntitlementService $grantService
    ) {}

    public function getDriver(?string $gatewayName = 'paymob'): PaymentGatewayInterface
    {
        return match (strtolower((string) $gatewayName)) {
            'fawry' => new FawryGateway(),
            default => new PaymobGateway(),
        };
    }

    public function initiatePayment(Order $order, string $gatewayName = 'paymob'): CheckoutResult
    {
        $driver = $this->getDriver($gatewayName);
        $result = $driver->createCheckout($order);

        $order->update([
            'payment_gateway' => $gatewayName,
            'checkout_id' => $result->checkoutId,
            'gateway_reference' => $result->gatewayReference ?? $result->checkoutId,
            'payment_url' => $result->paymentUrl,
        ]);

        return $result;
    }

    public function processWebhook(Request $request, string $gatewayName): Order
    {
        $driver = $this->getDriver($gatewayName);
        $payload = $driver->verifyWebhook($request);

        // Find order by gateway_reference or checkout_id
        $order = Order::where('gateway_reference', $payload->gatewayReference)
            ->orWhere('checkout_id', $payload->gatewayReference)
            ->orWhere('id', $payload->gatewayReference)
            ->first();

        if (!$order) {
            Log::warning("PaymentService Webhook: Order not found for gateway reference {$payload->gatewayReference}");
            throw new \RuntimeException("الطلب المرجعي غير موجود في النظام.");
        }

        // Idempotency: Ignore if order is already completed
        if ($order->status === \App\Enums\OrderStatus::Completed) {
            Log::info("PaymentService Webhook: Order {$order->id} is already completed.");
            return $order;
        }

        DB::transaction(function () use ($order, $payload, $gatewayName) {
            if ($payload->status === 'paid') {
                // 1. Grant entitlements / enrollments
                $this->grantService->handle($order);

                // 2. Mark order as completed
                $order->update([
                    'status' => \App\Enums\OrderStatus::Completed->value,
                    'transaction_id' => $payload->transactionId ?? $order->transaction_id,
                    'payment_gateway' => $gatewayName,
                    'paid_at' => now(),
                    'metadata' => array_merge($order->metadata ?? [], ['webhook' => $payload->rawPayload]),
                ]);

                Log::info("PaymentService Webhook: Successfully completed Order {$order->id}");
            } elseif ($payload->status === 'failed') {
                $order->update([
                    'status' => \App\Enums\OrderStatus::Failed->value,
                    'failure_reason' => 'فشلت عملية الدفع عبر البوابة.',
                    'metadata' => array_merge($order->metadata ?? [], ['webhook' => $payload->rawPayload]),
                ]);
            }
        });

        return $order;
    }
}
