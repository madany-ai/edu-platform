<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure student role exists
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        for ($i = 1; $i <= 10; $i++) {
            $user = User::firstOrCreate(
                ['email' => 'student' . $i . '@example.com'],
                [
                    'name' => 'Student ' . $i,
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]
            );
            
            $user->assignRole('student');

            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_code' => 'STU-1000' . $i,
                    'first_name' => 'Student',
                    'second_name' => 'Fake',
                    'third_name' => 'Middle',
                    'last_name' => (string) $i,
                    'phone' => '010' . str_pad($i, 8, '0', STR_PAD_LEFT),
                    'father_phone' => '012' . str_pad($i, 8, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 == 0 ? 'male' : 'female',
                    'birth_date' => '2005-01-01',
                    'academic_year' => '1',
                    'school_name' => 'مدرسة تجريبية',
                    'is_verified' => true,
                ]
            );
        }

        // Fake Courses
        $instructor = User::where('email', 'mrhefni@mrhifnimuhammad.tech')->first();
        if ($instructor) {
            for ($c = 1; $c <= 5; $c++) {
                \App\Models\Course::updateOrCreate(
                    ['course_code' => 'CRS-' . $c],
                    [
                        'title' => 'دورة تجريبية ' . $c,
                        'description' => 'وصف الدورة التجريبية ' . $c,
                        'price' => 100 * $c,
                        'status' => 'published',
                        'instructor_id' => $instructor->id,
                        'academic_year' => '1',
                    ]
                );
            }
        }
    }
}
