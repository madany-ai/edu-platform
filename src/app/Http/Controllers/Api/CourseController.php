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
        private readonly CourseService $courseService,
        private readonly \App\Services\ProgressService $progressService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->listPublished($request->only(['search']));

        return CourseResource::collection($courses);
    }

    public function show(Course $course): CourseResource
    {
        $course->load(['instructor', 'sections.lectures.video', 'sections.lectures.exams', 'sections.lectures.assignments'])
            ->loadCount(['sections', 'enrollments']);

        $user = auth('sanctum')->user();
        if ($user) {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $lectureIds = $course->sections->flatMap(fn($s) => $s->lectures->pluck('id'))->toArray();
                $progressMap = \App\Models\StudentActivity::where('student_id', $student->id)
                    ->where('type', 'video_progress')
                    ->where('entity_type', \App\Models\Lecture::class)
                    ->whereIn('entity_id', $lectureIds)
                    ->get()
                    ->keyBy('entity_id')
                    ->map(fn($a) => $a->metadata)
                    ->toArray();

                $course->setAttribute('progress_map', $progressMap);
            }
        }

        return new CourseResource($course);
    }

    public function store(StoreCourseRequest $request): CourseResource
    {
        $this->authorize('create', Course::class);

        $course = $this->courseService->create([
            ...$request->validated(),
            'instructor_id' => $request->user()->id,
        ]);

        return new CourseResource($course);
    }

    public function update(StoreCourseRequest $request, Course $course): CourseResource
    {
        $this->authorize('update', $course);

        $course = $this->courseService->update($course, $request->validated());

        return new CourseResource($course);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $this->courseService->delete($course);

        return response()->json(['message' => 'Course deleted']);
    }

    public function showLecture(\App\Models\Lecture $lecture): \App\Http\Resources\LectureResource
    {
        $lecture->load(['video', 'files', 'section.course', 'exams', 'assignments']);

        $user = auth('sanctum')->user();
        if ($user) {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $progress = \App\Models\StudentActivity::where('student_id', $student->id)
                    ->where('type', 'video_progress')
                    ->where('entity_type', \App\Models\Lecture::class)
                    ->where('entity_id', $lecture->id)
                    ->first();
                if ($progress) {
                    $lecture->setAttribute('progress', $progress->metadata);
                }
            }
        }

        return new \App\Http\Resources\LectureResource($lecture);
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
            'youtube_url' => 'nullable|url',
        ]);

        $lecture = $section->lectures()->create(\Illuminate\Support\Arr::except($validated, ['youtube_url']));

        if (!empty($validated['youtube_url'])) {
            $lecture->video()->create([
                'video_path' => $validated['youtube_url'],
                'status' => 'completed',
                'bunny_video_id' => 'youtube',
                'duration' => $validated['duration'] ?? 0,
            ]);
        }

        return response()->json($lecture->load('video'), 201);
    }

    public function updateLecture(Request $request, \App\Models\CourseSection $section, \App\Models\Lecture $lecture): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'youtube_url' => 'nullable|url',
        ]);

        $lecture->update(\Illuminate\Support\Arr::except($validated, ['youtube_url']));

        if (!empty($validated['youtube_url'])) {
            $lecture->video()->updateOrCreate(
                ['lecture_id' => $lecture->id],
                [
                    'video_path' => $validated['youtube_url'],
                    'status' => 'completed',
                    'bunny_video_id' => 'youtube',
                    'duration' => $validated['duration'] ?? 0,
                ]
            );
        }

        return response()->json($lecture->load('video'));
    }

    public function destroyLecture(\App\Models\CourseSection $section, \App\Models\Lecture $lecture): JsonResponse
    {
        $lecture->delete();

        return response()->json(['message' => 'تم حذف المحاضرة بنجاح.']);
    }

    public function downloadFile(Request $request, \App\Models\Lecture $lecture, \App\Models\LectureFile $file)
    {
        if ($file->lecture_id !== $lecture->id) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $storagePath = $file->getAttributes()['file_path'];

        if (!\Illuminate\Support\Facades\Storage::disk('minio')->exists($storagePath)) {
            return response()->json(['message' => 'File not found on storage.'], 404);
        }

        $mimeType = \Illuminate\Support\Facades\Storage::disk('minio')->mimeType($storagePath);
        $filename = basename($storagePath);
        $stream = \Illuminate\Support\Facades\Storage::disk('minio')->readStream($storagePath);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    public function updateProgress(Request $request, \App\Models\Lecture $lecture): JsonResponse
    {
        $validated = $request->validate([
            'current_time' => 'required|numeric|min:0',
            'is_completed' => 'required|boolean',
        ]);

        $user = $request->user();
        $student = \App\Models\Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $result = $this->progressService->updateLectureProgress($student, $lecture, $validated);

        return response()->json([
            'message' => 'Progress updated successfully.',
            'progress' => $result['progress'],
        ]);
    }
}
