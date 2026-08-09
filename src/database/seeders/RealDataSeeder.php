<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\AcademicTrack;
use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\CenterGrade;
use App\Models\City;
use App\Models\CommunicationLog;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Governorate;
use App\Models\GradeLevel;
use App\Models\Group;
use App\Models\Lecture;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
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

        // 2. Instructor Account
        $instructor = User::firstOrCreate(
            ['email' => 'teacher@lms.local'],
            [
                'name' => 'أ. محمد خالد (مدرس المادة)',
                'password' => Hash::make('password123'),
                'phone' => '01000000000',
                'status' => 'active',
            ]
        );
        $instructor->assignRole('instructor');

        // 3. Assistant Account
        $assistant = User::firstOrCreate(
            ['email' => 'assistant@lms.local'],
            [
                'name' => 'أحمد المساعد (مسؤول السنتر)',
                'password' => Hash::make('password123'),
                'phone' => '01100000000',
                'status' => 'active',
            ]
        );
        $assistant->assignRole('assistant');

        // 4. Governorates & Cities
        $gov = Governorate::firstOrCreate(['name' => 'القاهرة']);
        $city = City::firstOrCreate(
            ['name' => 'مدينة نصر', 'governorate_id' => $gov->id]
        );

        // 5. Academic Year & Semester
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026 - 2027'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
            ]
        );

        $semester1 = Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'الترم الأول'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2027-01-31',
                'is_active' => true,
            ]
        );

        // 6. Grade Levels (إعدادي وثانوي)
        $gradesConfig = [
            ['name' => 'الصف الأول الإعدادي', 'sort' => 1],
            ['name' => 'الصف الثاني الإعدادي', 'sort' => 2],
            ['name' => 'الصف الثالث الإعدادي', 'sort' => 3],
            ['name' => 'الصف الأول الثانوي', 'sort' => 4],
            ['name' => 'الصف الثاني الثانوي', 'sort' => 5],
            ['name' => 'الصف الثالث الثانوي', 'sort' => 6],
        ];

        $gradeModels = [];
        foreach ($gradesConfig as $g) {
            $gradeModels[$g['name']] = GradeLevel::firstOrCreate(
                ['name' => $g['name']],
                ['sort_order' => $g['sort']]
            );
        }

        // Academic Tracks (للثانوي)
        $trackSci = AcademicTrack::firstOrCreate(['name' => 'علمي علوم']);
        $trackMath = AcademicTrack::firstOrCreate(['name' => 'علمي رياضة']);
        $trackLit = AcademicTrack::firstOrCreate(['name' => 'أدبي']);

        // 7. Groups (المجموعات الدراسية للسنتر)
        $group3Prep = Group::firstOrCreate(
            ['name' => 'تالتة إعدادي - مجموعة الأحد والأربعاء (4:00 عصراً)'],
            [
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $gradeModels['الصف الثالث الإعدادي']->id,
                'capacity' => 45,
                'schedule' => [
                    ['day' => 'الأحد', 'time' => '16:00'],
                    ['day' => 'الأربعاء', 'time' => '16:00'],
                ],
                'is_active' => true,
            ]
        );

        $group1Sec = Group::firstOrCreate(
            ['name' => 'أولى ثانوي - مجموعة السبت والثلاثاء (5:30 مساءً)'],
            [
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $gradeModels['الصف الأول الثانوي']->id,
                'capacity' => 50,
                'schedule' => [
                    ['day' => 'السبت', 'time' => '17:30'],
                    ['day' => 'الثلاثاء', 'time' => '17:30'],
                ],
                'is_active' => true,
            ]
        );

        $group3Sec = Group::firstOrCreate(
            ['name' => 'تالتة ثانوي - مجموعة الاثنين والخميس (6:00 مساءً)'],
            [
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $gradeModels['الصف الثالث الثانوي']->id,
                'capacity' => 60,
                'schedule' => [
                    ['day' => 'الاثنين', 'time' => '18:00'],
                    ['day' => 'الخميس', 'time' => '18:00'],
                ],
                'is_active' => true,
            ]
        );

        // 8. Students Data Seeding
        $studentsList = [
            // تالتة إعدادي
            [
                'first_name' => 'عمر', 'second_name' => 'خالد', 'third_name' => 'إبراهيم', 'last_name' => 'السيد',
                'email' => 'omar.khaled@student.local', 'phone' => '01012345671', 'father_phone' => '01212345671', 'mother_phone' => '01112345671',
                'group' => $group3Prep, 'grade' => $gradeModels['الصف الثالث الإعدادي'], 'code' => 'ST2026101',
            ],
            [
                'first_name' => 'مريم', 'second_name' => 'أحمد', 'third_name' => 'محمود', 'last_name' => 'حسن',
                'email' => 'maryam.ahmed@student.local', 'phone' => '01012345672', 'father_phone' => '01212345672', 'mother_phone' => '01112345672',
                'group' => $group3Prep, 'grade' => $gradeModels['الصف الثالث الإعدادي'], 'code' => 'ST2026102',
            ],
            [
                'first_name' => 'يوسف', 'second_name' => 'مصطفى', 'third_name' => 'عادل', 'last_name' => 'كمال',
                'email' => 'youssef.mostafa@student.local', 'phone' => '01012345673', 'father_phone' => '01212345673', 'mother_phone' => '01112345673',
                'group' => $group3Prep, 'grade' => $gradeModels['الصف الثالث الإعدادي'], 'code' => 'ST2026103',
            ],
            [
                'first_name' => 'فاطمة', 'second_name' => 'علي', 'third_name' => 'حسن', 'last_name' => 'منصور',
                'email' => 'fatma.ali@student.local', 'phone' => '01012345674', 'father_phone' => '01212345674', 'mother_phone' => '01112345674',
                'group' => $group3Prep, 'grade' => $gradeModels['الصف الثالث الإعدادي'], 'code' => 'ST2026104',
            ],
            [
                'first_name' => 'كريم', 'second_name' => 'سامح', 'third_name' => 'فاروق', 'last_name' => 'فؤاد',
                'email' => 'karim.sameh@student.local', 'phone' => '01012345675', 'father_phone' => '01212345675', 'mother_phone' => '01112345675', 'code' => 'ST2026105',
                'group' => $group3Prep, 'grade' => $gradeModels['الصف الثالث الإعدادي'],
            ],

            // أولى ثانوي
            [
                'first_name' => 'زياد', 'second_name' => 'طارق', 'third_name' => 'عبدالعزيز', 'last_name' => 'الشريف',
                'email' => 'zeyad.tarek@student.local', 'phone' => '01012345676', 'father_phone' => '01212345676', 'mother_phone' => '01112345676', 'code' => 'ST2026106',
                'group' => $group1Sec, 'grade' => $gradeModels['الصف الأول الثانوي'],
            ],
            [
                'first_name' => 'سارة', 'second_name' => 'حسام', 'third_name' => 'جمال', 'last_name' => 'الدين',
                'email' => 'sara.hossam@student.local', 'phone' => '01012345677', 'father_phone' => '01212345677', 'mother_phone' => '01112345677', 'code' => 'ST2026107',
                'group' => $group1Sec, 'grade' => $gradeModels['الصف الأول الثانوي'],
            ],

            // تالتة ثانوي
            [
                'first_name' => 'محمد', 'second_name' => 'عبدالرحمن', 'third_name' => 'سليمان', 'last_name' => 'راضي',
                'email' => 'mohamed.abdo@student.local', 'phone' => '01012345678', 'father_phone' => '01212345678', 'mother_phone' => '01112345678', 'code' => 'ST2026108',
                'group' => $group3Sec, 'grade' => $gradeModels['الصف الثالث الثانوي'], 'track' => $trackSci,
            ],
            [
                'first_name' => 'نور', 'second_name' => 'شريف', 'third_name' => 'عصام', 'last_name' => 'مظهر',
                'email' => 'nour.sherif@student.local', 'phone' => '01012345679', 'father_phone' => '01212345679', 'mother_phone' => '01112345679', 'code' => 'ST2026109',
                'group' => $group3Sec, 'grade' => $gradeModels['الصف الثالث الثانوي'], 'track' => $trackMath,
            ],
        ];

        $studentModels = [];
        foreach ($studentsList as $s) {
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name' => "{$s['first_name']} {$s['second_name']} {$s['last_name']}",
                    'password' => Hash::make('password123'),
                    'phone' => $s['phone'],
                    'status' => 'active',
                ]
            );
            $user->assignRole('student');

            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'student_code' => $s['code'],
                    'first_name' => $s['first_name'],
                    'second_name' => $s['second_name'],
                    'third_name' => $s['third_name'],
                    'last_name' => $s['last_name'],
                    'phone' => $s['phone'],
                    'father_phone' => $s['father_phone'],
                    'mother_phone' => $s['mother_phone'],
                    'guardian_job' => 'موظف',
                    'governorate_id' => $gov->id,
                    'city_id' => $city->id,
                    'grade_level_id' => $s['grade']->id,
                    'academic_track_id' => isset($s['track']) ? $s['track']->id : null,
                    'group_id' => $s['group']->id,
                    'gender' => 'male',
                    'birth_date' => '2008-01-01',
                    'is_verified' => true,
                ]
            );

            $studentModels[] = $student;
        }

        // 9. Academic Sessions & Attendance Simulation (حسابات الحصص والغياب)
        $topics = [
            'حصة 1: التفاعلات الكيميائية والمفاهيم الأساسية',
            'حصة 2: سرعة التفاعلات الكيميائية والعوامل المؤثرة',
            'حصة 3: التيار الكهربي والجهد الكهربي',
            'حصة 4: قانون أوم وتطبيقاته العملية',
        ];

        $dates = [
            now()->subWeeks(3)->format('Y-m-d'),
            now()->subWeeks(2)->format('Y-m-d'),
            now()->subWeeks(1)->format('Y-m-d'),
            now()->format('Y-m-d'),
        ];

        $sessions3Prep = [];
        foreach ($topics as $index => $topic) {
            $session = AcademicSession::create([
                'group_id' => $group3Prep->id,
                'date' => $dates[$index],
                'topic' => $topic,
                'notes' => 'تم تغطية أفكار الامتحانات الهامة في هذه الحصة',
                'created_by' => $instructor->id,
            ]);
            $sessions3Prep[] = $session;

            // Attendance status simulation
            foreach ($studentModels as $stIndex => $student) {
                if ($student->group_id !== $group3Prep->id) continue;

                // Simulate attendance pattern: Omar & Maryam always present, Youssef late once, Fatma absent once
                $status = 'present';
                if ($student->first_name === 'يوسف' && $index === 2) {
                    $status = 'late';
                } elseif ($student->first_name === 'فاطمة' && $index === 1) {
                    $status = 'absent';
                } elseif ($student->first_name === 'كريم' && $index >= 1) {
                    $status = 'absent'; // Frequent absentee test case
                }

                Attendance::create([
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'is_guest' => false,
                ]);

                // Dispatch notification
                app(NotificationService::class)->notifyAttendance(
                    $student,
                    $status,
                    $session->topic,
                    $session->date->format('Y-m-d')
                );
            }
        }

        // Add a Guest attendance simulation: Zeyad (from Group 1Sec) attends 3Prep session as a guest!
        $guestStudent = $studentModels[5]; // زياد
        Attendance::create([
            'session_id' => $sessions3Prep[3]->id,
            'student_id' => $guestStudent->id,
            'status' => 'guest',
            'is_guest' => true,
            'original_group_id' => $guestStudent->group_id,
        ]);

        // 10. Center Exams & Grades Simulation (الامتحانات الورقية ورصد الدرجات)
        $exam1 = CenterExam::create([
            'name' => 'اختبار شهر سبتمبر - علوم (تالتة إعدادي)',
            'description' => 'شمل الوحدة الأولى الكاملة للتفاعلات الكيميائية',
            'total_marks' => 30.00,
            'date' => now()->subWeeks(2)->format('Y-m-d'),
            'semester_id' => $semester1->id,
            'academic_year_id' => $academicYear->id,
            'group_id' => $group3Prep->id,
            'created_by' => $instructor->id,
        ]);

        $scoresConfig = [
            'عمر' => 29.5,   // 🥇 98.3%
            'مريم' => 28.5,  // 🥈 95.0%
            'يوسف' => 26.0,  // 🥉 86.6%
            'فاطمة' => 24.0, // 80.0%
            'كريم' => 14.5,  // 48.3%
        ];

        foreach ($studentModels as $student) {
            if ($student->group_id !== $group3Prep->id) continue;
            $score = $scoresConfig[$student->first_name] ?? 20.0;

            CenterGrade::create([
                'center_exam_id' => $exam1->id,
                'student_id' => $student->id,
                'score' => $score,
                'notes' => $score >= 28 ? 'ممتاز، استمر بالمركز الأول' : 'تحتاج لمراجعة مسائل التفاعلات',
            ]);

            app(NotificationService::class)->notifyCenterGrade(
                $student,
                $exam1->name,
                $score,
                30.00
            );
        }

        // 11. Communication Logs Simulation
        CommunicationLog::create([
            'student_id' => $studentModels[4]->id, // كريم (غائب)
            'date' => now()->format('Y-m-d'),
            'contact_method' => 'اتصال هاتف',
            'reason' => 'متابعة غياب كريم في آخر حصتين',
            'notes' => 'تم التواصل مع الأب وأفاد بوجود عذر مرضي، وسيحضر الحصة التعويضية القادمة.',
            'created_by' => $assistant->id,
        ]);

        // 12. Online Course & Lectures Setup (للتجربة الأونلاين)
        $course = Course::firstOrCreate(
            ['title' => 'المراجعة الشاملة في العلوم - الصف الثالث الإعدادي'],
            [
                'instructor_id' => $instructor->id,
                'description' => 'كورسات ومحاضرات مراجعة نهائية وشرح مبسط لجميع الأبواب مع بنك أسئلة واختبارات تفاعلية.',
                'price' => 250.00,
                'status' => 'published',
            ]
        );

        $section = CourseSection::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'الوحدة الأولى: التفاعلات الكيميائية'],
            ['sort_order' => 1]
        );

        Lecture::firstOrCreate(
            ['section_id' => $section->id, 'title' => 'المحاضرة الأولى: التفاعلات والسرعة الكيميائية'],
            [
                'duration' => 3600,
                'sort_order' => 1,
            ]
        );
    }
}
