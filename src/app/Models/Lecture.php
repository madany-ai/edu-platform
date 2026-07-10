<?php

namespace App\Models;

use App\Models\Assignment;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\LectureFile;
use App\Models\LectureVideo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lecture extends Model
{
    protected $fillable = ['section_id', 'title', 'description', 'duration', 'sort_order', 'video_path', 'pdf_url'];

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

    public function exam(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }
}
