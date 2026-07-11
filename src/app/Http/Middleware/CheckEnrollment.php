<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Entitlement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $lecture = $request->route('lecture');

        if (! $lecture) {
            return $next($request);
        }

        $courseId = $lecture->section->course_id ?? null;

        if (! $courseId) {
            return response()->json(['message' => 'المحاضرة غير موجودة.'], 404);
        }

        $course = Course::find($courseId);

        if ($course && $course->instructor_id === $user->id) {
            return $next($request);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $student = $user->student;

        if (! $student) {
            return response()->json(['message' => 'غير مسجل في هذه الدورة.'], 403);
        }

        $isEnrolled = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        $hasEntitlement = Entitlement::where('student_id', $student->id)
            ->where('lecture_id', $lecture->id)
            ->exists();

        if (! $isEnrolled && ! $hasEntitlement) {
            return response()->json(['message' => 'غير مسجل في هذه الدورة أو المحاضرة.'], 403);
        }

        return $next($request);
    }
}
