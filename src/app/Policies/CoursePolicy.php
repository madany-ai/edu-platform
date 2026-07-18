<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
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

    public function view(User $user, Course $course): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('instructor');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->id === $course?->instructor_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course?->instructor_id;
    }

    public function manageEnrollments(User $user, Course $course): bool
    {
        return $user->id === $course?->instructor_id;
    }
}
