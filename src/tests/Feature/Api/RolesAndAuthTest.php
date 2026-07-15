<?php

use App\Models\Course;
use App\Models\Student;
use App\Models\User;

function createInstructor(): User
{
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('instructor');
    return $user;
}

function createStudent(?User $user = null): array
{
    $user ??= User::factory()->create(['status' => 'active']);
    $user->assignRole('student');

    $student = Student::create([
        'user_id' => $user->id,
        'first_name' => 'Ahmed',
        'second_name' => 'Mohamed',
        'third_name' => 'Ali',
        'last_name' => 'Hussein',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    return ['user' => $user, 'student' => $student];
}

function createAssistant(?User $user = null): User
{
    $user ??= User::factory()->create(['status' => 'active']);
    $user->assignRole('assistant');
    return $user;
}

it('returns 401 for unauthenticated GET requests', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
    $this->getJson('/api/dashboard/student')->assertStatus(401);
    $this->getJson('/api/my-enrollments')->assertStatus(401);
    $this->getJson('/api/my-entitlements')->assertStatus(401);
    $this->getJson('/api/my-attempts')->assertStatus(401);
    $this->getJson('/api/products')->assertStatus(401);
    $this->getJson('/api/bundles')->assertStatus(401);
});

it('returns 401 for unauthenticated POST requests', function () {
    $this->postJson('/api/auth/logout')->assertStatus(401);
});

it('prevents student from creating a course', function () {
    $data = createStudent();

    $this->actingAs($data['user'])->postJson('/api/courses', [
        'title' => 'Hacked Course',
    ])->assertStatus(403);
});

it('prevents student from viewing instructor students list', function () {
    $data = createStudent();

    $this->actingAs($data['user'])->getJson('/api/instructor/students')
        ->assertStatus(403);
});

it('prevents assistant from deleting a course', function () {
    $instructor = createInstructor();
    $course = Course::create([
        'title' => 'Test Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $assistant = createAssistant();
    $course->assistants()->attach($assistant->id);

    $this->actingAs($assistant)->deleteJson("/api/courses/{$course->id}")
        ->assertStatus(403);
});

it('allows assigned assistant to access course lectures', function () {
    $instructor = createInstructor();
    $course = Course::create([
        'title' => 'Test Course',
        'description' => 'Test',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $section = $course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create([
        'title' => 'Lecture 1',
        'description' => 'Test lecture',
        'sort_order' => 1,
    ]);

    $assistant = createAssistant();
    $course->assistants()->attach($assistant->id);

    $this->actingAs($assistant)->getJson("/api/lectures/{$lecture->id}")
        ->assertOk();
});

it('prevents unassigned assistant from accessing lectures', function () {
    $instructor = createInstructor();
    $course = Course::create([
        'title' => 'Test Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $section = $course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create([
        'title' => 'Lecture 1',
        'description' => 'Test',
        'sort_order' => 1,
    ]);

    $assistant = createAssistant();

    $this->actingAs($assistant)->getJson("/api/lectures/{$lecture->id}")
        ->assertStatus(403);
});

it('allows instructor to manage own course', function () {
    $instructor = createInstructor();
    $course = Course::create([
        'title' => 'My Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $this->actingAs($instructor)->putJson("/api/courses/{$course->id}", [
        'title' => 'Updated Course',
        'description' => 'Updated description',
        'price' => 200,
    ])->assertOk();

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Updated Course']);
});

it('prevents instructor from managing other instructors course', function () {
    $instructor1 = createInstructor();
    $instructor2 = createInstructor();

    $course = Course::create([
        'title' => 'Instructor 1 Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $instructor1->id,
    ]);

    $this->actingAs($instructor2)->putJson("/api/courses/{$course->id}", [
        'title' => 'Hacked',
        'description' => 'Hacked description',
        'price' => 0,
    ])->assertStatus(403);
});

it('prevents student from viewing course enrollments', function () {
    $data = createStudent();

    $instructor = createInstructor();
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $this->actingAs($data['user'])->getJson("/api/courses/{$course->id}/enrollments")
        ->assertStatus(403);
});
