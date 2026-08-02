<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lecture;
use App\Models\Exam;

class ExamPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Exam $exam): bool
    {
        return true;
    }

    public function create(User $user, ?Lecture $lecture = null): bool
    {
        if ($lecture === null) {
            return $user->hasRole('instructor');
        }
        return $user->hasRole('instructor') && $user->id === $lecture->section?->course?->instructor_id;
    }

    public function update(User $user, Exam $exam): bool
    {
        if ($exam->lecture === null) {
            return false;
        }
        return $user->hasRole('instructor') && $user->id === $exam->lecture->section?->course?->instructor_id;
    }

    public function delete(User $user, Exam $exam): bool
    {
        if ($exam->lecture === null) {
            return false;
        }
        return $user->hasRole('instructor') && $user->id === $exam->lecture->section?->course?->instructor_id;
    }
}
