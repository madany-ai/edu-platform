<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\EnrollmentResource;
use App\Models\Student;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function instructor(): JsonResponse
    {
        $userId = request()->user()->id;

        $stats = $this->dashboardService->getInstructorStats($userId);

        return response()->json($stats);
    }

    public function instructorCourses(): AnonymousResourceCollection
    {
        $userId = request()->user()->id;

        $courses = $this->dashboardService->getInstructorCourses($userId);

        return CourseResource::collection($courses);
    }

    public function instructorRecentEnrollments(): AnonymousResourceCollection
    {
        $userId = request()->user()->id;

        $enrollments = $this->dashboardService->getInstructorRecentEnrollments($userId);

        return EnrollmentResource::collection($enrollments);
    }

    public function instructorCoursePerformance(): JsonResponse
    {
        $userId = request()->user()->id;

        $performance = $this->dashboardService->getInstructorCoursePerformance($userId);

        return response()->json($performance);
    }

    public function instructorNotifications(): JsonResponse
    {
        $userId = request()->user()->id;

        $notifications = $this->dashboardService->getInstructorNotifications($userId);

        return response()->json($notifications);
    }

    public function myNotifications(): JsonResponse
    {
        $userId = request()->user()->id;

        $notifications = \App\Models\Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function instructorStudents(): AnonymousResourceCollection
    {
        $userId = request()->user()->id;
        $courseIds = \App\Models\Course::where('instructor_id', $userId)->pluck('id');

        $students = Student::whereHas('enrollments', function ($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })->with('user')->paginate(20);

        return \App\Http\Resources\InstructorStudentResource::collection($students);
    }

    public function student(): JsonResponse
    {
        $user = request()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student) {
            return response()->json([
                'enrollments_count' => 0,
                'active_enrollments' => 0,
                'completed_lectures' => 0,
                'total_watch_minutes' => 0,
            ]);
        }

        $stats = $this->dashboardService->getStudentStats($student->id);

        return response()->json($stats);
    }
}
