<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\CenterGrade;
use App\Models\Course;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::whereHas('roles', fn ($q) => $q->where('name', 'instructor'))->first();
        if (! $instructor) {
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

        $codeService = app(CodeGeneratorService::class);
        $statuses = ['present', 'absent', 'late', 'excused'];
        $years = ['sec_1', 'sec_2', 'sec_3'];

        $studentCounter = 1;

        foreach ($years as $year) {
            // 1. Seed Courses
            $courses = [];
            $courseTitles = ["كورس الكيمياء - $year", "مراجعة $year", "تأسيس $year"];
            foreach ($courseTitles as $title) {
                $courses[] = Course::create([
                    'title' => $title,
                    'description' => 'هذا الكورس مصمم لتقديم محتوى تعليمي متميز للطلاب.',
                    'status' => 'published',
                    'price' => rand(100, 500),
                    'instructor_id' => $instructor->id,
                    'academic_year' => $year,
                ]);
            }

            // 2. Seed Groups
            $groups = [];
            $groupNames = ["مجموعة الأحد $year", "مجموعة الاثنين $year"];
            foreach ($groupNames as $name) {
                $groups[] = Group::create([
                    'academic_year' => $year,
                    'name' => $name,
                    'schedule' => ['الأحد', 'الثلاثاء'],
                    'capacity' => 50,
                    'is_active' => true,
                ]);
            }

            // 3. Seed Students
            $students = [];
            $names = ['أحمد', 'محمد', 'محمود', 'مصطفى', 'علي', 'عمر', 'كريم', 'خالد', 'طارق', 'يوسف'];

            foreach ($names as $index => $firstName) {
                $user = User::firstOrCreate(
                    ['email' => 'student'.$studentCounter.'@demo.com'],
                    [
                        'name' => $firstName.' أحمد محمود '.$year,
                        'phone' => '010'.rand(10000000, 99999999),
                        'password' => Hash::make('password'),
                        'status' => 'active',
                    ]
                );
                $user->assignRole('student');

                $student = Student::firstOrNew(['user_id' => $user->id]);
                $student->fill([
                    'first_name' => $firstName,
                    'second_name' => 'أحمد',
                    'third_name' => 'محمود',
                    'last_name' => 'إبراهيم',
                    'phone' => $user->phone,
                    'father_phone' => '011'.rand(10000000, 99999999),
                    'academic_year' => $year,
                    'group_id' => $groups[$index % 2]->id,
                    'gender' => 'male',
                    'birth_date' => now()->subYears(18)->format('Y-m-d'),
                    'is_verified' => true,
                ]);
                if (! $student->student_code) {
                    $student->student_code = $codeService->generateStudentCode($student);
                }
                $student->save();
                $students[] = $student;
                $studentCounter++;
            }

            // 4. Seed Academic Sessions & Attendance
            foreach ($groups as $group) {
                $groupStudents = array_filter($students, fn ($s) => $s->group_id === $group->id);

                for ($i = 0; $i < 3; $i++) {
                    $session = AcademicSession::create([
                        'group_id' => $group->id,
                        'date' => now()->subDays($i * 7),
                        'topic' => 'محاضرة رقم '.($i + 1),
                        'created_by' => $instructor->id,
                    ]);

                    foreach ($groupStudents as $student) {
                        Attendance::create([
                            'session_id' => $session->id,
                            'student_id' => $student->id,
                            'status' => $statuses[array_rand($statuses)],
                            'is_guest' => false,
                        ]);
                    }
                }
            }

            // 5. Seed Center Exams & Grades
            foreach ($groups as $group) {
                $groupStudents = array_filter($students, fn ($s) => $s->group_id === $group->id);

                for ($i = 1; $i <= 2; $i++) {
                    $exam = CenterExam::create([
                        'name' => 'امتحان شامل '.$i.' - '.$year,
                        'total_marks' => 100,
                        'date' => now()->subDays($i * 14),
                        'group_id' => $group->id,
                        'created_by' => $instructor->id,
                    ]);

                    foreach ($groupStudents as $student) {
                        CenterGrade::create([
                            'center_exam_id' => $exam->id,
                            'student_id' => $student->id,
                            'score' => rand(50, 100),
                        ]);
                    }
                }
            }
        }
    }
}
