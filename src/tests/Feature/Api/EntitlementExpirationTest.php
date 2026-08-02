<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Entitlement;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->instructor = User::create([
        'name' => 'Instructor User',
        'email' => 'inst_exp@test.com',
        'password' => bcrypt('password'),
        'status' => \App\Enums\UserStatus::Active,
    ]);

    $this->studentUser = User::create([
        'name' => 'Student User',
        'email' => 'student_exp@test.com',
        'password' => bcrypt('password'),
        'status' => \App\Enums\UserStatus::Active,
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'first_name' => 'Sara',
        'second_name' => 'Ahmed',
        'third_name' => 'Mahmoud',
        'last_name' => 'Hassan',
        'phone' => '01011112222',
        'father_phone' => '01111112222',
        'mother_phone' => '01211112222',
        'guardian_job' => 'Doctor',
        'gender' => 'female',
        'birth_date' => '2002-05-15',
        'is_verified' => true,
    ]);

    $this->course = Course::create([
        'title' => 'Expiration Test Course',
        'description' => 'Course description',
        'price' => 100.00,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = CourseSection::create([
        'course_id' => $this->course->id,
        'title' => 'Section 1',
        'sort_order' => 1,
    ]);

    $this->lecture = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 1',
        'description' => 'Lecture 1 description',
        'duration' => 20,
        'sort_order' => 1,
        'status' => 'published',
    ]);
});

test('1.17 Expired entitlement is excluded from my-entitlements and blocked by CheckEnrollment', function () {
    // 1. Expired entitlement (yesterday)
    $expiredEntitlement = Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'expires_at' => now()->subDay(),
    ]);

    // GET /api/my-entitlements should not include expired entitlement
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/my-entitlements');

    $response->assertStatus(200);
    $response->assertJsonMissing(['id' => $expiredEntitlement->id]);

    // CheckEnrollment middleware should block access (403)
    $lectureResponse = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture->id}");

    $lectureResponse->assertStatus(403);
});

test('1.17 Active entitlement (future expires_at or null) is included and grants access', function () {
    // 2. Active entitlement (expires in 10 days)
    $activeEntitlement = Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'expires_at' => now()->addDays(10),
    ]);

    // GET /api/my-entitlements should include active entitlement
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/my-entitlements');

    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $activeEntitlement->id]);

    // CheckEnrollment middleware allows access
    $lectureResponse = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture->id}");

    $lectureResponse->assertStatus(200);
});
