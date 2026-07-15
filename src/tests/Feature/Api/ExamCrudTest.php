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

    \App\Models\Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);
});

it('instructor creates exam with questions', function () {
    $response = $this->actingAs($this->instructor)->postJson("/api/lectures/{$this->lecture->id}/exam", [
        'title' => 'Midterm',
        'duration' => 60,
        'questions' => [
            [
                'type' => 'multiple_choice',
                'question' => 'What is 2+2?',
                'degree' => 10,
                'choices' => [
                    ['answer' => '4', 'is_correct' => true],
                    ['answer' => '3', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJson(['title' => 'Midterm']);

    $exam = Exam::where('lecture_id', $this->lecture->id)->first();
    expect($exam)->not->toBeNull();
    expect($exam->questions()->count())->toBe(1);
});

it('instructor creates exam without questions', function () {
    $response = $this->actingAs($this->instructor)->postJson("/api/lectures/{$this->lecture->id}/exam", [
        'title' => 'Empty Exam',
        'duration' => 30,
    ]);

    $response->assertStatus(201);

    $exam = Exam::where('title', 'Empty Exam')->first();
    expect($exam->questions()->count())->toBe(0);
});

it('instructor updates exam title', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Old Title',
        'duration' => 30,
    ]);

    $this->actingAs($this->instructor)->putJson("/api/exams/{$exam->id}", [
        'title' => 'New Title',
    ])->assertOk();

    $this->assertDatabaseHas('exams', ['id' => $exam->id, 'title' => 'New Title']);
});

it('instructor updates exam with new questions', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Old Exam',
        'duration' => 30,
    ]);

    $response = $this->actingAs($this->instructor)->putJson("/api/exams/{$exam->id}", [
        'title' => 'Updated Exam',
        'questions' => [
            [
                'type' => 'multiple_choice',
                'question' => 'What is 3+3?',
                'degree' => 5,
                'choices' => [
                    ['answer' => '6', 'is_correct' => true],
                    ['answer' => '5', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    $response->assertOk();

    $exam->refresh();
    expect($exam->title)->toBe('Updated Exam');
    expect($exam->questions()->count())->toBe(1);
});

it('instructor deletes exam', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'To Delete',
        'duration' => 30,
    ]);

    $this->actingAs($this->instructor)->deleteJson("/api/exams/{$exam->id}")
        ->assertOk();

    $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
});

it('student cannot create exam', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/exam", [
        'title' => 'Hacked',
    ])->assertStatus(403);
});

it('student cannot update exam', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Original',
        'duration' => 30,
    ]);

    $this->actingAs($this->studentUser)->putJson("/api/exams/{$exam->id}", [
        'title' => 'Hacked',
    ])->assertStatus(403);
});

it('student cannot delete exam', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'To Delete',
        'duration' => 30,
    ]);

    $this->actingAs($this->studentUser)->deleteJson("/api/exams/{$exam->id}")
        ->assertStatus(403);
});

it('student starts exam attempt', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");

    $response->assertOk()
        ->assertJsonStructure(['id', 'exam_id', 'student_id', 'started_at']);
});

it('start attempt returns existing if not submitted', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    $response1 = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $id1 = $response1->json('id');

    $response2 = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $id2 = $response2->json('id');

    expect($id1)->toBe($id2);
});

it('student submits attempt and gets auto-graded', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    $question = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'What is 2+2?',
        'degree' => 10,
    ]);

    $correctChoice = Choice::create([
        'question_id' => $question->id,
        'answer' => '4',
        'is_correct' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $response = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => $correctChoice->id],
        ],
    ]);

    $response->assertOk()->assertJsonPath('score', 100);
});

it('student submits attempt with wrong answer gets zero', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    $question = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'What is 2+2?',
        'degree' => 10,
    ]);

    $wrongChoice = Choice::create([
        'question_id' => $question->id,
        'answer' => '3',
        'is_correct' => false,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $response = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => $wrongChoice->id],
        ],
    ]);

    $response->assertOk()->assertJsonPath('score', 0);
});

it('essay questions get full score when answered', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Essay Exam',
        'duration' => 60,
    ]);

    $question = Question::create([
        'exam_id' => $exam->id,
        'type' => 'essay',
        'question' => 'Explain calculus',
        'degree' => 20,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $response = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => 'Calculus is...'],
        ],
    ]);

    $response->assertOk()->assertJsonPath('score', 100);
});

it('essay questions reject empty answer via validation', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Essay Exam',
        'duration' => 60,
    ]);

    $question = Question::create([
        'exam_id' => $exam->id,
        'type' => 'essay',
        'question' => 'Explain calculus',
        'degree' => 20,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $response = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => ''],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['answers.0.answer']);
});

it('student views attempt result', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    $question = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'What is 2+2?',
        'degree' => 10,
    ]);

    $correctChoice = Choice::create([
        'question_id' => $question->id,
        'answer' => '4',
        'is_correct' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => $correctChoice->id],
        ],
    ]);

    $this->actingAs($this->studentUser)->getJson("/api/attempts/{$attemptId}/result")
        ->assertOk()
        ->assertJsonPath('score', 100);
});

it('my-attempts lists only submitted attempts', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    $question = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'Q1?',
        'degree' => 10,
    ]);

    $correctChoice = Choice::create([
        'question_id' => $question->id,
        'answer' => 'A',
        'is_correct' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");

    $this->actingAs($this->studentUser)->getJson('/api/my-attempts')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => $correctChoice->id],
        ],
    ]);

    $this->actingAs($this->studentUser)->getJson('/api/my-attempts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows exam for lecture', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Midterm',
        'duration' => 60,
    ]);

    Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'Q1?',
        'degree' => 10,
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture->id}/exam");

    $response->assertOk()
        ->assertJsonPath('exam.title', 'Midterm');
});

it('returns 404 when no exam for lecture', function () {
    $emptyLecture = $this->section->lectures()->create([
        'title' => 'No Exam',
        'sort_order' => 5,
    ]);

    $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$emptyLecture->id}/exam")
        ->assertStatus(404);
});

it('rejects exam store with missing required fields', function () {
    $response = $this->actingAs($this->instructor)->postJson("/api/lectures/{$this->lecture->id}/exam", [
        'duration' => 30,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

it('partial score for mixed correct and wrong answers', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Mixed Exam',
        'duration' => 60,
    ]);

    $q1 = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'Q1?',
        'degree' => 10,
    ]);
    $correct1 = Choice::create(['question_id' => $q1->id, 'answer' => 'A', 'is_correct' => true]);
    Choice::create(['question_id' => $q1->id, 'answer' => 'B', 'is_correct' => false]);

    $q2 = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'Q2?',
        'degree' => 10,
    ]);
    Choice::create(['question_id' => $q2->id, 'answer' => 'C', 'is_correct' => true]);
    $wrong2 = Choice::create(['question_id' => $q2->id, 'answer' => 'D', 'is_correct' => false]);

    $response = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $attemptId = $response->json('id');

    $response = $this->actingAs($this->studentUser)->postJson("/api/attempts/{$attemptId}/submit", [
        'answers' => [
            ['question_id' => $q1->id, 'answer' => $correct1->id],
            ['question_id' => $q2->id, 'answer' => $wrong2->id],
        ],
    ]);

    $response->assertOk()->assertJsonPath('score', 50);
});
