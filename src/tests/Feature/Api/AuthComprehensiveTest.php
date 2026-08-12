<?php

use App\Models\Governorate;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->governorate = Governorate::create(['name' => 'القاهرة']);
});

function regData(array $overrides = []): array
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
        'academic_year' => 'sec_3',
        'cf-turnstile-response' => 'dummy-token',
    ], $overrides);
}

it('creates student record with correct fields on registration', function () {
    $this->postJson('/api/auth/register', regData([
        'phone' => '01555555555',
        'gender' => 'female',
    ]))->assertStatus(201);

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    $student = Student::where('user_id', $user->id)->first();
    expect($student)->not->toBeNull();
    expect($student->phone)->toBe('01555555555');
    expect($student->gender)->toBe('female');
    expect($student->first_name)->toBe('Test');
    expect($student->last_name)->toBe('User');
    expect($student->student_code)->not->toBeNull();
});

it('rejects registration with short password', function () {
    $response = $this->postJson('/api/auth/register', regData([
        'password' => 'short',
        'password_confirmation' => 'short',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects registration with mismatched password confirmation', function () {
    $response = $this->postJson('/api/auth/register', regData([
        'password_confirmation' => 'different-password',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects registration with invalid email format', function () {
    $response = $this->postJson('/api/auth/register', regData([
        'email' => 'not-an-email',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects registration with invalid gender', function () {
    $response = $this->postJson('/api/auth/register', regData([
        'gender' => 'other',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['gender']);
});

it('rejects registration with non-existent governorate', function () {
    $response = $this->postJson('/api/auth/register', regData([
        'governorate_id' => '00000000-0000-0000-0000-000000000000',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['governorate_id']);
});

it('rejects registration with non-existent grade level', function () {
    $response = $this->postJson('/api/auth/register', regData([
        'academic_year' => 'invalid_year',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['academic_year']);
});

it('login by student_code', function () {
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');

    $student = Student::create([
        'user_id' => $user->id,
        'student_code' => 'ST30001',
        'first_name' => 'Ahmed',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'Ali',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'ST30001',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
});

it('login by phone number', function () {
    $user = User::factory()->create([
        'email' => 'phoneuser@test.com',
        'password' => bcrypt('password123'),
        'status' => 'active',
        'phone' => '01099999999',
    ]);
    $user->assignRole('student');

    $response = $this->postJson('/api/auth/login', [
        'email' => '01099999999',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
});

it('login by student phone', function () {
    $user = User::factory()->create([
        'email' => 'studentphone@test.com',
        'password' => bcrypt('password123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');

    Student::create([
        'user_id' => $user->id,
        'first_name' => 'Test',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'User',
        'phone' => '01088888888',
        'father_phone' => '01188888888',
        'mother_phone' => '01288888888',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => '01088888888',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
});

it('returns 401 for non-existent email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(401);
});

it('me endpoint returns roles array for student', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('student');

    Student::create([
        'user_id' => $user->id,
        'first_name' => 'Test',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'User',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $response = $this->actingAs($user)->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id', 'name', 'email', 'status', 'roles', 'student',
        ])
        ->assertJsonPath('roles.0', 'student');
});

it('me endpoint returns null student for instructor', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('instructor');

    $response = $this->actingAs($user)->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonFragment(['roles' => ['instructor']])
        ->assertJson(['student' => null]);
});

it('logout deletes current token', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/auth/logout')
        ->assertStatus(200);
});

it('registration sends notification to instructors', function () {
    $instructor = User::factory()->create(['status' => 'active']);
    $instructor->assignRole('instructor');

    $this->postJson('/api/auth/register', regData([
        'email' => 'newstudent@test.com',
    ]))->assertStatus(201);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $instructor->id,
        'title' => 'تسجيل طالب جديد',
    ]);
});

it('login updates last_login_at', function () {
    $user = User::factory()->create([
        'email' => 'logintest@test.com',
        'password' => bcrypt('password123'),
        'status' => 'active',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'logintest@test.com',
        'password' => 'password123',
        'cf-turnstile-response' => 'dummy-token',
    ])->assertStatus(200);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});
