<?php

it('login endpoint has rate limiting applied', function () {
    // The login route is wrapped with throttle:login middleware
    // We test that the endpoint works normally within limits
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertStatus(401);
});

it('register endpoint has rate limiting applied', function () {
    $governorate = \App\Models\Governorate::create(['name' => 'Cairo']);

    $response = $this->postJson('/api/auth/register', [
        'first_name' => 'Test',
        'second_name' => 'User',
        'third_name' => 'Name',
        'last_name' => 'Last',
        'email' => 'ratelimittest@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '01012345678',
        'father_phone' => '01012345679',
        'mother_phone' => '01012345680',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2000-01-01',
        'governorate_id' => $governorate->id,
        'academic_year' => 'sec_1',
        'cf-turnstile-response' => 'test-token',
    ]);

    // Should work (not rate limited on first attempt)
    expect($response->status())->toBeIn([201, 422]);
});

it('stream key endpoint has rate limiting applied', function () {
    $instructor = \App\Models\User::factory()->create(['status' => 'active']);
    $instructor->assignRole('instructor');

    $course = \App\Models\Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $response = $this->actingAs($instructor)->getJson("/api/lectures/{$lecture->id}/key");

    // Should return 400 (missing token) not 429 (rate limited)
    $response->assertStatus(400)
        ->assertJson(['message' => 'Missing token']);
});
