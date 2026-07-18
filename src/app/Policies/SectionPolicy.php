<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseSection;

class SectionPolicy
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

    public function view(User $user, CourseSection $section): bool
    {
        return true;
    }

    public function create(User $user, ?Course $course = null): bool
    {
        if ($course === null) {
            return $user->hasRole('instructor');
        }
        return $user->hasRole('instructor') && $user->id === $course->instructor_id;
    }

    public function update(User $user, CourseSection $section): bool
    {
        return $user->hasRole('instructor') && $user->id === $section->course?->instructor_id;
    }

    public function delete(User $user, CourseSection $section): bool
    {
        return $user->hasRole('instructor') && $user->id === $section->course?->instructor_id;
    }
}
