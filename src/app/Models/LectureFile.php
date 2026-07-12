<?php

namespace App\Models;

use App\Models\Lecture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureFile extends Model
{
    use HasUuids;
    protected $fillable = ['lecture_id', 'type', 'file_path'];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    public function getFilePathAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        try {
            $url = \Illuminate\Support\Facades\Storage::disk('minio')
                ->temporaryUrl($value, now()->addHours(2));
            return str_replace('http://minio:9000', 'http://localhost:9000', $url);
        } catch (\Exception $e) {
            $url = \Illuminate\Support\Facades\Storage::disk('minio')->url($value);
            return str_replace('http://minio:9000', 'http://localhost:9000', $url);
        }
    }
}
