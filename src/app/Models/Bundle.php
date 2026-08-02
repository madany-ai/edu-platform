<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Bundle extends Model
{
    use HasUuids;
    protected $fillable = [
        'instructor_id',
        'name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bundle_products');
    }

    public function resolveLectureIds(): Collection
    {
        return $this->products->flatMap(fn (Product $p) => $p->resolveLectureIds())->unique()->values();
    }
}
