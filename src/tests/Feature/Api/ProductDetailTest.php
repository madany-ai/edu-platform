<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->studentUser = User::factory()->create(['status' => 'active']);
    $this->studentUser->assignRole('student');

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

it('show product with course sellable type loads sections and lectures', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Course Product',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.sellable.title', 'Course');

    expect($response->json('data.sellable.sections'))->not->toBeNull()
        ->and($response->json('data.sellable.sections.0.lectures'))->not->toBeNull();
});

it('show product with section sellable type loads lectures', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Section Product',
        'sellable_id' => $this->section->id,
        'sellable_type' => CourseSection::class,
        'price' => 50,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.sellable.title', 'S1');

    expect($response->json('data.sellable.lectures'))->not->toBeNull()
        ->and($response->json('data.sellable.lectures'))->toHaveCount(1);
});

it('show product with lecture sellable type loads sellable', function () {
    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 20,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.sellable.title', 'L1');
});

it('product index filters by valid type', function () {
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Course Product',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Lecture Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 20,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/products?type=course');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Course Product');
});

it('product index returns all active products for invalid type filter', function () {
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Product 1',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Product 2',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 20,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/products?type=invalid_type');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('product index only returns active products', function () {
    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Active Product',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Inactive Product',
        'sellable_id' => $this->lecture->id,
        'sellable_type' => Lecture::class,
        'price' => 20,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/products');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toContain('Active Product')
        ->and($names)->not->toContain('Inactive Product');
});

it('bundles endpoint returns bundles with products', function () {
    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Test Bundle',
        'price' => 150,
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Bundled Product',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    $bundle->products()->attach($product->id);

    $response = $this->actingAs($this->studentUser)->getJson('/api/bundles');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    $bundleData = $response->json('data.0');
    expect($bundleData['name'])->toBe('Test Bundle')
        ->and($bundleData['products'])->toHaveCount(1);
});

it('showBundle returns bundle with products', function () {
    $bundle = Bundle::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Detail Bundle',
        'price' => 200,
    ]);

    $product = Product::create([
        'instructor_id' => $this->instructor->id,
        'name' => 'Bundle Product',
        'sellable_id' => $this->course->id,
        'sellable_type' => Course::class,
        'price' => 100,
        'is_active' => true,
    ]);

    $bundle->products()->attach($product->id);

    $response = $this->actingAs($this->studentUser)->getJson("/api/bundles/{$bundle->id}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'Detail Bundle')
        ->assertJsonCount(1, 'data.products');
});

it('product index returns empty when no active products', function () {
    $response = $this->actingAs($this->studentUser)->getJson('/api/products');

    $response->assertOk()
        ->assertJson(['data' => []]);
});
