<?php

namespace App\Domain\Dashboard\Controllers;

use App\Domain\Shared\Controllers\Controller;
use App\Domain\Course\Models\Enrollment;
use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseReview;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function student(): JsonResponse
    {
        $userId = request()->user()->id;

        $enrollments = Enrollment::where('user_id', $userId)->count();
        $completed = Enrollment::where('user_id', $userId)->where('progress', 100)->count();
        $totalMinutes = Enrollment::where('user_id', $userId)
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->sum('courses.duration_minutes');
        $certificates = Enrollment::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->count();

        return response()->json([
            'enrollments_count' => $enrollments,
            'completed_lessons_count' => $completed,
            'total_learning_minutes' => $totalMinutes,
            'certificates_count' => $certificates,
        ]);
    }

    public function instructor(): JsonResponse
    {
        $userId = request()->user()->id;

        $coursesCount = Course::where('instructor_id', $userId)->count();
        $totalStudents = Enrollment::whereIn('course_id', function ($q) use ($userId) {
            $q->select('id')->from('courses')->where('instructor_id', $userId);
        })->count();
        $totalRevenue = Course::where('instructor_id', $userId)->sum('price');
        $avgRating = CourseReview::whereIn('course_id', function ($q) use ($userId) {
            $q->select('id')->from('courses')->where('instructor_id', $userId);
        })->avg('rating');

        return response()->json([
            'courses_count' => $coursesCount,
            'total_students' => $totalStudents,
            'total_revenue' => (float) $totalRevenue,
            'average_rating' => round($avgRating ?? 0, 1),
        ]);
    }
}
