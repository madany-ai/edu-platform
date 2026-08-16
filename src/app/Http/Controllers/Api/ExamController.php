<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Student;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $examService
    ) {}

    public function show(Lecture $lecture): JsonResponse
    {
        $isAssignment = request()->is('*assignment*');
        $examId = request()->query('exam_id');
        if ($examId) {
            $exam = Exam::with('questions.choices')
                ->where('lecture_id', $lecture->id)
                ->where('id', $examId)
                ->first();
        } else {
            $exam = $this->examService->getExamByLecture($lecture->id, $isAssignment);
        }

        if (! $exam) {
            $msg = $isAssignment ? 'لا يوجد واجب لهذه المحاضرة.' : 'لا يوجد امتحان لهذه المحاضرة.';
            return response()->json(['message' => $msg], 404);
        }

        if ($exam->relationLoaded('questions')) {
            $exam->questions->each(function ($question) {
                if ($question->relationLoaded('choices')) {
                    $question->choices->each->makeHidden('is_correct');
                }
            });
        }

        $user = request()->user();
        $student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;
        $latestAttempt = null;
        $maxAttemptsReached = false;
        
        if ($student) {
            $latestAttempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->latest()
                ->first();
                
            $completedAttemptsCount = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->whereNotNull('submitted_at')
                ->count();
                
            $maxAttempts = $exam->max_attempts ?? 3;
            $maxAttemptsReached = $completedAttemptsCount >= $maxAttempts;
        }

        return response()->json([
            'exam' => $exam,
            'latest_attempt' => $latestAttempt,
            'max_attempts_reached' => $maxAttemptsReached,
        ]);
    }

    public function store(Request $request, Lecture $lecture): JsonResponse
    {
        $this->authorize('create', [Exam::class, $lecture]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'questions' => 'nullable|array',
            'questions.*.type' => 'nullable|string|in:multiple_choice,essay',
            'questions.*.question' => 'required|string',
            'questions.*.degree' => 'nullable|numeric|min:0',
            'questions.*.choices' => 'required|array|min:2',
            'questions.*.choices.*.answer' => 'required|string',
            'questions.*.choices.*.is_correct' => 'required|boolean',
        ]);

        $exam = $this->examService->createExam($lecture, $validated);

        return response()->json($exam, 201);
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'questions' => 'nullable|array',
            'questions.*.type' => 'nullable|string|in:multiple_choice,essay',
            'questions.*.question' => 'required|string',
            'questions.*.degree' => 'nullable|numeric|min:0',
            'questions.*.choices' => 'required|array|min:2',
            'questions.*.choices.*.answer' => 'required|string',
            'questions.*.choices.*.is_correct' => 'required|boolean',
        ]);

        $exam = $this->examService->updateExam($exam, $validated);

        return response()->json($exam);
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('delete', $exam);

        $this->examService->deleteExam($exam);

        return response()->json(['message' => 'تم حذف الامتحان بنجاح.']);
    }

    public function startAttempt(Exam $exam): JsonResponse
    {
        $user = request()->user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        // Check if student is enrolled or has entitlement to the lecture/course
        $lecture = $exam->lecture;
        if (!$lecture) {
            abort(404, 'الامتحان غير مرتبط بمحاضرة.');
        }

        $courseId = $lecture->section->course_id ?? null;
        if (!$courseId) {
            abort(404, 'الامتحان غير مرتبط بكورس.');
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

        if (!$isEnrolled && !$hasEntitlement) {
            return response()->json(['message' => 'غير مسجل في هذه الدورة أو المحاضرة.'], 403);
        }

        // Check if preceding blocking exams have been passed
        $videoAccessService = app(\App\Services\VideoAccessService::class);
        if ($videoAccessService->isBlockedByExam($user, $lecture, $exam->is_assignment ? 'assignment' : 'exam', $exam->id)) {
            return response()->json(['message' => 'هذا الاختبار مغلق حتى تجتاز الاختبارات السابقة أولاً.'], 403);
        }

        $attempt = $this->examService->startAttempt($exam, $student);

        return response()->json($attempt);
    }

    public function submitAttempt(ExamAttempt $attempt): JsonResponse
    {
        $this->authorize('submit', $attempt);

        $validated = request()->validate([
            'answers' => 'present|array',
            'answers.*.question_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('questions', 'id')->where('exam_id', $attempt->exam_id),
            ],
            'answers.*.answer' => 'required|string',
        ]);

        $attempt = $this->examService->submitAttempt($attempt, $validated['answers']);

        return response()->json($attempt);
    }

    public function result(ExamAttempt $attempt): JsonResponse
    {
        $this->authorize('viewResult', $attempt);

        $attempt->load('answers.question.choices');
        $attempt->answers->each(function ($answer) {
            if ($answer->question && $answer->question->choices) {
                $answer->question->choices->each(function ($choice) {
                    $choice->makeVisible('is_correct');
                });
            }
        });

        return response()->json($attempt);
    }

    public function myAttempts(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json(['data' => []]);
        }

        $attempts = ExamAttempt::with(['exam.lecture.section.course'])
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->latest()
            ->get();

        return response()->json(['data' => $attempts]);
    }
}
