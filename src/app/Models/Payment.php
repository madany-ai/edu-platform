<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'provider', 'transaction_id', 'amount', 'status'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
