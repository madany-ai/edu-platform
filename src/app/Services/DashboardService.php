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
    public function getInstructorStats(string $instructorId): array
    {
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        $stats = Course::where('instructor_id', $instructorId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(price) as total_revenue
            ")
            ->first();

        $enrollmentStats = Enrollment::whereIn('course_id', $courseIds)
            ->selectRaw("
                COUNT(*) as total_students,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
                SUM(CASE WHEN source = 'manual' AND status = 'active' THEN 1 ELSE 0 END) as pending_enrollments,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent_enrollments
            ", [now()->subDays(7)])
            ->first();

        $totalLectures = DB::table('lectures')
            ->join('course_sections', 'lectures.section_id', '=', 'course_sections.id')
            ->join('courses', 'course_sections.course_id', '=', 'courses.id')
            ->where('courses.instructor_id', $instructorId)
            ->count();

        return [
            'courses' => [
                'total' => (int) ($stats->total ?? 0),
                'published' => (int) ($stats->published ?? 0),
                'draft' => (int) ($stats->draft ?? 0),
            ],
            'students' => [
                'total' => (int) ($enrollmentStats->total_students ?? 0),
                'active' => (int) ($enrollmentStats->active_students ?? 0),
                'recent_enrollments' => (int) ($enrollmentStats->recent_enrollments ?? 0),
            ],
            'revenue' => [
                'total' => (float) ($stats->total_revenue ?? 0),
            ],
            'content' => [
                'total_lectures' => $totalLectures,
            ],
            'pending_enrollments' => (int) ($enrollmentStats->pending_enrollments ?? 0),
        ];
    }

    public function getInstructorCourses(string $instructorId): Collection
    {
        return Course::withCount(['sections', 'enrollments', 'lectures'])
            ->where('instructor_id', $instructorId)
            ->latest()
            ->get();
    }

    public function getInstructorRecentEnrollments(string $instructorId, int $limit = 10): Collection
    {
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        return Enrollment::with(['student.user', 'course'])
            ->whereIn('course_id', $courseIds)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getInstructorCoursePerformance(string $instructorId): Collection
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

    public function getInstructorNotifications(string $instructorId, int $limit = 10): Collection
    {
        return Notification::where('user_id', $instructorId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getStudentStats(string $studentId): array
    {
        $enrollmentsCount = Enrollment::where('student_id', $studentId)->count();
        $activeEnrollments = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')->count();

        // Calculate completed courses dynamically using fast joins
        $courseLecturesCount = DB::table('lectures')
            ->join('course_sections', 'lectures.section_id', '=', 'course_sections.id')
            ->join('enrollments', 'course_sections.course_id', '=', 'enrollments.course_id')
            ->where('enrollments.student_id', $studentId)
            ->select('enrollments.course_id', DB::raw('count(lectures.id) as total_lectures'))
            ->groupBy('enrollments.course_id')
            ->pluck('total_lectures', 'course_id')
            ->toArray();

        $completedLecturesCount = DB::table('student_activities')
            ->join('lectures', function ($join) {
                if (DB::getDriverName() === 'pgsql') {
                    $join->on(DB::raw('CAST(student_activities.entity_id AS uuid)'), '=', 'lectures.id');
                } else {
                    $join->on('student_activities.entity_id', '=', 'lectures.id');
                }
            })
            ->join('course_sections', 'lectures.section_id', '=', 'course_sections.id')
            ->where('student_activities.student_id', $studentId)
            ->where('student_activities.type', 'video_completed')
            ->where('student_activities.entity_type', \App\Models\Lecture::class)
            ->select('course_sections.course_id', DB::raw('count(distinct lectures.id) as completed_lectures'))
            ->groupBy('course_sections.course_id')
            ->pluck('completed_lectures', 'course_id')
            ->toArray();

        $completedCoursesCount = 0;
        foreach ($courseLecturesCount as $courseId => $total) {
            $completed = $completedLecturesCount[$courseId] ?? 0;
            if ($total > 0 && $completed === $total) {
                $completedCoursesCount++;
            }
        }

        $stats = DB::table('student_statistics')
            ->where('student_id', $studentId)
            ->first();

        return [
            'enrollments_count' => $enrollmentsCount,
            'active_enrollments' => $activeEnrollments,
            'completed_lectures' => $stats?->completed_lectures ?? 0,
            'total_watch_minutes' => $stats?->total_watch_minutes ?? 0,
            'average_exam_score' => $stats?->average_exam_score ?? 0,
            'completed_courses' => $completedCoursesCount,
        ];
    }

    public function getStudentRecentEnrollments(string $studentId, int $limit = 5): Collection
    {
        return Enrollment::with(['course.instructor', 'course.sections'])
            ->where('student_id', $studentId)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
