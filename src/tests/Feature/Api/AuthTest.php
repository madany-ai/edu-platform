<?php

use App\Models\GradeLevel;
use App\Models\Governorate;
use App\Models\User;

beforeEach(function () {
    $this->governorate = Governorate::create(['name' => 'القاهرة']);
    $this->gradeLevel = GradeLevel::create(['name' => 'الصف الثالث الثانوي', 'sort_order' => 3]);
});

function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Test',
        'second_name' => 'Second',
        'third_name' => 'Third',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Employee',
        'gender' => 'male',
        'birth_date' => '2000-01-01',
        'governorate_id' => test()->governorate->id,
        'grade_level_id' => test()->gradeLevel->id,
        'cf-turnstile-response' => 'dummy-token',
    ], $overrides);
}

it('registers a new student with pending status', function () {
    $response = $this->postJson('/api/auth/register', validRegistrationData());

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'status' => 'pending',
    ]);
});

it('creates user with pending status and student role', function () {
    $this->postJson('/api/auth/register', validRegistrationData());

    $user = User::where('email', 'test@example.com')->first();
    expect($user->status)->toBe(\App\Enums\UserStatus::Pending);
    expect($user->hasRole('student'))->toBeTrue();
});

it('rejects registration with existing email', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/auth/register', validRegistrationData());

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects registration with invalid data', function () {
    $response = $this->postJson('/api/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'first_name', 'email', 'password', 'second_name', 'third_name',
            'last_name', 'phone', 'father_phone', 'mother_phone',
            'guardian_job', 'gender', 'birth_date', 'governorate_id', 'grade_level_id',
        ]);
});

it('allows login with correct credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
        ]);
});

it('rejects login for pending user', function () {
    User::factory()->create([
        'email' => 'pending@test.com',
        'password' => bcrypt('password123'),
        'status' => 'pending',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'pending@test.com',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'حسابك قيد المراجعة. يرجى انتظار الموافقة من قبل الإدارة.',
        ]);
});

it('rejects login for rejected user', function () {
    User::factory()->create([
        'email' => 'rejected@test.com',
        'password' => bcrypt('password123'),
        'status' => 'rejected',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'rejected@test.com',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'لم يتم الموافقة على حسابك. يرجى التواصل مع الإدارة.',
        ]);
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'بيانات الدخول غير صحيحة.',
        ]);
});

it('allows authenticated user to access /me', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
});

it('rejects unauthenticated access to /me', function () {
    $this->getJson('/api/auth/me')
        ->assertStatus(401);
});

it('allows user to logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out']);
});

it('rejects unauthenticated logout', function () {
    $this->postJson('/api/auth/logout')
        ->assertStatus(401);
});
