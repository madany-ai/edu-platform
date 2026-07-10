<?php

namespace App\Models;

use App\Models\Lecture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureFile extends Model
{
    protected $fillable = ['lecture_id', 'type', 'file_path'];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }
}
