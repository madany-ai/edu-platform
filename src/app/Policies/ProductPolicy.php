<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $model_var): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function update(User $user, Product $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function delete(User $user, Product $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function restore(User $user, Product $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }

    public function forceDelete(User $user, Product $model_var): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('admin');
    }
}
