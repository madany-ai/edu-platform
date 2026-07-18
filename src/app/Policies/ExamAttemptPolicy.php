<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExamAttempt;

class ExamAttemptPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function submit(User $user, ExamAttempt $attempt): bool
    {
        return $attempt->student && $user->id === $attempt->student->user_id;
    }

    public function viewResult(User $user, ExamAttempt $attempt): bool
    {
        if ($attempt->student && $user->id === $attempt->student->user_id) {
            return true;
        }

        // Also allow the instructor who owns the course to view the results
        $instructorId = $attempt->exam?->lecture?->section?->course?->instructor_id ?? null;
        return $instructorId && $user->id === $instructorId;
    }
}
