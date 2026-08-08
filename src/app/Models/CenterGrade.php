<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterGrade extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'center_grades';

    protected $fillable = [
        'center_exam_id',
        'student_id',
        'score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function centerExam(): BelongsTo
    {
        return $this->belongsTo(CenterExam::class, 'center_exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
