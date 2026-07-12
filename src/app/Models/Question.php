<?php

namespace App\Models;

use App\Models\Choice;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasUuids;
    protected $fillable = ['exam_id', 'type', 'question', 'degree', 'image_path'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class);
    }

    public function getImagePathAttribute($value)
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
