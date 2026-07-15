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

        // Replace relative segment paths with backend-proxied URLs (avoids CORS issues with MinIO)
        $segmentBaseUrl = url('/api/lectures/' . $lecture->id . '/segment/');
        $modifiedPlaylist = str_replace('segment_', $segmentBaseUrl . 'segment_', $modifiedPlaylist);
        // Append token query param to each segment URL (they appear at end of line in m3u8)
        $modifiedPlaylist = preg_replace('/(segment_\S+\.ts)(\s|$)/', '$1?token=' . rawurlencode($token) . '$2', $modifiedPlaylist);

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

    public function streamSegment(Request $request, \App\Models\Lecture $lecture, string $segment, \App\Services\VideoAccessService $accessService)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['message' => 'Missing token'], 400);
        }

        if (!$accessService->validateToken($token, $lecture, $request->ip())) {
            return response()->json(['message' => 'Invalid or expired token'], 403);
        }

        $video = $lecture->video;
        if (!$video || !$video->video_path) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        // Sanitize segment filename to prevent path traversal
        $segment = basename($segment);
        if (!preg_match('/^segment_\d+\.ts$/', $segment)) {
            return response()->json(['message' => 'Invalid segment filename'], 400);
        }

        $segmentPath = 'hls/' . $lecture->id . '/' . $segment;

        if (!\Illuminate\Support\Facades\Storage::disk('minio')->exists($segmentPath)) {
            return response()->json(['message' => 'Segment not found'], 404);
        }

        $content = \Illuminate\Support\Facades\Storage::disk('minio')->get($segmentPath);

        return response($content)
            ->header('Content-Type', 'video/mp2t')
            ->header('Cache-Control', 'public, max-age=86400');
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
