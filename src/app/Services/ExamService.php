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
    public function getExamByLecture(int $lectureId): ?Exam
    {
        return Exam::with('questions.choices')
            ->where('lecture_id', $lectureId)
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

            if (! empty($data['questions'])) {
                $exam->questions()->delete();

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

    public function deleteExam(Exam $exam): bool
    {
        return $exam->delete();
    }

    public function startAttempt(Exam $exam, Student $student): ExamAttempt
    {
        $existingAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            return $existingAttempt;
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
        $attempt->load('answers.question');

        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            $totalPoints += $question->degree;

            if ($question->type === 'essay') {
                if ($answer->score !== null) {
                    $earnedPoints += $answer->score;
                }
            } else {
                $correctChoice = Choice::where('question_id', $question->id)
                    ->where('is_correct', true)
                    ->first();

                if ($correctChoice && $correctChoice->id == $answer->answer) {
                    $earnedPoints += $question->degree;
                }
            }
        }

        if ($totalPoints === 0) {
            return 0;
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
