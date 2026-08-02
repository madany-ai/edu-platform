<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Order;
use App\Models\Product;
use App\Models\Student;
use App\Models\User;
use App\Models\Entitlement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->instructor = User::create([
        'name' => 'Instructor User',
        'email' => 'instructor_p1@test.com',
        'password' => bcrypt('password'),
        'status' => \App\Enums\UserStatus::Active,
    ]);

    $this->studentUser = User::create([
        'name' => 'Student User',
        'email' => 'student_p1@test.com',
        'password' => bcrypt('password'),
        'status' => \App\Enums\UserStatus::Active,
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'first_name' => 'Ahmed',
        'second_name' => 'Ali',
        'third_name' => 'Hassan',
        'last_name' => 'Ibrahim',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2000-01-01',
        'is_verified' => true,
    ]);

    $this->course = Course::create([
        'title' => 'Phase 1 Test Course',
        'description' => 'Test Description',
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
        'description' => 'Lecture 1 Description',
        'duration' => 15,
        'sort_order' => 1,
        'status' => 'published',
    ]);

    $this->product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1 Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 50.00,
        'is_active' => true,
    ]);
});

test('1.1 streamKey endpoint requires authentication and access check', function () {
    // Guest gets 401
    $response = $this->getJson("/api/lectures/{$this->lecture->id}/key?token=invalid");
    $response->assertStatus(401);

    // Authenticated without entitlement/enrollment gets 403
    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture->id}/key?token=invalid");
    $response->assertStatus(403);
});

test('1.4 ProductResource does not leak raw video_path or secrets', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/products/{$this->product->id}");

    $response->assertStatus(200);
    $response->assertJsonMissing(['video_path', 'bunny_video_id', 'encryption_key']);
    $response->assertJsonStructure([
        'status',
        'data' => ['id', 'name', 'price', 'sellable_type', 'sellable'],
    ]);
});

test('1.6 VideoStreamController rejects non-UUID videoId to prevent SSRF', function () {
    $response = $this->getJson('/api/video/invalid-video-id-12345/playlist?token=abc');
    $response->assertStatus(400);

    $response = $this->getJson('/api/video/invalid-video-id-12345/segment?token=abc&file=test.ts');
    $response->assertStatus(400);
});

test('1.7 CheckEnrollment blocks non-instructors from accessing draft course lectures', function () {
    $draftCourse = Course::create([
        'title' => 'Draft Course',
        'description' => 'Draft Course Description',
        'status' => 'draft',
        'instructor_id' => $this->instructor->id,
    ]);

    $draftSection = CourseSection::create([
        'course_id' => $draftCourse->id,
        'title' => 'Draft Section',
    ]);

    $draftLecture = Lecture::create([
        'section_id' => $draftSection->id,
        'title' => 'Draft Lecture',
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$draftLecture->id}");

    $response->assertStatus(403);
});

test('1.8 downloadFile rejects disallowed file extensions', function () {
    $file = \App\Models\LectureFile::create([
        'lecture_id' => $this->lecture->id,
        'type' => 'document',
        'file_path' => 'lectures/files/script.sh',
    ]);

    // Give student entitlement first so middleware passes
    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/lectures/{$this->lecture->id}/files/{$file->id}");

    $response->assertStatus(403);
});

test('1.9 Student can fetch list of orders via GET /api/orders without leaking transaction_id', function () {
    Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->product->id,
        'purchasable_type' => Product::class,
        'amount_cents' => 5000,
        'currency' => 'EGP',
        'payment_method' => 'manual',
        'transaction_id' => 'SECRET_TX_123',
        'status' => 'pending',
        'idempotency_key' => 'IDEMP_TEST_1',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/orders');

    $response->assertStatus(200);
    $response->assertJsonMissing(['SECRET_TX_123', 'idempotency_key']);
    $response->assertJsonStructure([
        'status',
        'data' => [
            'data' => [
                '*' => ['id', 'purchasable_type', 'purchasable_name', 'amount', 'currency', 'status'],
            ]
        ]
    ]);
});

test('1.10 Order creation prevents purchasing already owned content', function () {
    // Give entitlement
    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/orders', [
            'purchasable_id' => $this->product->id,
            'purchasable_type' => 'product',
        ]);

    $response->assertStatus(409);
});
