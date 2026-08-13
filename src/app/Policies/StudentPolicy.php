<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Student $student): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'instructor', 'assistant']);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'instructor', 'assistant']);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'instructor']);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'instructor']);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
