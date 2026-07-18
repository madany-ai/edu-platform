<?php

namespace App\Traits;

use App\Models\Student;
use App\Models\User;

trait ResolvesStudent
{
    protected function resolveStudent(User $user): ?Student
    {
        return Student::where('user_id', $user->id)->first();
    }
}
