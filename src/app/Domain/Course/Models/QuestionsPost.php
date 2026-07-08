<?php

namespace App\Domain\Course\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionsPost extends Model
{
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
