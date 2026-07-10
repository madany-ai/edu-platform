<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Resources\CourseResource;
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
        $courses = $this->courseService->listPublished($request->only(['search']));

        return CourseResource::collection($courses);
    }

    public function show(Course $course): CourseResource
    {
        $course->load(['instructor', 'sections.lectures'])
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

    public function instructorCourses(): AnonymousResourceCollection
    {
        $courses = $this->courseService->getInstructorCourses(request()->user()->id);

        return CourseResource::collection($courses);
    }

    public function storeSection(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $section = $course->sections()->create($validated);

        return response()->json($section, 201);
    }

    public function updateSection(Request $request, Course $course, \App\Models\CourseSection $section): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $section->update($validated);

        return response()->json($section);
    }

    public function destroySection(Course $course, \App\Models\CourseSection $section): JsonResponse
    {
        $section->delete();

        return response()->json(['message' => 'تم حذف القسم بنجاح.']);
    }

    public function storeLecture(Request $request, \App\Models\CourseSection $section): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        $lecture = $section->lectures()->create($validated);

        return response()->json($lecture, 201);
    }

    public function updateLecture(Request $request, \App\Models\CourseSection $section, \App\Models\Lecture $lecture): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        $lecture->update($validated);

        return response()->json($lecture);
    }

    public function destroyLecture(\App\Models\CourseSection $section, \App\Models\Lecture $lecture): JsonResponse
    {
        $lecture->delete();

        return response()->json(['message' => 'تم حذف المحاضرة بنجاح.']);
    }
}
