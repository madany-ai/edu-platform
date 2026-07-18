<?php

use App\Models\Choice;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->studentUser = User::factory()->create(['status' => 'active']);
    $this->studentUser->assignRole('student');

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'first_name' => 'Test',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'Student',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $this->course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $this->service = app(\App\Services\ExamService::class);
});

it('gradeAttempt returns 0 when total points are zero', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Zero Degree Exam',
        'duration' => 30,
    ]);

    $question = $exam->questions()->create([
        'type' => 'multiple_choice',
        'question' => 'Zero degree question',
        'degree' => 0,
    ]);

    $choice = $question->choices()->create(['answer' => 'A', 'is_correct' => true]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    \App\Models\Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'answer' => $choice->id,
    ]);

    $score = $this->service->gradeAttempt($attempt);

    expect($score)->toBe(0.0);
});

it('gradeAttempt handles mixed MCQ and essay questions correctly', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Mixed Exam',
        'duration' => 30,
    ]);

    $mcq = $exam->questions()->create([
        'type' => 'multiple_choice',
        'question' => 'MCQ question',
        'degree' => 10,
    ]);
    $correctChoice = $mcq->choices()->create(['answer' => 'Correct', 'is_correct' => true]);
    $wrongChoice = $mcq->choices()->create(['answer' => 'Wrong', 'is_correct' => false]);

    $essay = $exam->questions()->create([
        'type' => 'essay',
        'question' => 'Essay question',
        'degree' => 10,
    ]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    \App\Models\Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $mcq->id,
        'answer' => $correctChoice->id,
    ]);

    \App\Models\Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $essay->id,
        'answer' => 'My essay answer',
    ]);

    $score = $this->service->gradeAttempt($attempt);

    expect((int) $score)->toBe(50);
});

it('gradeAttempt gives 50% for mixed correct and wrong MCQ', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Half Score',
        'duration' => 30,
    ]);

    $q1 = $exam->questions()->create([
        'type' => 'multiple_choice',
        'question' => 'Q1',
        'degree' => 10,
    ]);
    $correct1 = $q1->choices()->create(['answer' => 'A', 'is_correct' => true]);

    $q2 = $exam->questions()->create([
        'type' => 'multiple_choice',
        'question' => 'Q2',
        'degree' => 10,
    ]);
    $wrong2 = $q2->choices()->create(['answer' => 'B', 'is_correct' => false]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    \App\Models\Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $q1->id,
        'answer' => $correct1->id,
    ]);

    \App\Models\Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $q2->id,
        'answer' => $wrong2->id,
    ]);

    $score = $this->service->gradeAttempt($attempt);

    expect((int) $score)->toBe(50);
});

it('gradeAttempt gives zero for essay with empty answer', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Essay Exam',
        'duration' => 30,
    ]);

    $essay = $exam->questions()->create([
        'type' => 'essay',
        'question' => 'Empty essay',
        'degree' => 10,
    ]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    \App\Models\Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $essay->id,
        'answer' => '   ',
    ]);

    $score = $this->service->gradeAttempt($attempt);

    expect($score)->toBe(0.0);
});

it('getStudentResult returns null when no submitted attempt exists', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Exam',
        'duration' => 30,
    ]);

    $result = $this->service->getStudentResult($this->student, $exam);

    expect($result)->toBeNull();
});

it('getStudentResult returns latest submitted attempt', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Exam',
        'duration' => 30,
    ]);

    $oldAttempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'score' => 50,
        'submitted_at' => now()->subHour(),
    ]);

    $newAttempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'score' => 80,
        'submitted_at' => now(),
    ]);

    $result = $this->service->getStudentResult($this->student, $exam);

    expect($result->id)->toBe($newAttempt->id)
        ->and($result->score)->toBe(80);
});

it('getExamByLecture returns assignment when isAssignment is true', function () {
    Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Regular Exam',
        'duration' => 30,
        'is_assignment' => false,
    ]);

    $assignment = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Assignment',
        'duration' => 60,
        'is_assignment' => true,
    ]);

    $result = $this->service->getExamByLecture($this->lecture->id, true);

    expect($result->id)->toBe($assignment->id)
        ->and($result->title)->toBe('Assignment');
});

it('getExamByLecture returns null when no exam exists', function () {
    $result = $this->service->getExamByLecture($this->lecture->id, false);

    expect($result)->toBeNull();
});

it('submitAttempt stores answers and grades correctly', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Exam',
        'duration' => 30,
    ]);

    $question = $exam->questions()->create([
        'type' => 'multiple_choice',
        'question' => 'Q1',
        'degree' => 10,
    ]);
    $correctChoice = $question->choices()->create(['answer' => 'A', 'is_correct' => true]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    $result = $this->service->submitAttempt($attempt, [
        ['question_id' => $question->id, 'answer' => $correctChoice->id],
    ]);

    expect($result->submitted_at)->not->toBeNull()
        ->and((int) $result->score)->toBe(100);
});
