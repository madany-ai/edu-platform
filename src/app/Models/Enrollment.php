<?php

namespace App\Models;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasUuids;
    protected $fillable = ['student_id', 'course_id', 'status', 'source', 'started_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'status' => \App\Enums\EnrollmentStatus::class,
            'source' => \App\Enums\EnrollmentSource::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Override save to prevent persisting synthetic entitlement enrollments.
     */
    public function save(array $options = []): bool
    {
        if ($this->id && str_starts_with((string) $this->id, 'entitlement-fake-')) {
            throw new \RuntimeException("Cannot save a synthetic entitlement enrollment.");
        }
        return parent::save($options);
    }
}
