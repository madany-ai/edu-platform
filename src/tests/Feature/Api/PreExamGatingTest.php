<?php

use App\Models\Answer;
use App\Models\Choice;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Entitlement;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use App\Services\VideoAccessService;

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
        'title' => 'Physics Course',
        'description' => 'Physics',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = CourseSection::create([
        'course_id' => $this->course->id,
        'title' => 'Month 1',
        'sort_order' => 1,
    ]);

    $this->lecture1 = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 1 - Intro',
        'description' => 'Introduction',
        'duration' => 30,
        'sort_order' => 1,
    ]);

    $this->lecture2 = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 2 - Advanced',
        'description' => 'Advanced topics',
        'duration' => 45,
        'sort_order' => 2,
    ]);

    $this->blockingExam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Pre-Exam: Lecture 1',
        'duration' => 30,
        'sort_order' => 1,
        'pass_percentage' => 50,
        'is_blocking' => true,
        'is_assignment' => false,
    ]);

    $question = Question::create([
        'exam_id' => $this->blockingExam->id,
        'type' => 'multiple_choice',
        'question' => 'What is 2+2?',
        'degree' => 10,
    ]);

    $this->correctChoice = Choice::create([
        'question_id' => $question->id,
        'answer' => '4',
        'is_correct' => true,
    ]);

    $this->wrongChoice = Choice::create([
        'question_id' => $question->id,
        'answer' => '3',
        'is_correct' => false,
    ]);

    \App\Models\Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);
});

it('blocks video when pre-exam is not passed', function () {
    $accessService = app(VideoAccessService::class);

    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))
        ->toBeTrue();
});

it('allows video after passing pre-exam', function () {
    $attempt = ExamAttempt::create([
        'exam_id' => $this->blockingExam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $this->correctChoice->question_id,
        'answer' => $this->correctChoice->id,
    ]);

    $attempt->update(['score' => 100, 'submitted_at' => now()]);

    $accessService = app(VideoAccessService::class);

    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))
        ->toBeFalse();
});

it('keeps video blocked when exam is failed', function () {
    $attempt = ExamAttempt::create([
        'exam_id' => $this->blockingExam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    Answer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $this->correctChoice->question_id,
        'answer' => $this->wrongChoice->id,
    ]);

    $attempt->update(['score' => 0, 'submitted_at' => now()]);

    $accessService = app(VideoAccessService::class);

    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))
        ->toBeTrue();
});

it('saves exam score correctly in database', function () {
    $examService = app(\App\Services\ExamService::class);

    $attempt = $examService->startAttempt($this->blockingExam, $this->student);
    expect($attempt)->not->toBeNull();
    expect($attempt->submitted_at)->toBeNull();

    $attempt = $examService->submitAttempt($attempt, [
        ['question_id' => $this->correctChoice->question_id, 'answer' => $this->correctChoice->id],
    ]);

    expect($attempt->submitted_at)->not->toBeNull();
    expect((int) $attempt->score)->toBe(100);

    $this->assertDatabaseHas('exam_attempts', [
        'exam_id' => $this->blockingExam->id,
        'student_id' => $this->student->id,
        'score' => 100,
    ]);
});

it('works through exam attempt API flow', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/exams/{$this->blockingExam->id}/start");
    $response->assertOk();

    $attempt = $response->json();
    expect($attempt['id'])->not->toBeEmpty();

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/attempts/{$attempt['id']}/submit", [
            'answers' => [
                ['question_id' => $this->correctChoice->question_id, 'answer' => $this->correctChoice->id],
            ],
        ]);
    $response->assertOk()->assertJsonPath('score', 100);

    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/attempts/{$attempt['id']}/result");
    $response->assertOk()->assertJsonPath('score', 100);
});

it('blocks lecture access when exam not passed', function () {
    $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture2->id}")
        ->assertStatus(403);
});

it('allows lecture access after passing exam', function () {
    ExamAttempt::create([
        'exam_id' => $this->blockingExam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
        'submitted_at' => now(),
        'score' => 100,
    ]);

    $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture2->id}")
        ->assertOk();
});

it('does not block lecture by its own exam', function () {
    $accessService = app(VideoAccessService::class);

    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture1, 'video'))
        ->toBeFalse();
});

it('never blocks instructor by exams', function () {
    $accessService = app(VideoAccessService::class);

    expect($accessService->isBlockedByExam($this->instructor, $this->lecture2, 'video'))
        ->toBeFalse();
});
