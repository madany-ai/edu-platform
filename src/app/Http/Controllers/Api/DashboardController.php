<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function student(): JsonResponse
    {
        $user = request()->user();
        $student = Student::where('user_id', $user->id)->first();

        $enrollmentsCount = 0;
        if ($student) {
            $enrollmentsCount = Enrollment::where('student_id', $student->id)->count();
        }

        return response()->json([
            'enrollments_count' => $enrollmentsCount,
            'completed_lessons_count' => 0,
            'total_learning_minutes' => 0,
            'certificates_count' => 0,
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

        return response()->json([
            'courses_count' => $coursesCount,
            'total_students' => $totalStudents,
            'total_revenue' => (float) $totalRevenue,
        ]);
    }

    public function instructorStudents(): JsonResponse
    {
        $userId = request()->user()->id;
        $courseIds = \App\Models\Course::where('instructor_id', $userId)->pluck('id');

        $students = Student::whereHas('enrollments', function ($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })->with('user')->get();

        return response()->json($students);
    }
}
