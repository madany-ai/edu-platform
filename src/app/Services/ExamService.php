<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function getExamByLecture(string $lectureId, bool $isAssignment = false): ?Exam
    {
        return Exam::with('questions.choices')
            ->where('lecture_id', $lectureId)
            ->where('is_assignment', $isAssignment)
            ->first();
    }

    public function createExam(Lecture $lecture, array $data): Exam
    {
        return DB::transaction(function () use ($lecture, $data) {
            $exam = $lecture->exams()->create([
                'title' => $data['title'],
                'duration' => $data['duration'] ?? 30,
                'is_assignment' => $data['is_assignment'] ?? false,
            ]);

            if (! empty($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $question = $exam->questions()->create([
                        'type' => $questionData['type'] ?? 'multiple_choice',
                        'question' => $questionData['question'],
                        'degree' => $questionData['degree'] ?? 1,
                        'image_path' => $questionData['image_path'] ?? null,
                    ]);

                    if (! empty($questionData['choices'])) {
                        foreach ($questionData['choices'] as $choiceData) {
                            $question->choices()->create([
                                'answer' => $choiceData['answer'],
                                'is_correct' => $choiceData['is_correct'] ?? false,
                            ]);
                        }
                    }
                }
            }

            return $exam->load('questions.choices');
        });
    }

    public function updateExam(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $exam->update([
                'title' => $data['title'] ?? $exam->title,
                'duration' => $data['duration'] ?? $exam->duration,
                'is_assignment' => $data['is_assignment'] ?? $exam->is_assignment,
            ]);

            if (isset($data['questions'])) {
                $incomingQuestionIds = [];

                foreach ($data['questions'] as $questionData) {
                    $questionId = $questionData['id'] ?? null;

                    $question = null;
                    if ($questionId) {
                        $question = $exam->questions()->find($questionId);
                    }

                    if ($question) {
                        $question->update([
                            'type' => $questionData['type'] ?? $question->type,
                            'question' => $questionData['question'],
                            'degree' => $questionData['degree'] ?? $question->degree,
                            'image_path' => $questionData['image_path'] ?? $question->image_path,
                        ]);
                    } else {
                        $question = $exam->questions()->create([
                            'type' => $questionData['type'] ?? 'multiple_choice',
                            'question' => $questionData['question'],
                            'degree' => $questionData['degree'] ?? 1,
                            'image_path' => $questionData['image_path'] ?? null,
                        ]);
                    }

                    $incomingQuestionIds[] = $question->id;

                    if (isset($questionData['choices'])) {
                        $incomingChoiceIds = [];
                        foreach ($questionData['choices'] as $choiceData) {
                            $choiceId = $choiceData['id'] ?? null;

                            $choice = null;
                            if ($choiceId) {
                                $choice = $question->choices()->find($choiceId);
                            }

                            if ($choice) {
                                $choice->update([
                                    'answer' => $choiceData['answer'],
                                    'is_correct' => $choiceData['is_correct'] ?? false,
                                ]);
                            } else {
                                $choice = $question->choices()->create([
                                    'answer' => $choiceData['answer'],
                                    'is_correct' => $choiceData['is_correct'] ?? false,
                                ]);
                            }
                            $incomingChoiceIds[] = $choice->id;
                        }

                        $question->choices()->whereNotIn('id', $incomingChoiceIds)->delete();
                    }
                }

                $exam->questions()->whereNotIn('id', $incomingQuestionIds)->delete();
            }

            return $exam->load('questions.choices');
        });
    }

    public function deleteExam(Exam $exam): bool
    {
        return $exam->delete();
    }

    public function startAttempt(Exam $exam, Student $student): ExamAttempt
    {
        // Enforce maximum 3 attempts per exam for a student
        $completedAttemptsCount = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->count();

        $maxAttempts = $exam->max_attempts ?? 3;
        if ($completedAttemptsCount >= $maxAttempts) {
            abort(403, "لقد استنفدت الحد الأقصى للمحاولات المتاحة لهذا الاختبار ({$maxAttempts} محاولات).");
        }

        $existingAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            // Check if duration expired on the unsubmitted attempt
            if ($exam->duration && $existingAttempt->started_at->addMinutes($exam->duration + 5)->isPast()) {
                // Auto-submit empty attempt as timed out
                $existingAttempt->update([
                    'score' => 0,
                    'submitted_at' => $existingAttempt->started_at->addMinutes($exam->duration),
                ]);
            } else {
                return $existingAttempt;
            }
        }

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now(),
        ]);
    }

    public function submitAttempt(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            $exam = $attempt->exam;

            // Enforce time limit if set
            if ($exam->duration && $attempt->started_at->addMinutes($exam->duration + 5)->isPast()) {
                abort(422, 'تم تجاوز الوقت المحدد للاختبار.');
            }

            foreach ($answers as $answerData) {
                Answer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                ]);
            }

            $score = $this->gradeAttempt($attempt);

            $attempt->update([
                'score' => $score,
                'submitted_at' => now(),
            ]);

            return $attempt->load('answers.question.choices');
        });
    }

    public function gradeAttempt(ExamAttempt $attempt): float
    {
        $attempt->load(['exam.questions', 'answers']);

        $totalPoints = $attempt->exam->questions->sum('degree');
        if ($totalPoints == 0) {
            return 0;
        }

        $earnedPoints = 0;

        foreach ($attempt->answers as $answer) {
            $question = $attempt->exam->questions->firstWhere('id', $answer->question_id);
            if (! $question) {
                continue;
            }

            if ($question->type === 'essay') {
                // Essay questions require manual grading
            } else {
                $correctChoice = Choice::where('question_id', $question->id)
                    ->where('is_correct', true)
                    ->first();

                if ($correctChoice && $correctChoice->id === $answer->answer) {
                    $earnedPoints += $question->degree;
                }
            }
        }

        return round(($earnedPoints / $totalPoints) * 100, 2);
    }

    public function getStudentResult(Student $student, Exam $exam): ?ExamAttempt
    {
        return ExamAttempt::with('answers.question.choices')
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();
    }
}
