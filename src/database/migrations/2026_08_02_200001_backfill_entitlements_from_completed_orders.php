<?php

use App\Models\Order;
use App\Services\GrantEntitlementService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $grantService = app(GrantEntitlementService::class);
        
        $completedOrders = Order::where('status', \App\Enums\OrderStatus::Completed->value)->get();
        
        foreach ($completedOrders as $order) {
            try {
                $grantService->handle($order);
            } catch (\Throwable $e) {
                // Log or handle any single order failure without failing migration
                logger()->error("Backfill entitlement failed for order {$order->id}: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Data migration down is non-destructive
    }
};
