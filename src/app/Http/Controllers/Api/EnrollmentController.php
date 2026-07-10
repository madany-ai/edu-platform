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

    public function enroll(Course $course): JsonResponse
    {
        $enrollment = $this->enrollmentService->enrollByUserId($course, request()->user()->id);

        return response()->json(new EnrollmentResource($enrollment), 201);
    }

    public function purchase(Course $course): JsonResponse
    {
        $enrollment = $this->enrollmentService->enrollByUserId(
            $course,
            request()->user()->id,
            EnrollmentSource::Purchase
        );

        return response()->json(new EnrollmentResource($enrollment), 201);
    }

    public function courseEnrollments(Course $course): AnonymousResourceCollection
    {
        $enrollments = $this->enrollmentService->getCourseEnrollments($course);

        return EnrollmentResource::collection($enrollments);
    }

    public function revoke(Course $course, Student $student): JsonResponse
    {
        $this->enrollmentService->revokeEnrollment($course, $student);

        return response()->json(['message' => 'تم إلغاء التسجيل بنجاح.']);
    }
}
