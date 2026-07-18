<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\StoreReplyRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Lecture;
use App\Models\QuestionsPost;
use App\Models\QuestionReply;
use App\Models\Student;
use App\Services\QAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QAController extends Controller
{
    use \App\Traits\ResolvesStudent;
    public function __construct(
        private readonly QAService $qaService,
    ) {}

    public function store(StoreQuestionRequest $request, Lecture $lecture): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($user) ?? abort(404, 'الطالب غير موجود.');

        $question = $this->qaService->postQuestion($lecture, $student, $request->validated());

        return response()->json([
            'message' => 'تم نشر سؤالك بنجاح.',
            'question' => new QuestionResource($question),
        ], 201);
    }

    public function index(Request $request, Lecture $lecture): AnonymousResourceCollection
    {
        $user = $request->user();
        $student = $this->resolveStudent($user);

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        $questions = $this->qaService->getLectureQuestions($lecture, $student, $page, $perPage);

        return QuestionResource::collection($questions);
    }

    private function authorizeQuestionAccess(Request $request, QuestionsPost $question): void
    {
        $user = $request->user();
        if ($user->hasRole('super_admin')) {
            return;
        }

        $lecture = $question->lecture;
        if (! $lecture) {
            abort(404, 'المحاضرة غير موجودة.');
        }

        $courseId = $lecture->section->course_id ?? null;
        if (! $courseId) {
            abort(404, 'الكورس غير موجود.');
        }

        // Instructor check
        if ($user->hasRole('instructor') && $lecture->section->course->instructor_id === $user->id) {
            return;
        }

        // Assistant check
        if ($user->hasRole('assistant')) {
            $isAssigned = \App\Models\CourseAssistant::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();
            if ($isAssigned) {
                return;
            }
        }

        // Student check
        $student = $this->resolveStudent($user);
        if (! $student) {
            abort(403, 'غير مصرح لك.');
        }

        $isEnrolled = \App\Models\Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        $hasEntitlement = \App\Models\Entitlement::where('student_id', $student->id)
            ->where('lecture_id', $lecture->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (! $isEnrolled && ! $hasEntitlement) {
            abort(403, 'غير مسجل في هذا الكورس أو المحاضرة.');
        }
    }

    public function show(Request $request, QuestionsPost $question): QuestionResource
    {
        $this->authorizeQuestionAccess($request, $question);

        $question = $this->qaService->getQuestion($question);

        return new QuestionResource($question);
    }

    public function reply(StoreReplyRequest $request, QuestionsPost $question): JsonResponse
    {
        $this->authorizeQuestionAccess($request, $question);

        $user = $request->user();

        $reply = $this->qaService->replyToQuestion($question, $user, $request->validated());

        return response()->json([
            'message' => 'تم إضافة الرد بنجاح.',
            'reply' => new \App\Http\Resources\QuestionReplyResource($reply),
        ], 201);
    }

    public function myQuestions(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $student = $this->resolveStudent($user) ?? abort(404, 'الطالب غير موجود.');

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        $questions = $this->qaService->getMyQuestions($student, $page, $perPage);

        return QuestionResource::collection($questions);
    }

    public function destroyQuestion(Request $request, QuestionsPost $question): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($user);

        // Check if student owns it
        if ($student && $question->student_id === $student->id) {
            $question->delete();
            return response()->json(['message' => 'تم حذف السؤال بنجاح.']);
        }

        // Check if instructor or super_admin or assistant owns/is assigned to the course
        $lecture = $question->lecture;
        $course = $lecture ? $lecture->section->course : null;

        if ($user->hasRole('super_admin') ||
            ($user->hasRole('instructor') && $course && $course->instructor_id === $user->id) ||
            ($user->hasRole('assistant') && $course && \App\Models\CourseAssistant::where('user_id', $user->id)->where('course_id', $course->id)->exists())
        ) {
            $question->delete();
            return response()->json(['message' => 'تم حذف السؤال بنجاح.']);
        }

        return response()->json(['message' => 'غير مصرح لك بحذف هذا السؤال.'], 403);
    }

    public function destroyReply(Request $request, QuestionReply $reply): JsonResponse
    {
        $user = $request->user();

        // Check if user is the reply author
        if ($reply->user_id === $user->id) {
            $reply->delete();
            return response()->json(['message' => 'تم حذف الرد بنجاح.']);
        }

        // Check if instructor or super_admin or assistant owns/is assigned to the course of the question
        $question = $reply->question;
        $lecture = $question ? $question->lecture : null;
        $course = $lecture ? $lecture->section->course : null;

        if ($user->hasRole('super_admin') ||
            ($user->hasRole('instructor') && $course && $course->instructor_id === $user->id) ||
            ($user->hasRole('assistant') && $course && \App\Models\CourseAssistant::where('user_id', $user->id)->where('course_id', $course->id)->exists())
        ) {
            $reply->delete();
            return response()->json(['message' => 'تم حذف الرد بنجاح.']);
        }

        return response()->json(['message' => 'غير مصرح لك بحذف هذا الرد.'], 403);
    }

    public function staffQuestions(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        if ($user->hasRole('assistant')) {
            $questions = $this->qaService->getAssistantQuestions($user, $page, $perPage);
        } else {
            $questions = $this->qaService->getInstructorQuestions($user, $page, $perPage);
        }

        return QuestionResource::collection($questions);
    }
}
