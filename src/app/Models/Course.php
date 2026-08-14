<?php

namespace App\Models;

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Course extends Model
{
    use HasUuids, LogsActivity;
    
    protected $fillable = [
        'title', 'description', 'thumbnail', 'status',
        'price', 'instructor_id', 'course_code', 'academic_year',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'price'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new \App\Models\Scopes\AcademicYearScope);

        static::creating(function (Course $course) {
            if (! $course->course_code) {
                $course->course_code = app(CodeGeneratorService::class)->generateCourseCode();
            }
        });
    }

    public static function clearPublishedCache(): void
    {
        \Illuminate\Support\Facades\Cache::tags(['courses'])->flush();
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_assistants')
            ->using(CourseAssistant::class)
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
        return $this->hasManyThrough(
            Lecture::class,
            CourseSection::class,
            'course_id',
            'section_id',
            'id',
            'id'
        );
    }

    protected function casts(): array
    {
        return [
            'status' => \App\Enums\CourseStatus::class,
            'price' => 'decimal:2',
        ];
    }
}
