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

    public function showLecture(\App\Models\Lecture $lecture): JsonResponse
    {
        $lecture->load(['video', 'files', 'section.course']);

        $progress = null;
        $user = request()->user();
        if ($user) {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $activity = \App\Models\StudentActivity::where('student_id', $student->id)
                    ->where('type', 'video_progress')
                    ->where('entity_type', \App\Models\Lecture::class)
                    ->where('entity_id', $lecture->id)
                    ->first();
                
                if ($activity) {
                    $progress = $activity->metadata;
                }
            }
        }

        $responseData = $lecture->toArray();
        $responseData['progress'] = $progress;

        if ($lecture->video && $lecture->video->status === 'completed' && $lecture->video->video_path) {
            if (str_ends_with(strtolower($lecture->video->video_path), '.mp4')) {
                $responseData['video']['stream_url'] = \Illuminate\Support\Facades\Storage::disk('minio')
                    ->temporaryUrl($lecture->video->video_path, now()->addHours(2));
                $responseData['video']['stream_type'] = 'video/mp4';
            } else {
                $responseData['video']['stream_url'] = route('lectures.stream', ['lecture' => $lecture->id]);
                $responseData['video']['stream_type'] = 'application/x-mpegURL';
            }
        }

        return response()->json($responseData);
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

    public function streamLecture(Request $request, \App\Models\Lecture $lecture, \App\Services\VideoAccessService $accessService)
    {
        if (!$accessService->canAccess($request->user(), $lecture)) {
            return response()->json(['message' => 'غير مصرح لك بمشاهدة هذا الفيديو.'], 403);
        }

        $video = $lecture->video;
        if (!$video || $video->status !== 'completed' || !$video->video_path) {
            return response()->json(['message' => 'الفيديو غير متوفر أو لم يتم معالجته بعد.'], 404);
        }

        // Fetch .m3u8 content
        if (!\Illuminate\Support\Facades\Storage::disk('minio')->exists($video->video_path)) {
            return response()->json(['message' => 'ملف التشغيل غير موجود.'], 404);
        }

        $playlist = \Illuminate\Support\Facades\Storage::disk('minio')->get($video->video_path);

        // Generate temporary token bound to user, IP, and lecture
        $token = $accessService->generateSignedToken($request->user(), $lecture, $request->ip());

        // Replace key URI with our dynamic secure route
        // Original: #EXT-X-KEY:METHOD=AES-128,URI="key.key"
        // Target: #EXT-X-KEY:METHOD=AES-128,URI="https://yourdomain/api/lectures/{lecture}/key?token={token}"
        $keyUrl = route('lectures.key', ['lecture' => $lecture->id, 'token' => $token]);
        $modifiedPlaylist = str_replace('URI="key.key"', 'URI="' . $keyUrl . '"', $playlist);

        // Replace relative segment paths with absolute MinIO URLs
        $minioBaseUrl = rtrim(\Illuminate\Support\Facades\Storage::disk('minio')->url("hls/{$lecture->id}"), '/') . '/';
        $modifiedPlaylist = str_replace('segment_', $minioBaseUrl . 'segment_', $modifiedPlaylist);

        return response($modifiedPlaylist)
            ->header('Content-Type', 'application/x-mpegURL')
            ->header('Cache-Control', 'no-cache, private');
    }

    public function streamKey(Request $request, \App\Models\Lecture $lecture, \App\Services\VideoAccessService $accessService)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['message' => 'Missing token'], 400);
        }

        if (!$accessService->validateToken($token, $lecture, $request->ip())) {
            return response()->json(['message' => 'Invalid or expired token'], 403);
        }

        $video = $lecture->video;
        if (!$video || !$video->encryption_key) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        // Return the binary raw key (16 bytes)
        $rawKey = hex2bin($video->encryption_key);

        return response($rawKey)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Cache-Control', 'no-cache, private');
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

        $activity = \App\Models\StudentActivity::updateOrCreate(
            [
                'student_id' => $student->id,
                'type' => 'video_progress',
                'entity_type' => \App\Models\Lecture::class,
                'entity_id' => $lecture->id,
            ],
            [
                'metadata' => [
                    'current_time' => $validated['current_time'],
                    'is_completed' => $validated['is_completed'],
                ]
            ]
        );

        $stats = \App\Models\StudentStatistic::firstOrCreate(['student_id' => $student->id]);
        $stats->total_watch_minutes = ($stats->total_watch_minutes ?? 0) + (20 / 60);

        $wasCompleted = \App\Models\StudentActivity::where('student_id', $student->id)
            ->where('type', 'video_completed')
            ->where('entity_type', \App\Models\Lecture::class)
            ->where('entity_id', $lecture->id)
            ->exists();

        if ($validated['is_completed'] && !$wasCompleted) {
            \App\Models\StudentActivity::create([
                'student_id' => $student->id,
                'type' => 'video_completed',
                'entity_type' => \App\Models\Lecture::class,
                'entity_id' => $lecture->id,
                'metadata' => ['completed_at' => now()->toDateTimeString()],
            ]);
            $stats->completed_lectures = ($stats->completed_lectures ?? 0) + 1;
        }

        $stats->last_activity_at = now();
        $stats->save();

        return response()->json([
            'message' => 'Progress updated successfully.',
            'progress' => $activity->metadata,
        ]);
    }
}
