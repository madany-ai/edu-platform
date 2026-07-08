<?php

namespace App\Domain\Course\Controllers;

use App\Domain\Shared\Controllers\Controller;
use App\Domain\Course\Requests\StoreCourseRequest;
use App\Domain\Course\Requests\StoreReviewRequest;
use App\Domain\Course\Resources\CourseResource;
use App\Domain\Course\Resources\EnrollmentResource;
use App\Domain\Course\Services\CourseService;
use App\Domain\Course\Models\Course;
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
        $courses = $this->courseService->listPublished($request->only(['category', 'level', 'search']));

        return CourseResource::collection($courses);
    }

    public function show(string $slug): CourseResource
    {
        $course = $this->courseService->findBySlug($slug);

        if (! $course) {
            abort(404, 'Course not found');
        }

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
        $enrollment = $this->courseService->enrollUser($course, request()->user()->id);

        return response()->json(new EnrollmentResource($enrollment), 201);
    }

    public function myEnrollments(): AnonymousResourceCollection
    {
        $enrollments = $this->courseService->getUserEnrollments(request()->user()->id);

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
