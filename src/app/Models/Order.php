<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Enums\OrderStatus;

class Order extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'student_id',
        'purchasable_id',
        'purchasable_type',
        'amount_cents',
        'currency',
        'payment_method',
        'payment_gateway',
        'checkout_id',
        'payment_url',
        'gateway_reference',
        'metadata',
        'failure_reason',
        'transaction_id',
        'status',
        'paid_at',
        'refunded_at',
        'amount_refunded_cents',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'amount_cents' => 'integer',
            'amount_refunded_cents' => 'integer',
            'metadata' => 'array',
            'status' => OrderStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function purchasable()
    {
        return $this->morphTo();
    }

    public function entitlements()
    {
        return $this->hasMany(Entitlement::class, 'order_id');
    }
}
