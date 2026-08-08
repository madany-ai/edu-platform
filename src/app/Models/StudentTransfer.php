<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'from_group_id',
        'to_group_id',
        'reason',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'from_group_id');
    }

    public function toGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'to_group_id');
    }
}
