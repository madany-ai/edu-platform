<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Group $model_var): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function update(User $user, Group $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function delete(User $user, Group $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function restore(User $user, Group $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function forceDelete(User $user, Group $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }
}
