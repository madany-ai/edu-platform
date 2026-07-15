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
    public function __construct(
        private readonly QAService $qaService,
    ) {}

    public function store(StoreQuestionRequest $request, Lecture $lecture): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $question = $this->qaService->postQuestion($lecture, $student, $request->validated());

        return response()->json([
            'message' => 'تم نشر سؤالك بنجاح.',
            'question' => new QuestionResource($question),
        ], 201);
    }

    public function index(Request $request, Lecture $lecture): AnonymousResourceCollection
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        $questions = $this->qaService->getLectureQuestions($lecture, $student, $page, $perPage);

        return QuestionResource::collection($questions);
    }

    public function show(QuestionsPost $question): QuestionResource
    {
        $question = $this->qaService->getQuestion($question);

        return new QuestionResource($question);
    }

    public function reply(StoreReplyRequest $request, QuestionsPost $question): JsonResponse
    {
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
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        $questions = $this->qaService->getMyQuestions($student, $page, $perPage);

        return QuestionResource::collection($questions);
    }

    public function destroyQuestion(Request $request, QuestionsPost $question): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student || ! $this->qaService->deleteQuestion($question, $student)) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا السؤال.'], 403);
        }

        return response()->json(['message' => 'تم حذف السؤال بنجاح.']);
    }

    public function destroyReply(Request $request, QuestionReply $reply): JsonResponse
    {
        $user = $request->user();

        if (! $this->qaService->deleteReply($reply, $user)) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا الرد.'], 403);
        }

        return response()->json(['message' => 'تم حذف الرد بنجاح.']);
    }

    public function instructorQuestions(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        $questions = $this->qaService->getInstructorQuestions($user, $page, $perPage);

        return QuestionResource::collection($questions);
    }

    public function assistantQuestions(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $perPage = min((int) $request->query('per_page', 20), 50);
        $page = (int) $request->query('page', 1);

        $questions = $this->qaService->getAssistantQuestions($user, $page, $perPage);

        return QuestionResource::collection($questions);
    }
}
