<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        try {
            $order = $this->paymentService->processWebhook($request, $gateway);

            return response()->json([
                'status' => 'success',
                'message' => 'تم معالجة التنبيه بنجاح.',
                'order_id' => $order->id,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            Log::warning("PaymentWebhookController invalid signature for {$gateway}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Throwable $e) {
            Log::error("PaymentWebhookController exception for {$gateway}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'فشلت معالجة التنبيه.',
            ], 500);
        }
    }
}
