<?php

namespace App\Models;

use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\LectureFile;
use App\Models\LectureVideo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Lecture extends Model
{
    use HasUuids, LogsActivity;
    protected $fillable = [
        'section_id',
        'instructor_id',
        'title',
        'description',
        'duration',
        'sort_order',
        'video_path',
        'pdf_url',
        'status',
        'price',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'status' => \App\Enums\CourseStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'section_id', 'instructor_id', 'status', 'price'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted()
    {
        static::saved(function ($lecture) {
            $hasVideo = $lecture->video()->exists();
            $isFailed = $lecture->video && $lecture->video->status === 'failed';

            if ($lecture->video_path && ($lecture->wasChanged('video_path') || $lecture->wasRecentlyCreated || !$hasVideo || $isFailed)) {
                // Dispatch background HLS encryption processing
                \App\Jobs\ProcessVideoHLS::dispatch($lecture);
            }
        });
    }

    public function isStandalone(): bool
    {
        return $this->section_id === null;
    }

    public function resolveInstructorId(): ?string
    {
        if ($this->instructor_id) {
            return $this->instructor_id;
        }

        $this->loadMissing('section.course');
        return $this->section?->course?->instructor_id;
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function products()
    {
        return $this->morphMany(Product::class, 'sellable');
    }

    public function scopeStandalone($query)
    {
        return $query->whereNull('section_id');
    }

    public function scopeInCourse($query)
    {
        return $query->whereNotNull('section_id');
    }

    public function video(): HasOne
    {
        return $this->hasOne(LectureVideo::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(LectureFile::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class)->where('is_assignment', false);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Exam::class)->where('is_assignment', true);
    }
}
