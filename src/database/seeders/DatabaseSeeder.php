<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        Role::firstOrCreate(['name' => 'instructor']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'assistant']);

        // Create instructor
        $instructor = User::create([
            'name' => 'المدرس الرئيسي',
            'email' => 'instructor@lms.local',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        // Create sample students
        $students = [];
        for ($i = 1; $i <= 3; $i++) {
            $student = User::create([
                'name' => "طالب تجريبي {$i}",
                'email' => "student{$i}@lms.local",
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $student->assignRole('student');

            $students[] = Student::create([
                'user_id' => $student->id,
                'first_name' => 'طالب',
                'last_name' => "تجريبي {$i}",
                'gender' => 'male',
            ]);
        }

        // Create sample course
        $course = Course::create([
            'title' => 'دورة تعلم البرمجة',
            'description' => 'دورة شاملة لتعلم أساسيات البرمجة من الصفر إلى الاحتراف. تغطي لغات متعددة ومفاهيم أساسية.',
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $instructor->id,
        ]);

        // Create sections
        $section1 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'مقدمة في البرمجة',
            'sort_order' => 1,
        ]);

        $section2 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'أساسيات PHP',
            'sort_order' => 2,
        ]);

        // Create lectures for section 1
        Lecture::create([
            'section_id' => $section1->id,
            'title' => 'ما هي البرمجة؟',
            'description' => 'في هذه المحاضرة سنتعلم ما هي البرمجة ولماذا هي مهمة',
            'duration' => 15,
            'sort_order' => 1,
        ]);

        Lecture::create([
            'section_id' => $section1->id,
            'title' => 'إعداد بيئة العمل',
            'description' => 'كيفية تثبيت البرامج اللازمة للبدء في البرمجة',
            'duration' => 20,
            'sort_order' => 2,
        ]);

        // Create lectures for section 2
        Lecture::create([
            'section_id' => $section2->id,
            'title' => 'متغيرات PHP',
            'description' => 'شرح المتغيرات وأنواع البيانات في PHP',
            'duration' => 25,
            'sort_order' => 1,
        ]);

        Lecture::create([
            'section_id' => $section2->id,
            'title' => 'الشروط والحلقات',
            'description' => 'تعلم كيفية استخدام if/else والحلقات التكرارية',
            'duration' => 30,
            'sort_order' => 2,
        ]);

        // Create a paid course
        $paidCourse = Course::create([
            'title' => 'دورة تقدم الويب المتقدمة',
            'description' => 'دورة متقدمة في تطوير الويب باستخدام Laravel و React',
            'price' => 199.99,
            'status' => 'published',
            'instructor_id' => $instructor->id,
        ]);

        $paidSection = CourseSection::create([
            'course_id' => $paidCourse->id,
            'title' => 'أساسيات Laravel',
            'sort_order' => 1,
        ]);

        Lecture::create([
            'section_id' => $paidSection->id,
            'title' => 'مقدمة في Laravel',
            'description' => 'تعريف بإطار العمل Laravel وميزاته',
            'duration' => 20,
            'sort_order' => 1,
        ]);

        // Enroll first student in free course
        if (isset($students[0])) {
            \App\Models\Enrollment::create([
                'student_id' => $students[0]->id,
                'course_id' => $course->id,
                'status' => 'active',
                'source' => 'manual',
                'started_at' => now(),
            ]);
        }
    }
}
