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
        $examId = request()->query('exam_id');
        if ($examId) {
            $exam = Exam::with('questions.choices')
                ->where('lecture_id', $lecture->id)
                ->where('id', $examId)
                ->first();
        } else {
            $exam = $this->examService->getExamByLecture($lecture->id, false);
        }

        if (! $exam) {
            return response()->json(['message' => 'لا يوجد امتحان لهذه المحاضرة.'], 404);
        }

        $user = request()->user();
        $student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;
        $latestAttempt = null;
        if ($student) {
            $latestAttempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->latest()
                ->first();
        }

        return response()->json([
            'exam' => $exam,
            'latest_attempt' => $latestAttempt,
        ]);
    }

    public function showAssignment(Lecture $lecture): JsonResponse
    {
        $examId = request()->query('exam_id');
        if ($examId) {
            $exam = Exam::with('questions.choices')
                ->where('lecture_id', $lecture->id)
                ->where('id', $examId)
                ->first();
        } else {
            $exam = $this->examService->getExamByLecture($lecture->id, true);
        }

        if (! $exam) {
            return response()->json(['message' => 'لا يوجد واجب لهذه المحاضرة.'], 404);
        }

        $user = request()->user();
        $student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;
        $latestAttempt = null;
        if ($student) {
            $latestAttempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->latest()
                ->first();
        }

        return response()->json([
            'exam' => $exam,
            'latest_attempt' => $latestAttempt,
        ]);
    }

    public function store(Request $request, Lecture $lecture): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'questions' => 'nullable|array',
            'questions.*.type' => 'nullable|string|in:multiple_choice,true_false',
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
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'questions' => 'nullable|array',
            'questions.*.type' => 'nullable|string|in:multiple_choice,true_false',
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
        $this->examService->deleteExam($exam);

        return response()->json(['message' => 'تم حذف الامتحان بنجاح.']);
    }

    public function startAttempt(Exam $exam): JsonResponse
    {
        $user = request()->user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $attempt = $this->examService->startAttempt($exam, $student);

        return response()->json($attempt);
    }

    public function submitAttempt(ExamAttempt $attempt): JsonResponse
    {
        $validated = request()->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'required|string',
        ]);

        $attempt = $this->examService->submitAttempt($attempt, $validated['answers']);

        return response()->json($attempt);
    }

    public function result(ExamAttempt $attempt): JsonResponse
    {
        return response()->json($attempt->load('answers.question.choices'));
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
