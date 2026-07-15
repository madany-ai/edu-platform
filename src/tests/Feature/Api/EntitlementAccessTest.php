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
        'title' => 'Math Course',
        'description' => 'Advanced Math',
        'price' => 100.00,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = CourseSection::create([
        'course_id' => $this->course->id,
        'title' => 'Month 1',
        'sort_order' => 1,
    ]);

    $this->lecture1 = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 1',
        'description' => 'First lecture',
        'duration' => 30,
        'sort_order' => 1,
    ]);

    $this->lecture2 = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 2',
        'description' => 'Second lecture',
        'duration' => 45,
        'sort_order' => 2,
    ]);
});

it('denies access with expired entitlement', function () {
    $order = Order::create([
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
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture1->id}")
        ->assertStatus(403);
});

it('grants access with valid entitlement', function () {
    $order = Order::create([
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

    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture1->id}")
        ->assertOk();
});

it('denies access without entitlement', function () {
    $this->actingAs($this->studentUser)->getJson("/api/lectures/{$this->lecture1->id}")
        ->assertStatus(403);
});

it('creates pending order via API, entitlements require confirmation', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1 Access',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10.00,
        'access_duration_days' => 30,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ]);

    $response->assertStatus(201)
        ->assertJson(['status' => 'success']);

    $order = Order::where('student_id', $this->student->id)->first();
    expect($order->status)->toBe('pending');

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
    ]);

    $order->update(['status' => 'completed', 'paid_at' => now()]);
    app(GrantEntitlementService::class)->handle($order);

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
    ]);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture2->id,
    ]);
});

it('creates pending order for bundle, entitlements require confirmation', function () {
    $product1 = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10.00,
        'access_duration_days' => 15,
    ]);

    $product2 = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 2',
        'sellable_id' => $this->lecture2->id,
        'sellable_type' => Lecture::class,
        'price' => 10.00,
        'access_duration_days' => 30,
    ]);

    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Both Lectures',
        'price' => 15.00,
    ]);
    $bundle->products()->attach([$product1->id, $product2->id]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $bundle->id,
        'purchasable_type' => 'bundle',
    ])->assertStatus(201);

    $order = Order::where('student_id', $this->student->id)->first();
    expect($order->status)->toBe('pending');

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
    ]);

    $order->update(['status' => 'completed', 'paid_at' => now()]);
    app(GrantEntitlementService::class)->handle($order);

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
    ]);
    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture2->id,
    ]);
});

it('rejects purchase from unverified student', function () {
    $unverifiedStudentUser = User::factory()->create(['status' => 'active']);
    $unverifiedStudentUser->assignRole('student');

    Student::create([
        'user_id' => $unverifiedStudentUser->id,
        'first_name' => 'Unverified',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'Student',
        'phone' => '01000000001',
        'father_phone' => '01100000001',
        'mother_phone' => '01200000001',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
        'is_verified' => false,
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10.00,
        'access_duration_days' => 30,
    ]);

    $this->actingAs($unverifiedStudentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(403);
});

it('returns entitlements via my-entitlements endpoint', function () {
    $order = Order::create([
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

    $this->actingAs($this->studentUser)->getJson('/api/my-entitlements')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('sets correct expires_at from product access_duration_days', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1 Access',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10.00,
        'access_duration_days' => 60,
    ]);

    $order = Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $product->id,
        'purchasable_type' => Product::class,
        'amount_cents' => 1000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    (new GrantEntitlementService())->handle($order);

    $entitlement = Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $this->lecture1->id)
        ->first();

    expect($entitlement)->not->toBeNull();
    expect((int) round(now()->diffInDays($entitlement->expires_at)))->toBe(60);
});

it('grants access to free course via enrollment', function () {
    $freeCourse = Course::create([
        'title' => 'Free Course',
        'description' => 'Free',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $freeSection = $freeCourse->sections()->create(['title' => 'Free Section', 'sort_order' => 1]);
    $freeLecture = $freeSection->lectures()->create([
        'title' => 'Free Lecture',
        'description' => 'Free',
        'sort_order' => 1,
    ]);

    \App\Models\Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $freeCourse->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $accessService = app(\App\Services\VideoAccessService::class);
    expect($accessService->canAccess($this->studentUser, $freeLecture))->toBeTrue();
});
