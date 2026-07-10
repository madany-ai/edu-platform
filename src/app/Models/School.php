<?php

namespace App\Models;

use App\Models\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class School extends Model
{
    protected $fillable = ['city_id', 'name', 'type'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
