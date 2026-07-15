<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lecture;
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
    ]);

    $this->course = Course::create([
        'title' => 'Math Course',
        'description' => 'Math',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create([
        'title' => 'L1',
        'description' => 'Content',
        'duration' => 30,
        'sort_order' => 1,
    ]);

    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);
});

it('updates lecture progress', function () {
    $response = $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 120,
        'is_completed' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('progress.current_time', 120)
        ->assertJsonPath('progress.is_completed', false);
});

it('progress is idempotent via updateOrCreate', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 60,
        'is_completed' => false,
    ]);

    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 120,
        'is_completed' => true,
    ]);

    $count = StudentActivity::where('student_id', $this->student->id)
        ->where('type', 'video_progress')
        ->where('entity_id', $this->lecture->id)
        ->count();
    expect($count)->toBe(1);

    $activity = StudentActivity::where('student_id', $this->student->id)
        ->where('type', 'video_progress')
        ->where('entity_id', $this->lecture->id)
        ->first();
    expect($activity->metadata['current_time'])->toBe(120);
    expect($activity->metadata['is_completed'])->toBeTrue();
});

it('completion creates video_completed activity', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 300,
        'is_completed' => true,
    ]);

    $this->assertDatabaseHas('student_activities', [
        'student_id' => $this->student->id,
        'type' => 'video_completed',
        'entity_type' => Lecture::class,
        'entity_id' => $this->lecture->id,
    ]);
});

it('completion updates student_statistics', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 300,
        'is_completed' => true,
    ]);

    $stats = StudentStatistic::where('student_id', $this->student->id)->first();
    expect($stats)->not->toBeNull();
    expect($stats->completed_lectures)->toBe(1);
    expect((int) $stats->total_watch_minutes)->toBe(30);
});

it('idempotent completion does not double count', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 300,
        'is_completed' => true,
    ]);

    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 300,
        'is_completed' => true,
    ]);

    $stats = StudentStatistic::where('student_id', $this->student->id)->first();
    expect($stats->completed_lectures)->toBe(1);

    $completedCount = StudentActivity::where('student_id', $this->student->id)
        ->where('type', 'video_completed')
        ->where('entity_id', $this->lecture->id)
        ->count();
    expect($completedCount)->toBe(1);
});

it('rejects progress update without current_time', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'is_completed' => false,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['current_time']);
});

it('rejects progress update without is_completed', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 60,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['is_completed']);
});

it('unauthenticated user cannot update progress', function () {
    $this->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 60,
        'is_completed' => false,
    ])->assertStatus(401);
});

it('updates last_activity_at in student_statistics', function () {
    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$this->lecture->id}/progress", [
        'current_time' => 60,
        'is_completed' => false,
    ]);

    $stats = StudentStatistic::where('student_id', $this->student->id)->first();
    expect($stats->last_activity_at)->not->toBeNull();
    expect($stats->last_activity_at->isPast())->toBeTrue();
});

it('completion with missing lecture duration defaults to 10 minutes', function () {
    $noDurationLecture = $this->section->lectures()->create([
        'title' => 'No Duration',
        'sort_order' => 2,
    ]);

    $this->actingAs($this->studentUser)->postJson("/api/lectures/{$noDurationLecture->id}/progress", [
        'current_time' => 100,
        'is_completed' => true,
    ]);

    $stats = StudentStatistic::where('student_id', $this->student->id)->first();
    expect((int) $stats->total_watch_minutes)->toBe(10);
});
