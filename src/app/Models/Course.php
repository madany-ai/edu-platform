<?php

namespace App\Models;

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    protected $fillable = [
        'title', 'description', 'thumbnail', 'status',
        'price', 'instructor_id',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_assistants')
            ->withTimestamps();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function lectures(): HasManyThrough
    {
        return $this->HasManyThrough(Lecture::class, CourseSection::class);
    }
}
