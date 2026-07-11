<?php

namespace App\Models;

use App\Models\Lecture;
use App\Models\QuestionReply;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionsPost extends Model
{
    use HasUuids;
    protected $fillable = ['lecture_id', 'student_id', 'body'];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(QuestionReply::class, 'question_id');
    }
}
