<?php

use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Entitlement;
use App\Models\Lecture;
use App\Models\Order;
use App\Models\Product;
use App\Models\Student;
use App\Models\User;
use App\Services\GrantEntitlementService;
use App\Services\VideoAccessService;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->studentUser = User::factory()->create(['status' => 'active']);
    $this->studentUser->assignRole('student');

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'first_name' => 'Test',
        'second_name' => 'Student',
        'third_name' => 'A',
        'last_name' => 'B',
        'phone' => '01011111111',
        'father_phone' => '01022222222',
        'mother_phone' => '01033333333',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);
});

it('can create a standalone lecture without section_id', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Standalone Chemistry Lecture',
        'description' => 'Single session lecture',
        'duration' => 60,
        'price' => 150.00,
        'status' => 'published',
    ]);

    expect($lecture->isStandalone())->toBeTrue();
    expect($lecture->section_id)->toBeNull();
    expect($lecture->instructor_id)->toBe($this->instructor->id);
    expect($lecture->resolveInstructorId())->toBe($this->instructor->id);
});

it('denies student access to standalone lecture without entitlement', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Standalone Lecture',
        'duration' => 60,
        'price' => 100.00,
        'status' => 'published',
    ]);

    $accessService = app(VideoAccessService::class);
    $hasAccess = $accessService->canAccess($this->studentUser, $lecture);

    expect($hasAccess)->toBeFalse();
});

it('grants student access to standalone lecture with valid entitlement', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Standalone Physics Lecture',
        'duration' => 90,
        'price' => 200.00,
        'status' => 'published',
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $lecture->id,
        'expires_at' => null,
    ]);

    $accessService = app(VideoAccessService::class);
    $hasAccess = $accessService->canAccess($this->studentUser, $lecture);

    expect($hasAccess)->toBeTrue();
});

it('denies access when standalone lecture entitlement expires', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Expired Standalone Lecture',
        'duration' => 45,
        'price' => 50.00,
        'status' => 'published',
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $lecture->id,
        'expires_at' => now()->subDay(),
    ]);

    $accessService = app(VideoAccessService::class);
    $hasAccess = $accessService->canAccess($this->studentUser, $lecture);

    expect($hasAccess)->toBeFalse();
});

it('purchasing standalone lecture product grants entitlement upon order completion', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Standalone Math Session',
        'duration' => 120,
        'price' => 150.00,
        'status' => 'published',
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'محاضرة: Standalone Math Session',
        'sellable_id' => $lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 150.00,
        'access_duration_days' => 30,
        'is_active' => true,
    ]);

    $order = Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $product->id,
        'purchasable_type' => Product::class,
        'amount_cents' => 15000,
        'currency' => 'EGP',
        'payment_method' => 'manual',
        'status' => 'pending',
    ]);

    // Grant entitlements via service
    $grantService = app(GrantEntitlementService::class);
    $grantService->handle($order);

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $lecture->id,
        'order_id' => $order->id,
    ]);

    $entitlement = Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $lecture->id)
        ->first();

    expect($entitlement->expires_at)->not->toBeNull();
});

it('standalone lecture in bundle grants entitlement upon bundle order completion', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Biology Revision Lecture',
        'duration' => 60,
        'price' => 100.00,
        'status' => 'published',
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'محاضرة: Biology Revision',
        'sellable_id' => $lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 100.00,
        'is_active' => true,
    ]);

    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Final Revision Bundle',
        'price' => 250.00,
    ]);
    $bundle->products()->attach($product->id);

    $order = Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $bundle->id,
        'purchasable_type' => Bundle::class,
        'amount_cents' => 25000,
        'currency' => 'EGP',
        'payment_method' => 'manual',
        'status' => 'pending',
    ]);

    $grantService = app(GrantEntitlementService::class);
    $grantService->handle($order);

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $lecture->id,
        'order_id' => $order->id,
    ]);
});

it('standalone-lectures endpoint returns active standalone lectures', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Public Standalone Lecture',
        'duration' => 60,
        'price' => 100.00,
        'status' => 'published',
    ]);

    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Public Standalone Lecture Product',
        'sellable_id' => $lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 100.00,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/standalone-lectures');

    $response->assertOk()
        ->assertJsonFragment(['title' => 'Public Standalone Lecture']);
});

it('isBlockedByExam returns false for standalone lecture', function () {
    $lecture = Lecture::create([
        'section_id' => null,
        'instructor_id' => $this->instructor->id,
        'title' => 'Standalone Lecture No Exams',
        'duration' => 60,
        'price' => 100.00,
        'status' => 'published',
    ]);

    $accessService = app(VideoAccessService::class);
    $isBlocked = $accessService->isBlockedByExam($this->studentUser, $lecture);

    expect($isBlocked)->toBeFalse();
});
