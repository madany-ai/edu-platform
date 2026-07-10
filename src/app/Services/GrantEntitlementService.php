<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Entitlement;

class GrantEntitlementService
{
    public function handle(Order $order): void
    {
        $purchasable = $order->purchasable;

        if (!$purchasable) {
            return;
        }

        if ($purchasable instanceof Product) {
            $this->grantForProduct($purchasable, $order);
        } elseif ($purchasable instanceof Bundle) {
            // Load products relation if not loaded
            $purchasable->loadMissing('products');
            foreach ($purchasable->products as $product) {
                $this->grantForProduct($product, $order);
            }
        }
    }

    protected function grantForProduct(Product $product, Order $order): void
    {
        $lectureIds = $product->resolveLectureIds();
        $expiresAt = $product->access_duration_days
            ? now()->addDays($product->access_duration_days)
            : null;

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
