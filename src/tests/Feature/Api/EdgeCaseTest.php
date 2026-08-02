<?php

use App\Models\Choice;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Entitlement;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Order;
use App\Models\Product;
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
        'is_verified' => true,
    ]);

    $this->course = Course::create([
        'title' => 'Multi Section Course',
        'description' => 'Course with multiple sections',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section1 = $this->course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);
    $this->section2 = $this->course->sections()->create(['title' => 'Section 2', 'sort_order' => 2]);

    $this->lecture1 = $this->section1->lectures()->create([
        'title' => 'Lecture 1',
        'description' => 'Content',
        'duration' => 30,
        'sort_order' => 1,
    ]);

    $this->lecture2 = $this->section2->lectures()->create([
        'title' => 'Lecture 2',
        'description' => 'Content',
        'duration' => 45,
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

it('multiple blocking exams in sequence block until all passed', function () {
    // Create lecture1b in section1 (between lecture1 and lecture2)
    $lecture1b = $this->section1->lectures()->create([
        'title' => 'Lecture 1B',
        'description' => 'Content',
        'duration' => 30,
        'sort_order' => 2,
    ]);

    $exam1 = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Exam 1',
        'duration' => 30,
        'sort_order' => 1,
        'pass_percentage' => 50,
        'is_blocking' => true,
        'is_assignment' => false,
    ]);

    $q1 = Question::create([
        'exam_id' => $exam1->id,
        'type' => 'multiple_choice',
        'question' => 'Q1?',
        'degree' => 10,
    ]);
    $c1 = Choice::create(['question_id' => $q1->id, 'answer' => 'A', 'is_correct' => true]);

    $exam2 = Exam::create([
        'lecture_id' => $lecture1b->id,
        'title' => 'Exam 2',
        'duration' => 30,
        'sort_order' => 1,
        'pass_percentage' => 50,
        'is_blocking' => true,
        'is_assignment' => false,
    ]);

    $q2 = Question::create([
        'exam_id' => $exam2->id,
        'type' => 'multiple_choice',
        'question' => 'Q2?',
        'degree' => 10,
    ]);
    $c2 = Choice::create(['question_id' => $q2->id, 'answer' => 'B', 'is_correct' => true]);

    $accessService = app(VideoAccessService::class);

    // Initially blocked (both exams in section1 precede lecture2 in section2)
    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeTrue();

    // Pass exam 1 only - still blocked by exam 2
    ExamAttempt::create([
        'exam_id' => $exam1->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
        'submitted_at' => now(),
        'score' => 100,
    ]);

    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeTrue();

    // Pass exam 2 as well - unblocked
    ExamAttempt::create([
        'exam_id' => $exam2->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
        'submitted_at' => now(),
        'score' => 100,
    ]);

    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeFalse();
});

it('submitting exam twice creates new attempt', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Multi-Submit Exam',
        'duration' => 30,
    ]);

    $q = Question::create([
        'exam_id' => $exam->id,
        'type' => 'multiple_choice',
        'question' => 'Q?',
        'degree' => 10,
    ]);
    $c = Choice::create(['question_id' => $q->id, 'answer' => 'A', 'is_correct' => true]);

    // First attempt
    $r1 = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $a1 = $r1->json('id');

    $this->actingAs($this->studentUser)->postJson("/api/attempts/{$a1}/submit", [
        'answers' => [['question_id' => $q->id, 'answer' => $c->id]],
    ]);

    // Start second attempt
    $r2 = $this->actingAs($this->studentUser)->postJson("/api/exams/{$exam->id}/start");
    $a2 = $r2->json('id');

    expect($a1)->not->toBe($a2);

    $this->actingAs($this->studentUser)->postJson("/api/attempts/{$a2}/submit", [
        'answers' => [['question_id' => $q->id, 'answer' => $c->id]],
    ]);

    $this->assertDatabaseCount('exam_attempts', 2);
});

it('product purchase creates correct order fields', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Access',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 25.50,
        'access_duration_days' => 30,
        'is_active' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(201);

    $order = Order::where('student_id', $this->student->id)->first();
    expect($order->amount_cents)->toBe(2550);
    expect($order->currency)->toBe('EGP');
    expect($order->status->value)->toBe('pending');
    expect($order->paid_at)->toBeNull();
    expect($order->transaction_id)->toStartWith('PENDING-');
    expect($order->payment_method)->toBe('manual');
});

it('instructor cannot enroll in own course via student endpoints', function () {
    $this->actingAs($this->instructor)->postJson("/api/courses/{$this->course->id}/enroll")
        ->assertStatus(403);
});

it('course price zero check for free course enrollment path', function () {
    $freeCourse = Course::create([
        'title' => 'Free Course',
        'description' => 'Free',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $s = $freeCourse->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $l = $s->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $accessService = app(VideoAccessService::class);

    // Enrolled student can access free course lectures
    \App\Models\Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    expect($accessService->canAccess($this->studentUser, $l))->toBeTrue();

    // Non-enrolled student cannot
    $otherStudentUser = User::factory()->create(['status' => 'active']);
    $otherStudentUser->assignRole('student');
    expect($accessService->canAccess($otherStudentUser, $l))->toBeFalse();
});

it('assistant cannot access unassigned course lectures via video stream', function () {
    $assistant = User::factory()->create(['status' => 'active']);
    $assistant->assignRole('assistant');

    $accessService = app(VideoAccessService::class);
    expect($accessService->canAccess($assistant, $this->lecture1))->toBeFalse();
});

it('exam blocking does not affect same-lecture exam access', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Exam on L1',
        'duration' => 30,
        'sort_order' => 1,
        'pass_percentage' => 50,
        'is_blocking' => true,
        'is_assignment' => false,
    ]);

    $accessService = app(VideoAccessService::class);

    // Exam should not be blocked by itself
    expect($accessService->isBlockedByExam($this->studentUser, $this->lecture1, 'exam', $exam->id))->toBeFalse();
});
