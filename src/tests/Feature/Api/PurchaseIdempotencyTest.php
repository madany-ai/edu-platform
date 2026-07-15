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

    $this->product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Course Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 50,
        'is_active' => true,
    ]);
});

it('double click purchase creates two pending orders', function () {
    $response1 = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $response2 = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $orderCount = Order::where('student_id', $this->student->id)
        ->where('purchasable_id', $this->product->id)
        ->count();

    expect($orderCount)->toBe(2)
        ->and($response1->status())->toBe(201)
        ->and($response2->status())->toBe(201);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);
});

it('confirming order grants entitlements', function () {
    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $order = Order::where('student_id', $this->student->id)->first();
    $order->update(['status' => 'completed', 'paid_at' => now()]);

    app(GrantEntitlementService::class)->handle($order);

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'order_id' => $order->id,
    ]);
});

it('confirming second order updates entitlement expires_at', function () {
    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $firstOrder = Order::where('student_id', $this->student->id)->first();
    $firstOrder->update(['status' => 'completed', 'paid_at' => now()]);
    app(GrantEntitlementService::class)->handle($firstOrder);

    $firstEntitlement = Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $this->lecture->id)
        ->first();

    $firstExpiresAt = $firstEntitlement->expires_at;

    \Carbon\Carbon::setTestNow(now()->addDay());

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $secondOrder = Order::where('student_id', $this->student->id)
        ->where('purchasable_id', $this->product->id)
        ->latest()
        ->first();

    $secondOrder->update(['status' => 'completed', 'paid_at' => now()]);
    app(GrantEntitlementService::class)->handle($secondOrder);

    \Carbon\Carbon::setTestNow();

    $this->assertDatabaseHas('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'order_id' => $secondOrder->id,
    ]);
});

it('different products create separate pending orders', function () {
    $lecture2 = $this->section->lectures()->create(['title' => 'L2', 'sort_order' => 2]);

    $product2 = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 2 Product',
        'sellable_id' => $lecture2->id,
        'sellable_type' => \App\Models\Lecture::class,
        'price' => 30,
        'is_active' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product2->id,
        'purchasable_type' => 'product',
    ]);

    $orders = Order::where('student_id', $this->student->id)->get();

    expect($orders)->toHaveCount(2)
        ->and($orders->every(fn ($o) => $o->status === 'pending'))->toBeTrue();
});

it('bundle double purchase creates double pending orders', function () {
    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Test Bundle',
        'price' => 100,
    ]);

    $bundle->products()->attach($this->product->id);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $bundle->id,
        'purchasable_type' => 'bundle',
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $bundle->id,
        'purchasable_type' => 'bundle',
    ]);

    $orderCount = Order::where('student_id', $this->student->id)
        ->where('purchasable_id', $bundle->id)
        ->count();

    expect($orderCount)->toBe(2);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
    ]);
});

it('each order gets unique transaction_id', function () {
    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $this->product->id,
        'purchasable_type' => 'product',
    ]);

    $transactions = Order::where('student_id', $this->student->id)
        ->pluck('transaction_id')
        ->toArray();

    expect(count($transactions))->toBe(2)
        ->and($transactions[0])->not->toBe($transactions[1]);
});

it('concurrent-like rapid purchases still create separate pending orders', function () {
    $responses = [];
    for ($i = 0; $i < 3; $i++) {
        $responses[] = $this->actingAs($this->studentUser)->postJson('/api/orders', [
            'purchasable_id' => $this->product->id,
            'purchasable_type' => 'product',
        ]);
    }

    $orderCount = Order::where('student_id', $this->student->id)->count();

    expect($orderCount)->toBe(3);

    foreach ($responses as $response) {
        $response->assertStatus(201);
    }
});
