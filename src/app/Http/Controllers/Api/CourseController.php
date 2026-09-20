<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveLectureRequest;
use App\Http\Requests\SaveSectionRequest;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateProgressRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LectureResource;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\LectureFile;
use App\Models\Scopes\AcademicYearScope;
use App\Models\Student;
use App\Models\StudentActivity;
use App\Services\CourseService;
use App\Services\ProgressService;
use App\Services\VideoAccessService;
use App\Traits\ResolvesStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    use ResolvesStudent;

    public function __construct(
        private readonly CourseService $courseService,
        private readonly ProgressService $progressService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->listPublished($request->only(['search']));

        return CourseResource::collection($courses);
    }

    public function show(string $course): CourseResource
    {
        $course = Course::withoutGlobalScope(AcademicYearScope::class)->findOrFail($course);

        $user = auth('sanctum')->user();
        $isPublished = $course->status === CourseStatus::Published || $course->status === 'published';

        if (! $isPublished) {
            if (! $user || (! $user->hasRole('super_admin') && ! $user->hasRole('admin') && $user->id !== $course->instructor_id)) {
                abort(404, 'الدورة غير موجودة أو غير منشورة.');
            }
        }

        $course->load(['instructor', 'sections.lectures.video', 'sections.lectures.exams', 'sections.lectures.assignments'])
            ->loadCount(['sections', 'enrollments']);

        $user = auth('sanctum')->user();
        if ($user) {
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $lectureIds = $course->sections->flatMap(fn ($s) => $s->lectures->pluck('id'))->toArray();
                $progressMap = StudentActivity::where('student_id', $student->id)
                    ->where('type', 'video_progress')
                    ->where('entity_type', Lecture::class)
                    ->whereIn('entity_id', $lectureIds)
                    ->get()
                    ->keyBy('entity_id')
                    ->map(fn ($a) => $a->metadata)
                    ->toArray();

                $course->setAttribute('progress_map', $progressMap);

                $examIds = $course->sections->flatMap(fn ($s) => $s->lectures->flatMap(fn ($l) => $l->exams->pluck('id')->concat($l->assignments->pluck('id'))))->toArray();
                $allAttempts = ExamAttempt::where('student_id', $student->id)
                    ->whereIn('exam_id', $examIds)
                    ->whereNotNull('submitted_at')
                    ->get()
                    ->groupBy('exam_id');

                $attemptsMap = $allAttempts->map(fn ($group) => $group->sortByDesc('submitted_at')->first())->all();
                $attemptsCountMap = $allAttempts->map(fn ($group) => $group->count())->all();

                $course->setAttribute('attempts_map', $attemptsMap);
                $course->setAttribute('attempts_count_map', $attemptsCountMap);

                $course->setAttribute('attempts_map', $attemptsMap);
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

    public function showLecture(Lecture $lecture): LectureResource
    {
        $lecture->load(['video', 'files', 'section.course', 'exams', 'assignments']);

        $user = auth('sanctum')->user();
        if ($user) {
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $progress = StudentActivity::where('student_id', $student->id)
                    ->where('type', 'video_progress')
                    ->where('entity_type', Lecture::class)
                    ->where('entity_id', $lecture->id)
                    ->first();
                if ($progress) {
                    $lecture->setAttribute('progress', $progress->metadata);
                }
            }
        }

        $resource = new LectureResource($lecture);
        if ($student) {
            $resource->setStudent($student);
        }

        return $resource;
    }

    public function instructorCourses(): AnonymousResourceCollection
    {
        $courses = $this->courseService->getInstructorCourses(request()->user()->id);

        return CourseResource::collection($courses);
    }

    public function storeSection(SaveSectionRequest $request, Course $course): JsonResponse
    {
        $this->authorize('create', [CourseSection::class, $course]);

        $validated = $request->validated();

        $section = $course->sections()->create($validated);

        return response()->json($section, 201);
    }

    public function updateSection(SaveSectionRequest $request, Course $course, CourseSection $section): JsonResponse
    {
        if ($section->course_id !== $course->id) {
            abort(404, 'القسم غير موجود في هذا الكورس.');
        }

        $this->authorize('update', $section);

        $validated = $request->validated();

        $section->update($validated);

        return response()->json($section);
    }

    public function destroySection(Course $course, CourseSection $section): JsonResponse
    {
        if ($section->course_id !== $course->id) {
            abort(404, 'القسم غير موجود في هذا الكورس.');
        }

        $this->authorize('delete', $section);

        $section->delete();

        return response()->json(['message' => 'تم حذف القسم بنجاح.']);
    }

    public function storeLecture(SaveLectureRequest $request, CourseSection $section): JsonResponse
    {
        $this->authorize('create', [Lecture::class, $section]);

        $validated = $request->validated();

        $lecture = $section->lectures()->create(Arr::except($validated, ['youtube_url']));

        if (! empty($validated['youtube_url'])) {
            $lecture->video()->create([
                'video_path' => $validated['youtube_url'],
                'status' => 'completed',
                'bunny_video_id' => 'youtube',
                'duration' => $validated['duration'] ?? 0,
            ]);
        }

        return response()->json($lecture->load('video'), 201);
    }

    public function updateLecture(SaveLectureRequest $request, CourseSection $section, Lecture $lecture): JsonResponse
    {
        if ($lecture->section_id !== $section->id) {
            abort(404, 'المحاضرة غير موجودة في هذا القسم.');
        }

        $this->authorize('update', $lecture);

        $validated = $request->validated();

        $lecture->update(Arr::except($validated, ['youtube_url']));

        if (! empty($validated['youtube_url'])) {
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

    public function destroyLecture(CourseSection $section, Lecture $lecture): JsonResponse
    {
        if ($lecture->section_id !== $section->id) {
            abort(404, 'المحاضرة غير موجودة في هذا القسم.');
        }

        $this->authorize('delete', $lecture);

        $lecture->delete();

        return response()->json(['message' => 'تم حذف المحاضرة بنجاح.']);
    }

    public function downloadFile(Request $request, Lecture $lecture, LectureFile $file)
    {
        if ($file->lecture_id !== $lecture->id) {
            return response()->json(['message' => 'الملف غير موجود.'], 404);
        }

        $storagePath = $file->getAttributes()['file_path'];

        $extension = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'jpg', 'jpeg', 'png'];

        if (! in_array($extension, $allowedExtensions, true)) {
            return response()->json(['message' => 'نوع الملف غير مسموح بتحميله.'], 403);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($storagePath)) {
            return response()->json(['message' => 'الملف غير موجود على السيرفر.'], 404);
        }

        $mimeType = 'application/octet-stream';
        $filename = basename($storagePath);
        $stream = $disk->readStream($storagePath);

        return response()->stream(function () use ($stream) {
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function updateProgress(UpdateProgressRequest $request, Lecture $lecture): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $student = $this->resolveStudent($user);

        if (! $student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $result = $this->progressService->updateLectureProgress($student, $lecture, $validated);

        return response()->json([
            'message' => 'Progress updated successfully.',
            'progress' => $result['progress'],
        ]);
    }

    public function streamKey(Request $request, Lecture $lecture)
    {
        $token = $request->query('token');

        if (! $token) {
            return response()->json(['message' => 'Missing token'], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $accessService = app(VideoAccessService::class);

        if (! $accessService->canAccess($user, $lecture)) {
            return response()->json(['message' => 'Unauthorized access to lecture'], 403);
        }

        if (! $accessService->validateToken($token, $lecture, $request->ip())) {
            return response()->json(['message' => 'Invalid or expired token'], 403);
        }

        $video = $lecture->video;
        if (! $video || ! $video->encryption_key) {
            abort(404, 'Video key not found.');
        }

        $binaryKey = hex2bin($video->encryption_key);

        return response($binaryKey, 200)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Cache-Control', 'no-cache, private');
    }

    public function standaloneIndex(Request $request): AnonymousResourceCollection
    {
        $lectures = Lecture::query()
            ->standalone()
            ->where('status', 'published')
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->with(['video', 'instructor', 'products'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return LectureResource::collection($lectures);
    }
}
