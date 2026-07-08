<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Auth\Models\User;
use App\Domain\Course\Models\Category;
use App\Domain\Course\Models\CourseLesson;
use App\Domain\Course\Models\Enrollment;
use App\Domain\Course\Models\CourseReview;

class Course extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'short_description',
        'price', 'thumbnail', 'level', 'duration_minutes',
        'language', 'is_published', 'instructor_id', 'category_id',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }
}
