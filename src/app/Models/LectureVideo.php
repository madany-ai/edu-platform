<?php

namespace App\Models;

use App\Models\Lecture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureVideo extends Model
{
    use HasUuids;
    protected $fillable = [
        'lecture_id', 
        'bunny_video_id', 
        'duration', 
        'original_filename', 
        'video_path', 
        'status'
    ];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }
}
