<?php

namespace App\Models;

use App\Models\Lecture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureVideo extends Model
{
    protected $fillable = ['lecture_id', 'bunny_video_id', 'duration'];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }
}
