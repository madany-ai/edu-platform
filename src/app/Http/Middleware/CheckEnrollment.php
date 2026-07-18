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

        $lecture->loadMissing('section.course');
        $course = $lecture->section->course ?? null;

        if (! $course) {
            return response()->json(['message' => 'المحاضرة غير موجودة.'], 404);
        }

        $courseId = $course->id;

        if ($course->instructor_id === $user->id) {
            return $next($request);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if ($user->hasRole('assistant')) {
            $isAssigned = \App\Models\CourseAssistant::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();

            if ($isAssigned) {
                return $next($request);
            }
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
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (! $isEnrolled && ! $hasEntitlement) {
            return response()->json(['message' => 'غير مسجل في هذه الدورة أو المحاضرة.'], 403);
        }

        // Check if blocked by exam in preceding lectures
        $videoAccessService = app(\App\Services\VideoAccessService::class);
        if ($videoAccessService->isBlockedByExam($user, $lecture, 'lecture_access')) {
            return response()->json(['message' => 'هذه المحاضرة مغلقة حتى تجتاز الاختبار المطلوب أولاً.'], 403);
        }

        return $next($request);
    }
}
