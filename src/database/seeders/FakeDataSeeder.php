<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Role;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\CenterGrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('⛔ FakeDataSeeder cannot be executed in production!');
            return;
        }

        // ═══ Roles ═══
        Role::firstOrCreate(['name' => 'student']);

        // ═══ Instructor ═══
        $instructor = User::where('email', 'mrhefni@mrhifnimuhammad.tech')->first();
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

        // ═══ Academic Year ═══
        $year = AcademicYear::firstOrCreate(
            ['name' => '2025-2026'],
            ['start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]
        );

        // ═══ Students (10) ═══
        $studentNames = [
            ['first' => 'محمد', 'second' => 'علي', 'third' => 'حسين', 'last' => 'عبدالله',   'gender' => 'male'],
            ['first' => 'فاطمة', 'second' => 'أحمد', 'third' => 'محمد', 'last' => 'الزهراء', 'gender' => 'female'],
            ['first' => 'عمر', 'second' => 'خالد', 'third' => 'ياسر', 'last' => 'العتيبي',   'gender' => 'male'],
            ['first' => 'نورة', 'second' => 'سعود', 'third' => 'خالد', 'last' => 'الشمري',   'gender' => 'female'],
            ['first' => 'يوسف', 'second' => 'إبراهيم', 'third' => 'محمد', 'last' => 'القحطاني', 'gender' => 'male'],
            ['first' => 'ريم', 'second' => 'عبدالرحمن', 'third' => 'سليمان', 'last' => 'الدوسري', 'gender' => 'female'],
            ['first' => 'خالد', 'second' => 'سعد', 'third' => 'فهد', 'last' => 'المطيري',   'gender' => 'male'],
            ['first' => 'سارة', 'second' => 'ياسر', 'third' => 'عبدالله', 'last' => 'الحربي', 'gender' => 'female'],
            ['first' => 'أحمد', 'second' => 'حسام', 'third' => 'محمود', 'last' => 'الصعيدي', 'gender' => 'male'],
            ['first' => 'ياسمين', 'second' => 'عادل', 'third' => 'فتحي', 'last' => 'المنصوري', 'gender' => 'female'],
        ];

        $students = [];
        $academicYears = ['prep_3', 'prep_3', 'prep_3', 'sec_1', 'sec_1', 'sec_2', 'sec_2', 'sec_3', 'sec_3', 'sec_3'];
        $tracks = [null, null, null, null, null, null, null, 'math', 'science', 'literary'];

        foreach ($studentNames as $i => $s) {
            $user = User::firstOrCreate(
                ['email' => "fake{$i}@example.com"],
                [
                    'name' => "{$s['first']} {$s['last']}",
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]
            );
            $user->assignRole('student');

            $students[] = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $s['first'],
                    'second_name' => $s['second'],
                    'third_name' => $s['third'],
                    'last_name' => $s['last'],
                    'phone' => '010' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'father_phone' => '011' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'gender' => $s['gender'],
                    'birth_date' => '2008-' . str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT),
                    'academic_year' => $academicYears[$i],
                    'academic_track' => $tracks[$i],
                    'school_name' => 'مدرسة تجريبية',
                    'is_verified' => true,
                ]
            );
        }

        // ═══ Courses (3) ═══
        $course1 = Course::create([
            'title' => 'رياضيات أولى ثانوي — الفصل الأول',
            'description' => 'دورة شاملة لمنهج الرياضيات للصف الأول الثانوي الفصل الأول.',
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $instructor->id,
            'academic_year' => 'sec_1',
        ]);

        $course2 = Course::create([
            'title' => 'علوم تانية ثانوي — الفصل الثاني',
            'description' => 'منهج العلوم للصف الثاني الثانوي الفصل الثاني.',
            'price' => 199,
            'status' => 'published',
            'instructor_id' => $instructor->id,
            'academic_year' => 'sec_2',
        ]);

        $course3 = Course::create([
            'title' => 'مراجعة شاملة — رياضيات تالت ثانوي',
            'description' => 'دورة مراجعة شاملة لمنهج الرياضيات للصف الثالث الثانوي.',
            'price' => 299,
            'status' => 'published',
            'instructor_id' => $instructor->id,
            'academic_year' => 'sec_3',
        ]);

        // ═══ Sections & Lectures ═══
        $sections = [
            ['course' => $course1, 'title' => 'الوحدة الأولى: الدوال', 'lectures' => ['مقدمة في الدوال', 'الدوال الخطية', 'الدوال التربيعية', 'الدوال الأسية']],
            ['course' => $course1, 'title' => 'الوحدة الثانية: الأعداد المركبة', 'lectures' => ['الأعداد المركبة', 'الشكل الأسطواني', 'الشكل الكروي']],
            ['course' => $course2, 'title' => 'الوحدة الأولى: الخلية', 'lectures' => ['تشريح الخلية', 'وظائف أعضاء الخلية', 'انقسام الخلية']],
            ['course' => $course2, 'title' => 'الوحدة الثانية: الوراثة', 'lectures' => ['قوانين مندل', 'الوراثة البشريه', 'الطب الوراثي']],
            ['course' => $course3, 'title' => 'مراجعة الجبر والتحليل', 'lectures' => ['الدوال والحدود', 'المشتقات', 'التكامل']],
            ['course' => $course3, 'title' => 'مراجعة الهندسة', 'lectures' => ['الهندسة الفراغية', 'الإحداثيات']],
        ];

        $allLectures = [];
        foreach ($sections as $sIdx => $sData) {
            $section = CourseSection::create([
                'course_id' => $sData['course']->id,
                'title' => $sData['title'],
                'sort_order' => $sIdx + 1,
            ]);
            foreach ($sData['lectures'] as $lIdx => $lecTitle) {
                $lecture = Lecture::create([
                    'section_id' => $section->id,
                    'title' => $lecTitle,
                    'description' => "شرح {$lecTitle} بشكل مبسط.",
                    'duration' => mt_rand(20, 60),
                    'sort_order' => $lIdx + 1,
                ]);
                $allLectures[] = $lecture;
            }
        }

        // ═══ Enrollments (students in courses) ═══
        $enrollmentMap = [
            0 => $course1->id, 1 => $course1->id, 2 => $course1->id,
            3 => $course2->id, 4 => $course2->id, 5 => $course2->id,
            6 => $course3->id, 7 => $course3->id, 8 => $course3->id, 9 => $course3->id,
        ];

        foreach ($enrollmentMap as $sIdx => $courseId) {
            Enrollment::firstOrCreate(
                ['student_id' => $students[$sIdx]->id, 'course_id' => $courseId],
                [
                    'status' => 'active',
                    'source' => 'manual',
                    'started_at' => now()->subDays(mt_rand(1, 30)),
                ]
            );
        }

        // ═══ Center: Groups ═══
        $group1 = Group::create([
            'academic_year_id' => $year->id,
            'academic_year' => 'sec_1',
            'term' => 'term_1',
            'name' => 'أولى ثانوي — السبت والاثنين',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $group2 = Group::create([
            'academic_year_id' => $year->id,
            'academic_year' => 'sec_3',
            'term' => 'term_1',
            'name' => 'تالت ثانوي علمي — الأحد والأربعاء',
            'capacity' => 35,
            'is_active' => true,
        ]);

        // Assign some students to groups
        $students[0]->update(['group_id' => $group1->id]);
        $students[1]->update(['group_id' => $group1->id]);
        $students[2]->update(['group_id' => $group1->id]);
        $students[7]->update(['group_id' => $group2->id]);
        $students[8]->update(['group_id' => $group2->id]);
        $students[9]->update(['group_id' => $group2->id]);

        // ═══ Center: Sessions ═══
        $topics = ['مقدمة في الدوال الخطية', 'حل مسائل الدوال التربيعية', 'مراجعة الفصل الأول', 'امتحان تجريبي'];
        $sessions = [];
        foreach ($topics as $idx => $topic) {
            $sessions[] = AcademicSession::create([
                'group_id' => $idx < 2 ? $group1->id : $group2->id,
                'date' => now()->subDays(10 - ($idx * 2))->format('Y-m-d'),
                'topic' => $topic,
                'notes' => 'حصة تدريبية',
                'created_by' => $instructor->id,
            ]);
        }

        // ═══ Center: Attendance ═══
        $statuses = ['present', 'absent', 'late', 'present'];
        foreach ($sessions as $sIdx => $session) {
            $groupStudents = Student::where('group_id', $session->group_id)->get();
            foreach ($groupStudents as $stIdx => $student) {
                Attendance::create([
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => $statuses[($sIdx + $stIdx) % 4],
                    'is_guest' => false,
                ]);
            }
        }

        // ═══ Center: Exams & Grades ═══
        $exam1 = CenterExam::create([
            'name' => 'امتحان الفصل الأول — أولى ثانوي',
            'total_marks' => 30,
            'date' => now()->subDays(5)->format('Y-m-d'),
            'group_id' => $group1->id,
            'created_by' => $instructor->id,
        ]);

        $exam2 = CenterExam::create([
            'name' => 'امتحان تجريبي — تالت ثانوي',
            'total_marks' => 50,
            'date' => now()->subDays(2)->format('Y-m-d'),
            'group_id' => $group2->id,
            'created_by' => $instructor->id,
        ]);

        foreach (Student::where('group_id', $group1->id)->get() as $student) {
            CenterGrade::create([
                'center_exam_id' => $exam1->id,
                'student_id' => $student->id,
                'score' => mt_rand(10, 30),
                'notes' => '',
            ]);
        }

        foreach (Student::where('group_id', $group2->id)->get() as $student) {
            CenterGrade::create([
                'center_exam_id' => $exam2->id,
                'student_id' => $student->id,
                'score' => mt_rand(15, 50),
                'notes' => '',
            ]);
        }

        // ═══ Summary ═══
        $this->command->info('===========================================');
        $this->command->info('  Fake Data Seeded Successfully!');
        $this->command->info('===========================================');
        $this->command->info('  Students:     10 (fake1@example.com — fake9@example.com)');
        $this->command->info('  Password:     password');
        $this->command->info('  Courses:      3 (1 free, 2 paid)');
        $this->command->info('  Sections:     6 | Lectures: 24');
        $this->command->info('  Enrollments:  10');
        $this->command->info('  Groups:       2');
        $this->command->info('  Sessions:     4');
        $this->command->info('  Attendance:   records for all group students');
        $this->command->info('  Exams:        2 with grades');
        $this->command->info('===========================================');
    }
}
