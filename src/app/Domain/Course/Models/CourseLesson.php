<?php

namespace App\Domain\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Course\Models\Course;

class CourseLesson extends Model
{
    protected $fillable = [
        'course_id', 'title', 'duration_minutes',
        'video_url', 'content', 'sort_order',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
