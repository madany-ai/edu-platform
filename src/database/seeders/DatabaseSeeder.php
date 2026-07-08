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
        $user = User::create([
            'name' => 'Instructor',
            'email' => 'admin@lms.local',
            'password' => Hash::make('admin123'),
        ]);

        $role = Role::create(['name' => 'instructor']);
        $user->assignRole($role);
    }
}
