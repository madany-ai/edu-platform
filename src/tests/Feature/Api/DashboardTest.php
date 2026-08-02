<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentActivity;
use App\Models\StudentStatistic;
use App\Models\User;

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
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
});

it('returns student dashboard stats', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->studentUser)->getJson('/api/dashboard/student');

    $response->assertOk()
        ->assertJsonStructure([
            'enrollments_count',
            'active_enrollments',
            'completed_lectures',
            'total_watch_minutes',
        ]);
});

it('returns zero stats for student without enrollments', function () {
    $response = $this->actingAs($this->studentUser)->getJson('/api/dashboard/student');

    $response->assertOk()
        ->assertJson([
            'enrollments_count' => 0,
            'active_enrollments' => 0,
            'completed_lectures' => 0,
            'total_watch_minutes' => 0,
        ]);
});

it('returns student dashboard without student record', function () {
    $userWithoutStudent = User::factory()->create(['status' => 'active']);
    $userWithoutStudent->assignRole('student');

    $response = $this->actingAs($userWithoutStudent)->getJson('/api/dashboard/student');

    $response->assertOk()
        ->assertJson([
            'enrollments_count' => 0,
            'active_enrollments' => 0,
            'completed_lectures' => 0,
            'total_watch_minutes' => 0,
        ]);
});

it('returns instructor dashboard stats', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor');

    $response->assertOk()
        ->assertJsonStructure([
            'courses' => ['total', 'published', 'draft'],
            'students' => ['total', 'active', 'recent_enrollments'],
            'revenue' => ['total'],
            'content' => ['total_lectures'],
            'pending_enrollments',
        ]);

    expect($response->json('courses.total'))->toBe(1);
    expect($response->json('students.total'))->toBe(1);
});

it('returns instructor courses', function () {
    Course::create([
        'title' => 'My Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor/courses');

    // 1 from beforeEach + 1 created above = 2
    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns instructor recent enrollments', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor/recent-enrollments');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns instructor course performance', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor/course-performance');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.title', 'Math Course');
});

it('returns instructor notifications', function () {
    Notification::create([
        'user_id' => $this->instructor->id,
        'title' => 'New enrollment',
        'body' => 'Student enrolled',
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor/notifications');

    $response->assertOk()
        ->assertJsonCount(1);
});

it('returns instructor students', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/instructor/students');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('prevents student from accessing instructor students endpoint', function () {
    $this->actingAs($this->studentUser)->getJson('/api/instructor/students')
        ->assertStatus(403);
});

it('prevents student from accessing instructor dashboard', function () {
    $this->actingAs($this->studentUser)->getJson('/api/dashboard/instructor')
        ->assertStatus(403);
});

it('prevents student from accessing instructor courses dashboard', function () {
    $this->actingAs($this->studentUser)->getJson('/api/dashboard/instructor/courses')
        ->assertStatus(403);
});

it('prevents student from accessing instructor recent enrollments', function () {
    $this->actingAs($this->studentUser)->getJson('/api/dashboard/instructor/recent-enrollments')
        ->assertStatus(403);
});

it('prevents student from accessing instructor course performance', function () {
    $this->actingAs($this->studentUser)->getJson('/api/dashboard/instructor/course-performance')
        ->assertStatus(403);
});

it('prevents student from accessing instructor notifications', function () {
    $this->actingAs($this->studentUser)->getJson('/api/dashboard/instructor/notifications')
        ->assertStatus(403);
});
