<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Entitlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrantEntitlementService
{
    public function handle(Order $order): void
    {
        $purchasable = $order->purchasable;

        if (!$purchasable) {
            Log::warning("GrantEntitlementService: Order {$order->id} has no purchasable.");
            return;
        }

        DB::transaction(function () use ($purchasable, $order) {
            Log::info("GrantEntitlementService: Granting entitlements for Order {$order->id}, Purchasable: " . get_class($purchasable) . " ({$purchasable->id})");

            if ($purchasable instanceof Product) {
                $this->grantForProduct($purchasable, $order);
            } elseif ($purchasable instanceof Bundle) {
                // Load products relation if not loaded
                $purchasable->loadMissing('products');
                foreach ($purchasable->products as $product) {
                    $this->grantForProduct($product, $order);
                }
            }
        });
    }

    protected function grantForProduct(Product $product, Order $order): void
    {
        $lectureIds = $product->resolveLectureIds();
        $expiresAt = $product->access_duration_days
            ? now()->addDays($product->access_duration_days)
            : null;

        Log::info("GrantEntitlementService: Granting " . count($lectureIds) . " lectures from Product {$product->id} to Student {$order->student_id}");

        foreach ($lectureIds as $lectureId) {
            Entitlement::updateOrCreate(
                [
                    'student_id' => $order->student_id,
                    'lecture_id' => $lectureId,
                    'order_id' => $order->id,
                ],
                [
                    'expires_at' => $expiresAt,
                ]
            );
        }
    }
}
