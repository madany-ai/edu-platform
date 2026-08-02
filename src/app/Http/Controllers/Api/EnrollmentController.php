<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentSource;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService
    ) {}

    public function myEnrollments(Request $request): AnonymousResourceCollection
    {
        $enrollments = $this->enrollmentService->getStudentEnrollments($request->user()->id);

        return EnrollmentResource::collection($enrollments);
    }

    public function myEntitlements(Request $request): JsonResponse
    {
        $entitlements = $this->enrollmentService->getStudentEntitlements($request->user()->id);

        return response()->json([
            'status' => 'success',
            'data' => $entitlements
        ]);
    }

    public function enroll(Course $course): JsonResponse
    {
        $user = request()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student || ! $student->is_verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'عفواً، يجب تفعيل حسابك أولاً قبل التسجيل في أي دورة.'
            ], 403);
        }

        $isPublished = $course->status === \App\Enums\CourseStatus::Published || $course->status === 'published';
        if (! $isPublished) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذه الدورة غير متاحة للتسجيل حالياً.'
            ], 403);
        }

        if ((float) $course->price > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذه الدورة مدفوعة، يرجى الشراء عبر إنشاء طلب رسمياً.'
            ], 403);
        }

        $enrollment = $this->enrollmentService->enrollStudent($course, $student);

        return response()->json(new EnrollmentResource($enrollment), 201);
    }

    public function purchase(Course $course): JsonResponse
    {
        return $this->enroll($course);
    }

    public function courseEnrollments(Course $course): AnonymousResourceCollection
    {
        $this->authorize('manageEnrollments', $course);

        $enrollments = $this->enrollmentService->getCourseEnrollments($course);

        return EnrollmentResource::collection($enrollments);
    }

    public function revoke(Course $course, Student $student): JsonResponse
    {
        $this->authorize('manageEnrollments', $course);

        $this->enrollmentService->revokeEnrollment($course, $student);

        return response()->json(['message' => 'تم إلغاء التسجيل بنجاح.']);
    }
}
