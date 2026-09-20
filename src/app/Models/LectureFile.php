<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureFile extends Model
{
    use \App\Traits\ResolvesMinioUrls, HasUuids;

    protected $fillable = ['lecture_id', 'type', 'notes', 'file_path'];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    public function getFilePathAttribute($value)
    {
        if (request()->is('api/*')) {
            return $this->resolveMinioUrl($value);
        }

        return $value;
    }
}
