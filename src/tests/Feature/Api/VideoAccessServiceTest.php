<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Entitlement;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

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

    $this->admin = User::factory()->create(['status' => 'active']);
    $this->admin->assignRole('super_admin');

    $this->course = Course::create([
        'title' => 'Math Course',
        'description' => 'Advanced Math',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section1 = $this->course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);
    $this->section2 = $this->course->sections()->create(['title' => 'Section 2', 'sort_order' => 2]);

    $this->lecture1 = $this->section1->lectures()->create(['title' => 'Lecture 1', 'sort_order' => 1]);
    $this->lecture2 = $this->section1->lectures()->create(['title' => 'Lecture 2', 'sort_order' => 2]);
    $this->lecture3 = $this->section2->lectures()->create(['title' => 'Lecture 3', 'sort_order' => 1]);

    $this->service = app(\App\Services\VideoAccessService::class);
});

it('canAccess returns true for super_admin', function () {
    expect($this->service->canAccess($this->admin, $this->lecture1))->toBeTrue();
});

it('canAccess returns true for course instructor', function () {
    expect($this->service->canAccess($this->instructor, $this->lecture1))->toBeTrue();
});

it('canAccess returns false for instructor of different course', function () {
    $otherInstructor = User::factory()->create(['status' => 'active']);
    $otherInstructor->assignRole('instructor');

    expect($this->service->canAccess($otherInstructor, $this->lecture1))->toBeFalse();
});

it('canAccess returns true for student with valid entitlement', function () {
    $order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    expect($this->service->canAccess($this->studentUser, $this->lecture1))->toBeTrue();
});

it('canAccess returns false for student without entitlement on paid course', function () {
    expect($this->service->canAccess($this->studentUser, $this->lecture1))->toBeFalse();
});

it('canAccess returns false for user without student record', function () {
    $userNoStudent = User::factory()->create(['status' => 'active']);
    $userNoStudent->assignRole('student');

    expect($this->service->canAccess($userNoStudent, $this->lecture1))->toBeFalse();
});

it('canAccess returns true for enrolled student on free course', function () {
    $freeCourse = Course::create([
        'title' => 'Free Course',
        'description' => 'Free',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $freeCourse->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    \App\Models\Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    expect($this->service->canAccess($this->studentUser, $lecture))->toBeTrue();
});

it('canAccess returns false for unenrolled student on free course', function () {
    $freeCourse = Course::create([
        'title' => 'Free Course',
        'description' => 'Free',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $freeCourse->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    expect($this->service->canAccess($this->studentUser, $lecture))->toBeFalse();
});

it('canAccess returns false when entitled but blocked by exam', function () {
    $order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture2->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->canAccess($this->studentUser, $this->lecture2))->toBeFalse();
});

it('canAccess returns true when entitled and blocking exam is passed', function () {
    $order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture2->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'score' => 80,
        'submitted_at' => now(),
    ]);

    expect($this->service->canAccess($this->studentUser, $this->lecture2))->toBeTrue();
});

it('generateSignedToken creates valid encrypted token', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($token)->toBeString()->not->toBeEmpty();

    $payload = Crypt::decrypt($token);
    expect($payload)->toBeArray()
        ->toHaveKeys(['user_id', 'lecture_id', 'ip', 'expires_at'])
        ->and($payload['user_id'])->toBe($this->studentUser->id)
        ->and($payload['lecture_id'])->toBe($this->lecture1->id)
        ->and($payload['ip'])->toBe('192.168.1.1')
        ->and($payload['expires_at'])->toBeGreaterThan(now()->timestamp);
});

it('validateToken returns true for valid token', function () {
    $order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeTrue();
});

it('validateToken returns false for expired token', function () {
    $payload = [
        'user_id' => $this->studentUser->id,
        'lecture_id' => $this->lecture1->id,
        'ip' => '192.168.1.1',
        'expires_at' => now()->subMinutes(10)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken returns false for wrong lecture', function () {
    $payload = [
        'user_id' => $this->studentUser->id,
        'lecture_id' => $this->lecture2->id,
        'ip' => '192.168.1.1',
        'expires_at' => now()->addMinutes(5)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken returns false for wrong IP', function () {
    $payload = [
        'user_id' => $this->studentUser->id,
        'lecture_id' => $this->lecture1->id,
        'ip' => '10.0.0.1',
        'expires_at' => now()->addMinutes(5)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken returns false for inactive user', function () {
    $inactive = User::factory()->create(['status' => 'inactive']);

    $payload = [
        'user_id' => $inactive->id,
        'lecture_id' => $this->lecture1->id,
        'ip' => '192.168.1.1',
        'expires_at' => now()->addMinutes(5)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken returns false for corrupt token', function () {
    expect($this->service->validateToken('invalid-token-string', $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken returns false for non-array payload', function () {
    $token = Crypt::encrypt('not-an-array');
    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('isBlockedByExam returns false for admin', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->admin, $this->lecture2, 'video'))->toBeFalse();
});

it('isBlockedByExam returns true for student without student record', function () {
    $userNoStudent = User::factory()->create(['status' => 'active']);
    $userNoStudent->assignRole('student');

    expect($this->service->isBlockedByExam($userNoStudent, $this->lecture2, 'video'))->toBeTrue();
});

it('isBlockedByExam returns false when no blocking exams exist', function () {
    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeFalse();
});

it('isBlockedByExam returns true when exam not passed', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeTrue();
});

it('isBlockedByExam returns false when exam is passed', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'score' => 80,
        'submitted_at' => now(),
    ]);

    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeFalse();
});

it('isBlockedByExam does not block by its own exam for assignment type', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Same Lecture Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture1, 'assignment', $exam->id))->toBeFalse();
});

it('isBlockedByExam blocks assignment in later lecture by preceding exam', function () {
    $blockingExam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    $targetExam = Exam::create([
        'lecture_id' => $this->lecture2->id,
        'title' => 'Target Assignment',
        'duration' => 30,
        'is_blocking' => false,
        'is_assignment' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture2, 'assignment', $targetExam->id))->toBeTrue();
});

it('isBlockedByExam same section same lecture blocks video when exam precedes', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture2->id,
        'title' => 'Exam on same lecture',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => -1,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture2, 'video'))->toBeTrue();
});

it('isBlockedByExam does not block instructor for own course', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->instructor, $this->lecture2, 'video'))->toBeFalse();
});

it('isBlockedByExam blocks across sections', function () {
    $exam = Exam::create([
        'lecture_id' => $this->lecture1->id,
        'title' => 'Blocking Exam in Section 1',
        'duration' => 30,
        'is_blocking' => true,
        'sort_order' => 0,
        'pass_percentage' => 50,
    ]);

    expect($this->service->isBlockedByExam($this->studentUser, $this->lecture3, 'video'))->toBeTrue();
});
