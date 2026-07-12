<?php

namespace App\Models;

use App\Models\Assignment;
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
    protected $fillable = ['section_id', 'title', 'description', 'duration', 'sort_order', 'video_path', 'pdf_url'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'section_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getProgressAttribute()
    {
        $user = auth('sanctum')->user();
        if (!$user) return null;
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        if (!$student) return null;

        $activity = \App\Models\StudentActivity::where('student_id', $student->id)
            ->where('type', 'video_progress')
            ->where('entity_type', self::class)
            ->where('entity_id', $this->id)
            ->first();

        return $activity ? $activity->metadata : null;
    }

    protected static function booted()
    {
        static::saved(function ($lecture) {
            if ($lecture->video_path && ($lecture->wasChanged('video_path') || $lecture->wasRecentlyCreated)) {
                // Dispatch background HLS encryption processing
                \App\Jobs\ProcessVideoHLS::dispatch($lecture);
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
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
