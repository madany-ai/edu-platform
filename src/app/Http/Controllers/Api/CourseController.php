<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\EnrollmentResource;
use App\Services\CourseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courseService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->listPublished($request->only(['category', 'search']));

        return CourseResource::collection($courses);
    }

    public function show(Course $course): CourseResource
    {
        $course->load(['category', 'instructor', 'sections.lectures'])
            ->loadCount(['sections', 'enrollments']);

        return new CourseResource($course);
    }

    public function store(StoreCourseRequest $request): CourseResource
    {
        $course = $this->courseService->create([
            ...$request->validated(),
            'instructor_id' => $request->user()->id,
        ]);

        return new CourseResource($course);
    }

    public function update(StoreCourseRequest $request, Course $course): CourseResource
    {
        $course = $this->courseService->update($course, $request->validated());

        return new CourseResource($course);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->courseService->delete($course);

        return response()->json(['message' => 'Course deleted']);
    }

    public function enroll(Course $course): JsonResponse
    {
        $enrollment = $this->courseService->enrollStudent($course, request()->user());

        return response()->json(new EnrollmentResource($enrollment), 201);
    }

    public function myEnrollments(): AnonymousResourceCollection
    {
        $enrollments = $this->courseService->getUserEnrollments(request()->user());

        return EnrollmentResource::collection($enrollments);
    }

    public function review(StoreReviewRequest $request, Course $course): JsonResponse
    {
        $review = $this->courseService->createReview(
            $course,
            $request->user()->id,
            $request->input('rating'),
            $request->input('review')
        );

        return response()->json($review, 201);
    }

    public function instructorCourses(): AnonymousResourceCollection
    {
        $courses = $this->courseService->getInstructorCourses(request()->user()->id);

        return CourseResource::collection($courses);
    }
}
