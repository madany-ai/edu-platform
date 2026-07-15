<?php

use App\Models\Choice;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Exam;
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
        'title' => 'Math Course',
        'description' => 'Math',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'Month 1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create([
        'title' => 'Lecture 1',
        'description' => 'Content',
        'sort_order' => 1,
    ]);

    $this->assignment = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Assignment 1',
        'duration' => 60,
        'sort_order' => 1,
        'pass_percentage' => 70,
        'is_blocking' => false,
        'is_assignment' => true,
    ]);

    $question = Question::create([
        'exam_id' => $this->assignment->id,
        'type' => 'multiple_choice',
        'question' => 'Solve: 3x = 9',
        'degree' => 10,
    ]);

    Choice::create(['question_id' => $question->id, 'answer' => 'x = 3', 'is_correct' => true]);
    Choice::create(['question_id' => $question->id, 'answer' => 'x = 2', 'is_correct' => false]);

    \App\Models\Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);
});

it('allows student to view assignment with questions and choices', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture->id}/assignment");

    $response->assertOk()
        ->assertJsonStructure([
            'exam' => ['id', 'title', 'questions'],
            'latest_attempt',
        ])
        ->assertJsonCount(1, 'exam.questions');

    $question = $response->json('exam.questions.0');
    expect($question['choices'])->toHaveCount(2);
});

it('allows student to start and submit assignment with full score', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/exams/{$this->assignment->id}/start");
    $response->assertOk();
    $attemptId = $response->json('id');

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/attempts/{$attemptId}/submit", [
            'answers' => [
                [
                    'question_id' => $this->assignment->questions()->first()->id,
                    'answer' => $this->assignment->questions()->first()->choices()->where('is_correct', true)->first()->id,
                ],
            ],
        ]);

    $response->assertOk()->assertJsonPath('score', 100);

    $this->assertDatabaseHas('exam_attempts', [
        'id' => $attemptId,
        'score' => 100,
    ]);
});

it('gives zero score for wrong answer', function () {
    $question = $this->assignment->questions()->first();

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/exams/{$this->assignment->id}/start");
    $attemptId = $response->json('id');

    $wrongChoice = $question->choices()->where('is_correct', false)->first();

    $this->actingAs($this->studentUser)
        ->postJson("/api/attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $question->id, 'answer' => $wrongChoice->id],
            ],
        ])->assertOk()->assertJsonPath('score', 0);
});

it('allows student to view submission result with answers', function () {
    $question = $this->assignment->questions()->first();
    $correctChoice = $question->choices()->where('is_correct', true)->first();

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/exams/{$this->assignment->id}/start");
    $attemptId = $response->json('id');

    $this->actingAs($this->studentUser)
        ->postJson("/api/attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $question->id, 'answer' => $correctChoice->id],
            ],
        ]);

    $this->actingAs($this->studentUser)
        ->getJson("/api/attempts/{$attemptId}/result")
        ->assertOk()
        ->assertJsonPath('score', 100)
        ->assertJsonStructure(['answers' => [0 => ['question', 'answer']]]);
});

it('returns 404 when no assignment exists for lecture', function () {
    $emptyLecture = $this->section->lectures()->create([
        'title' => 'No Assignment Lecture',
        'sort_order' => 5,
    ]);

    $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$emptyLecture->id}/assignment")
        ->assertStatus(404);
});

it('returns submitted attempts in my-attempts endpoint', function () {
    $question = $this->assignment->questions()->first();
    $correctChoice = $question->choices()->where('is_correct', true)->first();

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/exams/{$this->assignment->id}/start");
    $attemptId = $response->json('id');

    $this->actingAs($this->studentUser)
        ->postJson("/api/attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $question->id, 'answer' => $correctChoice->id],
            ],
        ]);

    $this->actingAs($this->studentUser)
        ->getJson('/api/my-attempts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
