<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles Setup
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'assistant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // 2. Instructor Account (Admin)
        $instructor = User::firstOrCreate(
            ['email' => 'admin@mrhifnimuhammad.tech'],
            [
                'name' => 'الأستاذ محمد حفني',
                'password' => Hash::make('admin2026@'),
                'status' => 'active',
            ]
        );
        $instructor->assignRole('instructor');

        // 3. Assistant 1 Account
        $assistant1 = User::firstOrCreate(
            ['email' => 'assistant1@mrhifnimuhammad.tech'],
            [
                'name' => 'مساعد 1',
                'password' => Hash::make('admin2026@'),
                'status' => 'active',
            ]
        );
        $assistant1->assignRole('assistant');

        // 4. Assistant 2 Account
        $assistant2 = User::firstOrCreate(
            ['email' => 'assistant2@mrhifnimuhammad.tech'],
            [
                'name' => 'مساعد 2',
                'password' => Hash::make('admin2026@'),
                'status' => 'active',
            ]
        );
        $assistant2->assignRole('assistant');
    }
}

