<?php

use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['status' => 'active']);
    $this->admin->assignRole('super_admin');

    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->assistant = User::factory()->create(['status' => 'active']);
    $this->assistant->assignRole('assistant');
});

it('admin can access Filament dashboard', function () {
    $this->be($this->admin);
    $this->get('/admin')
        ->assertSuccessful();
});

it('admin can access courses resource list', function () {
    Course::create([
        'title' => 'Test Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->be($this->admin);
    $this->get('/admin/courses')
        ->assertSuccessful();
});

it('admin can access students resource list', function () {
    $this->be($this->admin);
    $this->get('/admin/students')
        ->assertSuccessful();
});

it('admin can access orders resource list', function () {
    $this->be($this->admin);
    $this->get('/admin/orders')
        ->assertSuccessful();
});

it('admin can access exams resource list', function () {
    $this->be($this->admin);
    $this->get('/admin/exams')
        ->assertSuccessful();
});

it('admin can access assistants resource list', function () {
    $this->be($this->admin);
    $this->get('/admin/assistants')
        ->assertSuccessful();
});

it('admin can access settings page', function () {
    $this->be($this->admin);
    $this->get('/admin/settings')
        ->assertSuccessful();
});

it('instructor can access Filament dashboard', function () {
    $this->be($this->instructor);
    $this->get('/admin')
        ->assertSuccessful();
});

it('instructor can access courses resource with own courses only', function () {
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
        'instructor_id' => $this->admin->id,
    ]);

    $this->be($this->instructor);
    $this->get('/admin/courses')
        ->assertSuccessful();
});

it('assistant can access Filament dashboard', function () {
    $this->be($this->assistant);
    $this->get('/admin')
        ->assertSuccessful();
});

it('assistant has limited access to courses resource', function () {
    $this->be($this->assistant);
    $this->get('/admin/courses')
        ->assertSuccessful();
});

it('unauthenticated user cannot access admin panel', function () {
    $this->get('/admin')
        ->assertRedirect();
});

it('student cannot access admin panel', function () {
    $student = User::factory()->create(['status' => 'active']);
    $student->assignRole('student');

    $this->be($student);
    $this->get('/admin')
        ->assertForbidden();
});

it('super_admin can access admin panel', function () {
    $this->be($this->admin);
    $this->get('/admin')
        ->assertSuccessful();
});
