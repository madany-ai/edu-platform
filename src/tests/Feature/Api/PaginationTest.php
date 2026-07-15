<?php

use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');
});

it('course listing paginates with default 12 per page', function () {
    // Create 15 published courses
    for ($i = 0; $i < 15; $i++) {
        Course::create([
            'title' => "Course {$i}",
            'description' => "Description {$i}",
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    $response = $this->getJson('/api/courses');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(12);

    // Check pagination metadata
    $meta = $response->json('meta');
    expect($meta)->not->toBeNull()
        ->and($meta['current_page'])->toBe(1)
        ->and($meta['per_page'])->toBe(12)
        ->and($meta['total'])->toBe(15)
        ->and($meta['last_page'])->toBe(2);
});

it('course listing page 2 returns remaining courses', function () {
    for ($i = 0; $i < 15; $i++) {
        Course::create([
            'title' => "Page Course {$i}",
            'description' => "Desc {$i}",
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    $response = $this->getJson('/api/courses?page=2');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(3);

    expect($response->json('meta.current_page'))->toBe(2);
});

it('course listing page beyond last page returns empty data', function () {
    Course::create([
        'title' => 'Only Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->getJson('/api/courses?page=999');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

it('course listing with search returns filtered results', function () {
    Course::create([
        'title' => 'Advanced Mathematics',
        'description' => 'Algebra and Calculus',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    Course::create([
        'title' => 'Physics 101',
        'description' => 'Mechanics and Thermodynamics',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->getJson('/api/courses?search=Math');

    $response->assertOk();

    $titles = collect($response->json('data'))->pluck('title')->toArray();
    expect($titles)->toContain('Advanced Mathematics')
        ->and($titles)->not->toContain('Physics 101');
});

it('course listing returns empty array when no published courses', function () {
    $response = $this->getJson('/api/courses');

    $response->assertOk()
        ->assertJson(['data' => []]);
});

it('instructor dashboard courses returns only own courses', function () {
    $otherInstructor = User::factory()->create(['status' => 'active']);
    $otherInstructor->assignRole('instructor');

    for ($i = 0; $i < 5; $i++) {
        Course::create([
            'title' => "My Course {$i}",
            'description' => "Desc",
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    Course::create([
        'title' => 'Other Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $otherInstructor->id,
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor/courses');

    $response->assertOk()
        ->assertJsonCount(5, 'data');

    $titles = collect($response->json('data'))->pluck('title')->toArray();
    expect($titles)->not->toContain('Other Course');
});
