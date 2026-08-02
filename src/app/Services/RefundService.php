<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Entitlement;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function refundOrder(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            $order->update([
                'status' => OrderStatus::Refunded,
                'refunded_at' => now(),
                'amount_refunded_cents' => $order->amount_cents,
                'failure_reason' => $reason ?? 'تم استرداد المبلغ إلى الطالب.',
            ]);

            // Delete entitlements created by this order
            Entitlement::where('order_id', $order->id)->delete();
        });
    }
}
