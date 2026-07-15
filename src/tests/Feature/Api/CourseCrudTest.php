<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->otherInstructor = User::factory()->create(['status' => 'active']);
    $this->otherInstructor->assignRole('instructor');

    $this->studentUser = User::factory()->create(['status' => 'active']);
    $this->studentUser->assignRole('student');
});

it('instructor creates a course', function () {
    $response = $this->actingAs($this->instructor)->postJson('/api/courses', [
        'title' => 'New Course',
        'description' => 'Course description',
        'price' => 150,
        'status' => 'draft',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'title', 'description', 'price']]);

    $this->assertDatabaseHas('courses', [
        'title' => 'New Course',
        'instructor_id' => $this->instructor->id,
    ]);
});

it('instructor creates course with published status', function () {
    $response = $this->actingAs($this->instructor)->postJson('/api/courses', [
        'title' => 'Published Course',
        'description' => 'Description',
        'price' => 200,
        'status' => 'published',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('courses', [
        'title' => 'Published Course',
        'status' => 'published',
    ]);
});

it('rejects course creation without required fields', function () {
    $response = $this->actingAs($this->instructor)->postJson('/api/courses', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'price']);
});

it('instructor updates own course', function () {
    $course = Course::create([
        'title' => 'Old Title',
        'description' => 'Old description',
        'price' => 100,
        'status' => 'draft',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->actingAs($this->instructor)->putJson("/api/courses/{$course->id}", [
        'title' => 'New Title',
        'description' => 'New description',
        'price' => 250,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'New Title',
        'price' => 250,
    ]);
});

it('instructor deletes own course', function () {
    $course = Course::create([
        'title' => 'To Delete',
        'description' => 'Delete me',
        'price' => 0,
        'status' => 'draft',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->actingAs($this->instructor)->deleteJson("/api/courses/{$course->id}");

    $response->assertOk()
        ->assertJson(['message' => 'Course deleted']);

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

it('prevents instructor from updating other instructors course', function () {
    $course = Course::create([
        'title' => 'Instructor 1 Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->actingAs($this->otherInstructor)->putJson("/api/courses/{$course->id}", [
        'title' => 'Hacked',
        'description' => 'Hacked',
        'price' => 0,
    ])->assertStatus(403);
});

it('prevents instructor from deleting other instructors course', function () {
    $course = Course::create([
        'title' => 'Instructor 1 Course',
        'description' => 'Test',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->actingAs($this->otherInstructor)->deleteJson("/api/courses/{$course->id}")
        ->assertStatus(403);
});

it('instructor creates section', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->actingAs($this->instructor)->postJson("/api/courses/{$course->id}/sections", [
        'title' => 'Section 1',
        'sort_order' => 1,
    ]);

    $response->assertStatus(201)
        ->assertJson(['title' => 'Section 1']);
});

it('instructor updates section', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'Old Title', 'sort_order' => 1]);

    $this->actingAs($this->instructor)
        ->putJson("/api/courses/{$course->id}/sections/{$section->id}", [
            'title' => 'New Title',
        ])->assertOk();

    $this->assertDatabaseHas('course_sections', ['id' => $section->id, 'title' => 'New Title']);
});

it('instructor deletes section', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'To Delete', 'sort_order' => 1]);

    $this->actingAs($this->instructor)
        ->deleteJson("/api/courses/{$course->id}/sections/{$section->id}")
        ->assertOk();

    $this->assertDatabaseMissing('course_sections', ['id' => $section->id]);
});

it('instructor creates lecture', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);

    $response = $this->actingAs($this->instructor)->postJson("/api/sections/{$section->id}/lectures", [
        'title' => 'Lecture 1',
        'description' => 'Description',
        'duration' => 30,
        'sort_order' => 1,
    ]);

    $response->assertStatus(201)
        ->assertJson(['title' => 'Lecture 1']);
});

it('instructor creates lecture with youtube_url', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);

    $response = $this->actingAs($this->instructor)->postJson("/api/sections/{$section->id}/lectures", [
        'title' => 'Lecture with YT',
        'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
        'duration' => 60,
    ]);

    $response->assertStatus(201);

    $lecture = Lecture::where('title', 'Lecture with YT')->first();
    expect($lecture->video)->not->toBeNull();
});

it('instructor updates lecture', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create([
        'title' => 'Old Lecture',
        'sort_order' => 1,
    ]);

    $this->actingAs($this->instructor)
        ->putJson("/api/sections/{$section->id}/lectures/{$lecture->id}", [
            'title' => 'Updated Lecture',
        ])->assertOk();

    $this->assertDatabaseHas('lectures', ['id' => $lecture->id, 'title' => 'Updated Lecture']);
});

it('instructor deletes lecture', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'To Delete', 'sort_order' => 1]);

    $this->actingAs($this->instructor)
        ->deleteJson("/api/sections/{$section->id}/lectures/{$lecture->id}")
        ->assertOk();

    $this->assertDatabaseMissing('lectures', ['id' => $lecture->id]);
});

it('public course listing only shows published courses', function () {
    Course::create([
        'title' => 'Published',
        'description' => 'Pub',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    Course::create([
        'title' => 'Draft',
        'description' => 'Draft',
        'price' => 0,
        'status' => 'draft',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->getJson('/api/courses');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->toArray();
    expect($titles)->toContain('Published');
    expect($titles)->not->toContain('Draft');
});

it('public course search filters by title', function () {
    Course::create([
        'title' => 'Math Course',
        'description' => 'Algebra',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    Course::create([
        'title' => 'Science Course',
        'description' => 'Physics',
        'price' => 50,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $response = $this->getJson('/api/courses?search=Math');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->toArray();
    expect($titles)->toContain('Math Course');
    expect($titles)->not->toContain('Science Course');
});

it('course show returns sections and lectures', function () {
    $course = Course::create([
        'title' => 'Full Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
    $section->lectures()->create(['title' => 'L2', 'sort_order' => 2]);

    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'title',
                'sections',
            ],
        ]);
});

it('prevents student from creating course', function () {
    $this->actingAs($this->studentUser)->postJson('/api/courses', [
        'title' => 'Hacked',
        'description' => 'Hack',
        'price' => 0,
    ])->assertStatus(403);
});

it('prevents student from creating sections', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->actingAs($this->studentUser)->postJson("/api/courses/{$course->id}/sections", [
        'title' => 'Test Section',
    ])->assertStatus(403);
});

it('prevents student from creating lectures', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);

    $this->actingAs($this->studentUser)->postJson("/api/sections/{$section->id}/lectures", [
        'title' => 'Test Lecture',
    ])->assertStatus(403);
});

it('prevents student from updating sections', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);

    $this->actingAs($this->studentUser)->putJson("/api/courses/{$course->id}/sections/{$section->id}", [
        'title' => 'Hacked',
    ])->assertStatus(403);
});

it('prevents student from deleting sections', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);

    $this->actingAs($this->studentUser)->deleteJson("/api/courses/{$course->id}/sections/{$section->id}")
        ->assertStatus(403);
});

it('prevents student from updating lectures', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $this->actingAs($this->studentUser)->putJson("/api/sections/{$section->id}/lectures/{$lecture->id}", [
        'title' => 'Hacked',
    ])->assertStatus(403);
});

it('prevents student from deleting lectures', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $this->actingAs($this->studentUser)->deleteJson("/api/sections/{$section->id}/lectures/{$lecture->id}")
        ->assertStatus(403);
});

it('instructor can view own courses via instructor courses endpoint', function () {
    Course::create([
        'title' => 'My Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    Course::create([
        'title' => 'Other Course',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->otherInstructor->id,
    ]);

    $response = $this->actingAs($this->instructor)->getJson('/api/dashboard/instructor/courses');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->toArray();
    expect($titles)->toContain('My Course');
    expect($titles)->not->toContain('Other Course');
});
