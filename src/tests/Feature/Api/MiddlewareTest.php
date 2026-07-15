<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lecture;
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
    $this->lecture = $this->section->lectures()->create([
        'title' => 'L1',
        'description' => 'Content',
        'sort_order' => 1,
    ]);
});

it('CheckUserStatus blocks non-active user', function () {
    $pendingUser = User::factory()->create(['status' => 'pending']);
    $pendingUser->assignRole('student');

    $this->actingAs($pendingUser)->getJson('/api/auth/me')
        ->assertStatus(403)
        ->assertJson(['message' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
});

it('CheckUserStatus allows active user', function () {
    $this->actingAs($this->studentUser)->getJson('/api/auth/me')
        ->assertStatus(200);
});

it('CheckEnrollment blocks unenrolled student from lecture', function () {
    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertStatus(403);
});

it('CheckEnrollment allows enrolled student', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertOk();
});

it('CheckEnrollment allows course instructor', function () {
    $this->actingAs($this->instructor)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertOk();
});

it('CheckEnrollment allows super_admin', function () {
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole('super_admin');

    $this->actingAs($admin)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertOk();
});

it('CheckEnrollment allows assigned assistant', function () {
    $assistant = User::factory()->create(['status' => 'active']);
    $assistant->assignRole('assistant');
    $this->course->assistants()->attach($assistant->id);

    $this->actingAs($assistant)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertOk();
});

it('CheckEnrollment blocks unassigned assistant', function () {
    $assistant = User::factory()->create(['status' => 'active']);
    $assistant->assignRole('assistant');

    $this->actingAs($assistant)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertStatus(403);
});

it('CheckEnrollment blocks suspended enrollment', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'suspended',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertStatus(403);
});

it('CheckEnrollment allows student with valid entitlement', function () {
    $order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    \App\Models\Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertOk();
});

it('CheckEnrollment blocks student with expired entitlement', function () {
    $order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    \App\Models\Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture->id}")
        ->assertStatus(403);
});
