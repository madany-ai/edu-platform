<?php

use App\Models\Bundle;
use App\Models\Course;
use App\Models\Entitlement;
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
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
});

it('order store creates pending order without entitlements', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 50,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('orders', [
        'student_id' => $this->student->id,
        'purchasable_id' => $product->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);
});

it('order store creates order with correct amount from product price', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Priced Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 7550,
        'is_active' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(201);

    $order = Order::where('student_id', $this->student->id)->first();

    expect($order->amount_cents)->toBe(755000)
        ->and($order->currency)->toBe('EGP')
        ->and($order->payment_method)->toBe('manual')
        ->and($order->transaction_id)->toStartWith('PENDING-');
});

it('order store does not set paid_at on creation', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(201);

    $order = Order::where('student_id', $this->student->id)->first();

    expect($order->paid_at)->toBeNull()
        ->and($order->status)->toBe('pending');
});

it('order store does not grant entitlements for bundle', function () {
    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Test Bundle',
        'price' => 100,
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Bundle Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 50,
        'is_active' => true,
    ]);

    $bundle->products()->attach($product->id);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $bundle->id,
        'purchasable_type' => 'bundle',
    ])->assertStatus(201);

    $this->assertDatabaseHas('orders', [
        'student_id' => $this->student->id,
        'purchasable_id' => $bundle->id,
        'purchasable_type' => Bundle::class,
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);
});

it('order store rejects order with missing purchasable_id', function () {
    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_type' => 'product',
    ])->assertStatus(422);
});

it('order store rejects order with missing purchasable_type', function () {
    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => 'some-id',
    ])->assertStatus(422);
});

it('order store rejects order with invalid purchasable_type', function () {
    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => 'some-id',
        'purchasable_type' => 'invalid',
    ])->assertStatus(422);
});

it('order store creates order for section product as pending', function () {
    $lecture2 = $this->section->lectures()->create(['title' => 'L2', 'sort_order' => 2]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Section Product',
        'sellable_id' => $this->section->id,
        'sellable_type' => \App\Models\CourseSection::class,
        'price' => 100,
        'is_active' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(201);

    $this->assertDatabaseHas('orders', [
        'student_id' => $this->student->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);
});

it('confirming pending order grants entitlements', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 50,
        'is_active' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(201);

    $order = Order::where('student_id', $this->student->id)->first();
    expect($order->status)->toBe('pending');

    $order->update([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    app(GrantEntitlementService::class)->handle($order);

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
    ]);

    $order->refresh();
    expect($order->status)->toBe('completed')
        ->and($order->paid_at)->not->toBeNull();
});
