<?php

namespace App\Http\Resources;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = match ($this->purchasable_type) {
            Product::class => 'product',
            Bundle::class => 'bundle',
            default => strtolower(class_basename($this->purchasable_type ?? '')),
        };

        $purchasableName = $this->purchasable?->name ?? 'محتوى غير متاح';

        $statusValue = $this->status instanceof \App\Enums\OrderStatus 
            ? $this->status->value 
            : (string) $this->status;

        $statusLabel = $this->status instanceof \App\Enums\OrderStatus 
            ? $this->status->label() 
            : (\App\Enums\OrderStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status);

        return [
            'id' => $this->id,
            'purchasable_type' => $type,
            'purchasable_id' => $this->purchasable_id,
            'purchasable_name' => $purchasableName,
            'amount' => round($this->amount_cents / 100, 2),
            'currency' => $this->currency ?? 'EGP',
            'status' => $statusValue,
            'status_label' => $statusLabel,
            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
        ];
    }
}
