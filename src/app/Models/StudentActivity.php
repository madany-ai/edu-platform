<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentActivity extends Model
{
    use HasUuids;
    protected $fillable = ['student_id', 'type', 'entity_type', 'entity_id', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'json'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
