<?php

namespace App\Policies;

use App\Models\CenterExam;
use App\Models\User;

class CenterExamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CenterExam $model_var): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function update(User $user, CenterExam $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function delete(User $user, CenterExam $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function restore(User $user, CenterExam $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function forceDelete(User $user, CenterExam $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }
}
