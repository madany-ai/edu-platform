<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class Product extends Model
{
    protected $fillable = [
        'instructor_id',
        'name',
        'sellable_id',
        'sellable_type',
        'price',
        'access_duration_days',
        'is_active',
    ];

    public function sellable(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolveLectureIds(): Collection
    {
        return match (true) {
            $this->sellable instanceof Lecture => collect([$this->sellable_id]),
            $this->sellable instanceof CourseSection => $this->sellable->lectures()->pluck('id'),
            $this->sellable instanceof Course => $this->sellable->lectures()->pluck('lectures.id'),
            default => collect(),
        };
    }
}
