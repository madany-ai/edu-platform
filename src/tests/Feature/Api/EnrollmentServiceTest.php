<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Entitlement;
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

    $this->service = app(\App\Services\EnrollmentService::class);
});

it('getStudentEnrollments returns synthetic enrollment for entitlement-only student', function () {
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
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $enrollments = $this->service->getStudentEnrollments($this->studentUser->id);

    $fakeEnrollment = $enrollments->first(fn($e) => str_starts_with($e->id ?? '', 'entitlement-fake-'));
    expect($fakeEnrollment)->not->toBeNull()
        ->and($fakeEnrollment->course_id)->toBe($this->course->id)
        ->and($fakeEnrollment->status)->toBe('active')
        ->and($fakeEnrollment->source)->toBe('purchase');
});

it('getStudentEnrollments does not duplicate synthetic when real enrollment exists', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

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
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $enrollments = $this->service->getStudentEnrollments($this->studentUser->id);

    $fakeEnrollments = $enrollments->filter(fn($e) => str_starts_with($e->id ?? '', 'entitlement-fake-'));
    expect($fakeEnrollments)->toHaveCount(0);
    expect($enrollments)->toHaveCount(1);
});

it('getStudentEnrollments returns empty collection for user without student', function () {
    $userNoStudent = User::factory()->create(['status' => 'active']);
    $userNoStudent->assignRole('student');

    $enrollments = $this->service->getStudentEnrollments($userNoStudent->id);

    expect($enrollments)->toBeEmpty();
});

it('revokeEnrollment returns false for non-existent enrollment', function () {
    $result = $this->service->revokeEnrollment($this->course, $this->student);

    expect($result)->toBeFalse();
});

it('revokeEnrollment returns true and updates status', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $result = $this->service->revokeEnrollment($this->course, $this->student);

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'suspended',
    ]);
});

it('isEnrolled returns true for active enrollment', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    expect($this->service->isEnrolled($this->studentUser->id, $this->course->id))->toBeTrue();
});

it('isEnrolled returns false for suspended enrollment', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'suspended',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    expect($this->service->isEnrolled($this->studentUser->id, $this->course->id))->toBeFalse();
});

it('isEnrolled returns false for user without student', function () {
    $userNoStudent = User::factory()->create(['status' => 'active']);
    $userNoStudent->assignRole('student');

    expect($this->service->isEnrolled($userNoStudent->id, $this->course->id))->toBeFalse();
});

it('getStudentEntitlements returns empty for user without student', function () {
    $userNoStudent = User::factory()->create(['status' => 'active']);
    $userNoStudent->assignRole('student');

    $entitlements = $this->service->getStudentEntitlements($userNoStudent->id);

    expect($entitlements)->toBeEmpty();
});

it('getStudentEntitlements returns entitlements for student', function () {
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
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
    ]);

    $entitlements = $this->service->getStudentEntitlements($this->studentUser->id);

    expect($entitlements)->toHaveCount(1)
        ->and($entitlements->first()->lecture_id)->toBe($this->lecture->id);
});
