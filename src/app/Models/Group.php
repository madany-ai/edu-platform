<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'academic_year_id',
        'academic_year',
        'name',
        'schedule',
        'capacity',
        'is_active',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope(new \App\Models\Scopes\AcademicYearScope);
    }

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'is_active' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function sessionYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class);
    }

    public function centerExams(): HasMany
    {
        return $this->hasMany(CenterExam::class);
    }
}
