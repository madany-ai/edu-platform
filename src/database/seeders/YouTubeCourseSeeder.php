<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\LectureVideo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class YouTubeCourseSeeder extends Seeder
{
    public function run(): void
    {
        // Get an instructor
        $instructor = User::whereHas('roles', fn ($q) => $q->where('name', 'instructor'))->first();
        
        if (!$instructor) {
            $instructor = User::factory()->create([
                'name' => 'Instructor Ahmed',
                'email' => 'instructor@example.com',
                'status' => 'active',
            ]);
            $instructor->assignRole('instructor');
        }

        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'كورس برمجة الويب الشامل (يوتيوب)',
            'description' => 'كورس تجريبي باستخدام فيديوهات يوتيوب لاختبار أداء المنصة.',
            'price' => 0,
            'status' => 'published',
            'thumbnail' => null,
        ]);

        $section1 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'القسم الأول: مقدمة للبرمجة',
            'sort_order' => 1,
        ]);

        $section2 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'القسم الثاني: التطبيق العملي',
            'sort_order' => 2,
        ]);

        $youtubeLinks = [
            'https://youtu.be/hRgqW70csbI?si=zwawOhxXoiZ-abXa',
            'https://youtu.be/01qrNq901iY?si=4YoNr1bZC_k4E1B9',
            'https://youtu.be/YZkiE9lkOiw?si=nrsfBN1r7PTQ5frn',
            'https://youtu.be/UaQWod-d4ts?si=ogtP1x6HviX-ZAWg',
        ];

        $lectureTitles = [
            'الدرس الأول: كيف تعمل الويب؟',
            'الدرس الثاني: أساسيات لغات البرمجة',
            'الدرس الثالث: إعداد بيئة العمل',
            'الدرس الرابع: مشروع عملي متكامل',
        ];

        foreach ($youtubeLinks as $index => $link) {
            $lecture = Lecture::create([
                'section_id' => $index < 2 ? $section1->id : $section2->id,
                'title' => $lectureTitles[$index],
                'description' => 'شرح تفصيلي للدرس',
                'duration' => 10, // 10 minutes default
                'sort_order' => $index + 1,
            ]);

            LectureVideo::create([
                'lecture_id' => $lecture->id,
                'video_path' => $link,
                'status' => 'completed',
                'bunny_video_id' => 'youtube',
                'duration' => 600, // 600 seconds
            ]);
        }
        
        // Enroll current student if there is one
        $student = User::whereHas('roles', fn ($q) => $q->where('name', 'student'))->first();
        if ($student && $student->student) {
            \App\Models\Enrollment::firstOrCreate([
                'student_id' => $student->student->id,
                'course_id' => $course->id,
            ], [
                'status' => 'active',
                'source' => 'manual',
            ]);
        }
    }
}
