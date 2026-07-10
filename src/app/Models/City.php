<?php

namespace App\Models;

use App\Models\Governorate;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['governorate_id', 'name'];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
