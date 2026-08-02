<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Entitlement;
use App\Models\Lecture;
use App\Models\Order;
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
        'is_verified' => true,
    ]);

    $this->freeCourse = Course::create([
        'title' => 'Free Course',
        'description' => 'Free',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->paidCourse = Course::create([
        'title' => 'Paid Course',
        'description' => 'Paid',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->freeCourse->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
});

it('lists my-enrollments for enrolled student', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/my-enrollments');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('my-enrollments returns empty for student with no enrollments', function () {
    $response = $this->actingAs($this->studentUser)->getJson('/api/my-enrollments');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('enrolls in free course', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/courses/{$this->freeCourse->id}/enroll");

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'status']);

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $this->student->id,
        'course_id' => $this->freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
    ]);
});

it('enrollment is idempotent (firstOrCreate)', function () {
    $this->actingAs($this->studentUser)
        ->postJson("/api/courses/{$this->freeCourse->id}/enroll")
        ->assertStatus(201);

    $this->actingAs($this->studentUser)
        ->postJson("/api/courses/{$this->freeCourse->id}/enroll")
        ->assertStatus(201);

    $count = Enrollment::where('student_id', $this->student->id)
        ->where('course_id', $this->freeCourse->id)
        ->count();
    expect($count)->toBe(1);
});

it('rejects direct purchase for paid course via course purchase endpoint', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/courses/{$this->paidCourse->id}/purchase");

    $response->assertStatus(403);
});

it('instructor can revoke enrollment', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->instructor)
        ->deleteJson("/api/courses/{$this->freeCourse->id}/enrollments/{$this->student->id}");

    $response->assertOk();

    $enrollment = Enrollment::where('student_id', $this->student->id)
        ->where('course_id', $this->freeCourse->id)
        ->first();
    expect($enrollment->status)->toBe(\App\Enums\EnrollmentStatus::Suspended);
});

it('student cannot revoke enrollment', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $this->actingAs($this->studentUser)
        ->deleteJson("/api/courses/{$this->freeCourse->id}/enrollments/{$this->student->id}")
        ->assertStatus(403);
});

it('instructor can view course enrollments', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->instructor)
        ->getJson("/api/courses/{$this->freeCourse->id}/enrollments");

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('student cannot view course enrollments', function () {
    $this->actingAs($this->studentUser)
        ->getJson("/api/courses/{$this->freeCourse->id}/enrollments")
        ->assertStatus(403);
});

it('my-entitlements returns entitlements list', function () {
    $order = Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->freeCourse->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 0,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($this->studentUser)
        ->getJson('/api/my-entitlements')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('my-entitlements returns empty for student without entitlements', function () {
    $this->actingAs($this->studentUser)
        ->getJson('/api/my-entitlements')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('enrollment requires authenticated user', function () {
    $this->postJson("/api/courses/{$this->freeCourse->id}/enroll")
        ->assertStatus(401);
});
