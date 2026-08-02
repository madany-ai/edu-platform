<?php

use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Order;
use App\Models\Product;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    \Illuminate\Support\Facades\Http::fake([
        'https://accept.paymob.com/*' => \Illuminate\Support\Facades\Http::response([
            'token' => 'fake_token',
            'id' => 12345,
        ], 201),
    ]);

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
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = CourseSection::create([
        'course_id' => $this->course->id,
        'title' => 'Section 1',
        'sort_order' => 1,
    ]);

    $this->lecture1 = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 1',
        'description' => 'Content',
        'duration' => 30,
        'sort_order' => 1,
    ]);

    $this->lecture2 = Lecture::create([
        'section_id' => $this->section->id,
        'title' => 'Lecture 2',
        'description' => 'Content',
        'duration' => 45,
        'sort_order' => 2,
    ]);
});

it('lists active products', function () {
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Active Product',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Inactive Product',
        'sellable_id' => $this->lecture2->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/products');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toContain('Active Product');
    expect($names)->not->toContain('Inactive Product');
});

it('filters products by type', function () {
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Product',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Course Product',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/products?type=lecture');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toContain('Lecture Product');
    expect($names)->not->toContain('Course Product');
});

it('shows product with sellable relation', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Access',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'Lecture Access');
});

it('lists bundles with products', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1 Product',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);

    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Math Bundle',
        'price' => 15,
    ]);
    $bundle->products()->attach($product->id);

    $response = $this->actingAs($this->studentUser)->getJson('/api/bundles');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows bundle detail with products', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1 Product',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);

    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Math Bundle',
        'price' => 15,
    ]);
    $bundle->products()->attach($product->id);

    $response = $this->actingAs($this->studentUser)->getJson("/api/bundles/{$bundle->id}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'Math Bundle');
});

it('verified student can order a product', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Access',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'access_duration_days' => 30,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ]);

    $response->assertStatus(201)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('orders', [
        'student_id' => $this->student->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
    ]);
});

it('verified student can order a bundle', function () {
    $product1 = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 1',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'access_duration_days' => 15,
        'is_active' => true,
    ]);
    $product2 = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture 2',
        'sellable_id' => $this->lecture2->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'access_duration_days' => 30,
        'is_active' => true,
    ]);

    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Bundle',
        'price' => 15,
    ]);
    $bundle->products()->attach([$product1->id, $product2->id]);

    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => $bundle->id,
        'purchasable_type' => 'bundle',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('orders', [
        'student_id' => $this->student->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
    ]);
    $this->assertDatabaseMissing('entitlements', [
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture2->id,
    ]);
});

it('rejects purchase from unverified student', function () {
    $unverifiedUser = User::factory()->create(['status' => 'active']);
    $unverifiedUser->assignRole('student');

    Student::create([
        'user_id' => $unverifiedUser->id,
        'first_name' => 'Unverified',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'Student',
        'phone' => '01000000001',
        'father_phone' => '01100000001',
        'mother_phone' => '01200000001',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
        'is_verified' => false,
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Product',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($unverifiedUser)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(403);
});

it('rejects purchase from user without student record', function () {
    $userWithoutStudent = User::factory()->create(['status' => 'active']);
    $userWithoutStudent->assignRole('student');

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Product',
        'sellable_id' => $this->lecture1->id,
        'sellable_type' => Lecture::class,
        'price' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($userWithoutStudent)->postJson('/api/orders', [
        'purchasable_id' => $product->id,
        'purchasable_type' => 'product',
    ])->assertStatus(404);
});

it('rejects purchase of non-existent product', function () {
    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => '00000000-0000-0000-0000-000000000000',
        'purchasable_type' => 'product',
    ]);

    $response->assertStatus(404)
        ->assertJson(['message' => 'المحتوى المطلوب غير موجود.']);
});

it('rejects order with missing purchasable_type', function () {
    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => 'some-id',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['purchasable_type']);
});

it('rejects order with invalid purchasable_type', function () {
    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => 'some-id',
        'purchasable_type' => 'invalid',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['purchasable_type']);
});

it('rejects purchase of non-existent bundle', function () {
    $response = $this->actingAs($this->studentUser)->postJson('/api/orders', [
        'purchasable_id' => '00000000-0000-0000-0000-000000000000',
        'purchasable_type' => 'bundle',
    ]);

    $response->assertStatus(404)
        ->assertJson(['message' => 'المحتوى المطلوب غير موجود.']);
});
