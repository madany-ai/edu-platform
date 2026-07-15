<?php

use App\Models\Course;
use App\Models\CourseAssistant;
use App\Models\Lecture;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->assistant = User::factory()->create(['status' => 'active']);
    $this->assistant->assignRole('assistant');

    $this->course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    CourseAssistant::create([
        'course_id' => $this->course->id,
        'user_id' => $this->assistant->id,
    ]);
});

it('assistant can access Filament dashboard', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin');

    $response->assertSuccessful();
});

it('assistant can view courses resource (scoped to assigned)', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/courses');

    $response->assertSuccessful();
});

it('assistant cannot create courses via Filament', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/courses/create');

    $response->assertForbidden();
});

it('assistant cannot view orders resource', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/orders');

    $response->assertForbidden();
});

it('assistant cannot view assistants resource', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/assistants');

    $response->assertForbidden();
});

it('assistant cannot view settings page', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/settings');

    $response->assertForbidden();
});

it('assistant cannot access products resource', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/products');

    expect($response->status())->toBeIn([403, 404]);
});

it('assistant cannot access bundles resource', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/bundles');

    expect($response->status())->toBeIn([403, 404]);
});

it('assistant cannot access activity resource', function () {
    $this->actingAs($this->assistant);

    $response = $this->get('/admin/activity-resource');

    expect($response->status())->toBeIn([403, 404]);
});

it('assistant cannot access instructor dashboard API', function () {
    $response = $this->actingAs($this->assistant)->getJson('/api/dashboard/instructor');

    $response->assertStatus(403);
});

it('assistant cannot access instructor courses API', function () {
    $response = $this->actingAs($this->assistant)->getJson('/api/dashboard/instructor/courses');

    $response->assertStatus(403);
});

it('assistant cannot create courses via API', function () {
    $response = $this->actingAs($this->assistant)->postJson('/api/courses', [
        'title' => 'Assistant Course',
        'description' => 'Desc',
        'price' => 0,
    ]);

    $response->assertStatus(403);
});

it('assistant cannot create sections via API', function () {
    $response = $this->actingAs($this->assistant)->postJson("/api/courses/{$this->course->id}/sections", [
        'title' => 'New Section',
    ]);

    $response->assertStatus(403);
});

it('assistant cannot create lectures via API', function () {
    $response = $this->actingAs($this->assistant)->postJson("/api/sections/{$this->section->id}/lectures", [
        'title' => 'New Lecture',
    ]);

    $response->assertStatus(403);
});

it('assistant cannot create exams via API', function () {
    $response = $this->actingAs($this->assistant)->postJson("/api/lectures/{$this->lecture->id}/exam", [
        'title' => 'Exam',
        'duration' => 30,
    ]);

    $response->assertStatus(403);
});

it('assistant CAN access assigned course lecture via API', function () {
    $response = $this->actingAs($this->assistant)->getJson("/api/lectures/{$this->lecture->id}");

    $response->assertOk();
});

it('assistant cannot access non-assigned course lecture via API', function () {
    $otherInstructor = User::factory()->create(['status' => 'active']);
    $otherInstructor->assignRole('instructor');

    $otherCourse = Course::create([
        'title' => 'Other',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $otherInstructor->id,
    ]);

    $otherSection = $otherCourse->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $otherLecture = $otherSection->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $response = $this->actingAs($this->assistant)->getJson("/api/lectures/{$otherLecture->id}");

    $response->assertStatus(403);
});

it('instructor can access assistant management but assistant cannot', function () {
    $this->actingAs($this->instructor);
    $response1 = $this->get('/admin/assistants');
    $response1->assertSuccessful();

    $this->actingAs($this->assistant);
    $response2 = $this->get('/admin/assistants');
    $response2->assertForbidden();
});

it('unassigned assistant cannot access course lecture', function () {
    $unassignedAssistant = User::factory()->create(['status' => 'active']);
    $unassignedAssistant->assignRole('assistant');

    $response = $this->actingAs($unassignedAssistant)->getJson("/api/lectures/{$this->lecture->id}");

    $response->assertStatus(403);
});
