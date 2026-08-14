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
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'assistant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // 2. Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@mrhifnimuhammad.tech'],
            [
                'name' => 'الحساب العام',
                'password' => Hash::make('admin2026@'),
                'status' => 'active',
            ]
        );
        $admin->assignRole('super_admin');

        // 3. Teacher Account (Mr Hefni)
        $instructor = User::firstOrCreate(
            ['email' => 'mrhefni@mrhifnimuhammad.tech'],
            [
                'name' => 'مستر حفني',
                'password' => Hash::make('admin2026@'),
                'status' => 'active',
            ]
        );
        $instructor->assignRole('instructor');
    }
}
