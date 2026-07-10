<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Bundle extends Model
{
    protected $fillable = [
        'instructor_id',
        'name',
        'price_cents',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bundle_products');
    }

    public function resolveLectureIds(): Collection
    {
        return $this->products->flatMap(fn (Product $p) => $p->resolveLectureIds())->unique()->values();
    }
}
