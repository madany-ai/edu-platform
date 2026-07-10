<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatistic extends Model
{
    protected $fillable = [
        'student_id', 'total_watch_minutes', 'attendance_rate',
        'average_exam_score', 'completed_courses', 'completed_lectures', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
