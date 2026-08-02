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
        if (empty($lectureIds)) {
            Log::warning("GrantEntitlementService: Product {$product->id} has no lectures resolved for Order {$order->id}");
            throw new \RuntimeException("فشل تفعيل المحتوى: لا يوجد دروس مرتبطة بهذا المنتج حالياً.");
        }

        $expiresAt = $product->access_duration_days
            ? now()->addDays($product->access_duration_days)
            : null;

        Log::info("GrantEntitlementService: Granting " . count($lectureIds) . " lectures from Product {$product->id} to Student {$order->student_id}");

        // If sellable is a Course, create/reactivate real Enrollment for student
        if ($product->sellable instanceof \App\Models\Course) {
            \App\Models\Enrollment::updateOrCreate(
                [
                    'student_id' => $order->student_id,
                    'course_id'  => $product->sellable_id,
                ],
                [
                    'status'     => \App\Enums\EnrollmentStatus::Active->value,
                    'source'     => \App\Enums\EnrollmentSource::Purchase->value,
                    'expires_at' => $expiresAt,
                    'started_at' => now(),
                ]
            );
        }

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

    public function revoke(Order $order): void
    {
        DB::transaction(function () use ($order) {
            Log::info("GrantEntitlementService: Revoking entitlements for Order {$order->id}");

            // Delete entitlements associated with this order
            Entitlement::where('order_id', $order->id)->delete();

            // Revoke course enrollment if order purchasable was a course
            $purchasable = $order->purchasable;
            if ($purchasable instanceof Product && $purchasable->sellable instanceof \App\Models\Course) {
                \App\Models\Enrollment::where('student_id', $order->student_id)
                    ->where('course_id', $purchasable->sellable_id)
                    ->update(['status' => \App\Enums\EnrollmentStatus::Suspended->value]);
            }
        });
    }
}
