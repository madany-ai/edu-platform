<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getInstructorStats(int $instructorId): array
    {
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        $coursesCount = Course::where('instructor_id', $instructorId)->count();
        $publishedCoursesCount = Course::where('instructor_id', $instructorId)
            ->where('status', 'published')->count();
        $draftCoursesCount = Course::where('instructor_id', $instructorId)
            ->where('status', 'draft')->count();

        $totalStudents = Enrollment::whereIn('course_id', $courseIds)->count();
        $activeStudents = Enrollment::whereIn('course_id', $courseIds)
            ->where('status', 'active')->count();

        $totalRevenue = Course::where('instructor_id', $instructorId)->sum('price');

        $totalLectures = Course::where('instructor_id', $instructorId)
            ->withCount('lectures')
            ->get()
            ->sum('lectures_count');

        $pendingEnrollments = Enrollment::whereIn('course_id', $courseIds)
            ->where('source', 'manual')
            ->where('status', 'active')
            ->count();

        $recentEnrollmentsCount = Enrollment::whereIn('course_id', $courseIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'courses' => [
                'total' => $coursesCount,
                'published' => $publishedCoursesCount,
                'draft' => $draftCoursesCount,
            ],
            'students' => [
                'total' => $totalStudents,
                'active' => $activeStudents,
                'recent_enrollments' => $recentEnrollmentsCount,
            ],
            'revenue' => [
                'total' => (float) $totalRevenue,
            ],
            'content' => [
                'total_lectures' => $totalLectures,
            ],
            'pending_enrollments' => $pendingEnrollments,
        ];
    }

    public function getInstructorCourses(int $instructorId): Collection
    {
        return Course::withCount(['sections', 'enrollments', 'lectures'])
            ->where('instructor_id', $instructorId)
            ->latest()
            ->get();
    }

    public function getInstructorRecentEnrollments(int $instructorId, int $limit = 10): Collection
    {
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        return Enrollment::with(['student.user', 'course'])
            ->whereIn('course_id', $courseIds)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getInstructorCoursePerformance(int $instructorId): Collection
    {
        return Course::withCount(['enrollments', 'lectures'])
            ->where('instructor_id', $instructorId)
            ->where('status', 'published')
            ->orderByDesc('enrollments_count')
            ->limit(5)
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'status' => $course->status,
                'price' => (float) $course->price,
                'enrollments_count' => $course->enrollments_count,
                'lectures_count' => $course->lectures_count,
                'sections_count' => $course->sections_count,
            ]);
    }

    public function getInstructorNotifications(int $instructorId, int $limit = 10): Collection
    {
        return Notification::where('user_id', $instructorId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getStudentStats(int $studentId): array
    {
        $enrollmentsCount = Enrollment::where('student_id', $studentId)->count();
        $activeEnrollments = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')->count();

        $stats = DB::table('student_statistics')
            ->where('student_id', $studentId)
            ->first();

        return [
            'enrollments_count' => $enrollmentsCount,
            'active_enrollments' => $activeEnrollments,
            'completed_lectures' => $stats?->completed_lectures ?? 0,
            'total_watch_minutes' => $stats?->total_watch_minutes ?? 0,
            'average_exam_score' => $stats?->average_exam_score ?? 0,
            'completed_courses' => $stats?->completed_courses ?? 0,
        ];
    }

    public function getStudentRecentEnrollments(int $studentId, int $limit = 5): Collection
    {
        return Enrollment::with(['course.instructor', 'course.sections'])
            ->where('student_id', $studentId)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
