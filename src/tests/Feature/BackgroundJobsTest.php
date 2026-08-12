<?php

use App\Jobs\ProcessVideoHLS;
use App\Models\Course;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('sends notification to instructors on student registration', function () {
    $instructor = User::factory()->create(['status' => 'active']);
    $instructor->assignRole('instructor');

    $governorate = Governorate::create(['name' => 'القاهرة']);

    $this->postJson('/api/auth/register', [
        'first_name' => 'أحمد',
        'second_name' => 'محمد',
        'third_name' => 'علي',
        'last_name' => 'حسين',
        'email' => 'ahmed@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'مهندس',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
        'governorate_id' => $governorate->id,
        'academic_year' => 'sec_3',
        'cf-turnstile-response' => 'dummy-token',
    ])->assertStatus(201);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $instructor->id,
        'title' => 'تسجيل طالب جديد',
    ]);
});

it('creates notification in database correctly', function () {
    $user = User::factory()->create(['status' => 'active']);

    $notification = app(\App\Services\NotificationService::class)->send(
        $user,
        'اختبار إشعار',
        'محتوى الإشعار التجريبي'
    );

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'user_id' => $user->id,
        'title' => 'اختبار إشعار',
        'body' => 'محتوى الإشعار التجريبي',
    ]);
});

it('creates notification with correct structure', function () {
    $user = User::factory()->create(['status' => 'active']);

    $notification = app(\App\Services\NotificationService::class)->send(
        $user,
        'تم الشراء',
        'تم شراء المحاضرة بنجاح'
    );

    expect($notification->id)->not->toBeNull();
    expect($notification->user_id)->toBe($user->id);
    expect($notification->title)->toBe('تم الشراء');
    expect($notification->read_at)->toBeNull();
});

it('dispatches ProcessVideoHLS job when lecture has video', function () {
    Queue::fake();

    $instructor = User::factory()->create(['status' => 'active']);
    $instructor->assignRole('instructor');

    $course = Course::create([
        'title' => 'Test',
        'description' => 'Test',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);

    $section->lectures()->create([
        'title' => 'Lecture with video',
        'video_path' => 'videos/test.mp4',
        'sort_order' => 1,
    ]);

    Queue::assertPushed(ProcessVideoHLS::class);
});

it('does not dispatch ProcessVideoHLS job without video', function () {
    Queue::fake();

    $instructor = User::factory()->create(['status' => 'active']);
    $instructor->assignRole('instructor');

    $course = Course::create([
        'title' => 'Test',
        'description' => 'Test',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $instructor->id,
    ]);

    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);

    $section->lectures()->create([
        'title' => 'Lecture without video',
        'sort_order' => 1,
    ]);

    Queue::assertNotPushed(ProcessVideoHLS::class);
});
