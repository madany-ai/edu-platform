<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lecture;

use App\Models\CourseSection;

class LecturePolicy
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

    public function view(User $user, Lecture $lecture): bool
    {
        return true;
    }

    public function create(User $user, ?CourseSection $section = null): bool
    {
        if ($section === null) {
            return $user->hasRole('instructor');
        }
        return $user->hasRole('instructor') && $user->id === $section->course?->instructor_id;
    }

    public function update(User $user, Lecture $lecture): bool
    {
        return $user->hasRole('instructor') && $user->id === $lecture->section?->course?->instructor_id;
    }

    public function delete(User $user, Lecture $lecture): bool
    {
        return $user->hasRole('instructor') && $user->id === $lecture->section?->course?->instructor_id;
    }
}
