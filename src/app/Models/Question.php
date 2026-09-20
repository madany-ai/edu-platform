<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use \App\Traits\ResolvesMinioUrls, HasUuids;

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
        if (request()->is('api/*')) {
            return $this->resolveMinioUrl($value);
        }

        return $value;
    }
}
