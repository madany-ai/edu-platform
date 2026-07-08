<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'student']);
        $role = Role::firstOrCreate(['name' => 'instructor']);

        $user = User::firstOrCreate(
            ['email' => 'admin@lms.local'],
            [
                'name' => 'Instructor',
                'password' => Hash::make('admin123'),
            ]
        );

        if (! $user->hasRole('instructor')) {
            $user->assignRole($role);
        }
    }
}
