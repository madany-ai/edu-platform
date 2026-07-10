<?php

namespace App\Domain\Course\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionReply extends Model
{
    protected $fillable = ['question_id', 'user_id', 'body'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionsPost::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
