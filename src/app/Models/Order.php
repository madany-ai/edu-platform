<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\OrderStatus;

class Order extends Model
{
    use HasUuids;
    protected $fillable = [
        'student_id',
        'purchasable_id',
        'purchasable_type',
        'amount_cents',
        'currency',
        'payment_method',
        'transaction_id',
        'status',
        'paid_at'
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount_cents' => 'integer',
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
}
