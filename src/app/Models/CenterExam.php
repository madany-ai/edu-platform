<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CenterExam extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'center_exams';

    protected $fillable = [
        'name',
        'description',
        'total_marks',
        'date',
        'semester_id',
        'academic_year_id',
        'group_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_marks' => 'decimal:2',
        ];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(CenterGrade::class, 'center_exam_id');
    }
}
