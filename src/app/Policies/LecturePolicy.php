<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lecture;

class LecturePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lecture $lecture): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('assistant');
    }

    public function update(User $user, Lecture $lecture): bool
    {
        return ! $user->hasRole('assistant');
    }

    public function delete(User $user, Lecture $lecture): bool
    {
        return ! $user->hasRole('assistant');
    }
}
