<?php

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\Governorate;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;

beforeEach(function () {
    $this->governorate = Governorate::create(['name' => 'القاهرة']);

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
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
});

it('NotificationService sends notification to correct user', function () {
    $service = app(NotificationService::class);

    $notification = $service->send(
        $this->instructor,
        'Test Title',
        'Test Body'
    );

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->user_id)->toBe($this->instructor->id)
        ->and($notification->title)->toBe('Test Title')
        ->and($notification->body)->toBe('Test Body')
        ->and($notification->read_at)->toBeNull();
});

it('registration notification is sent to all instructors', function () {
    $instructor2 = User::factory()->create(['status' => 'active']);
    $instructor2->assignRole('instructor');

    $this->postJson('/api/auth/register', [
        'first_name' => 'New',
        'second_name' => 'Student',
        'third_name' => 'Mid',
        'last_name' => 'Last',
        'email' => 'newstudent@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '01012345678',
        'father_phone' => '01112345678',
        'mother_phone' => '01212345678',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
        'governorate_id' => $this->governorate->id,
        'academic_year' => 'sec_3',
        'cf-turnstile-response' => 'test',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->instructor->id,
        'title' => 'تسجيل طالب جديد',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $instructor2->id,
        'title' => 'تسجيل طالب جديد',
    ]);
});

it('student approval sends notification to student', function () {
    $pendingUser = User::factory()->create(['status' => 'pending']);
    $pendingUser->assignRole('student');

    Student::create([
        'user_id' => $pendingUser->id,
        'first_name' => 'Pending',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'Student',
        'phone' => '01099999999',
        'father_phone' => '01199999999',
        'mother_phone' => '01299999999',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $pendingUser->update(['status' => 'active']);
    Student::where('user_id', $pendingUser->id)->update(['is_verified' => true]);

    app(NotificationService::class)->send(
        $pendingUser,
        'تم اعتماد حسابك',
        'تم اعتماد حسابك في المنصة. يمكنك الآن تسجيل الدخول والبدء في التعلم.'
    );

    $this->assertDatabaseHas('notifications', [
        'user_id' => $pendingUser->id,
        'title' => 'تم اعتماد حسابك',
    ]);
});

it('student rejection sends notification to student', function () {
    $pendingUser = User::factory()->create(['status' => 'pending']);
    $pendingUser->assignRole('student');

    app(NotificationService::class)->send(
        $pendingUser,
        'لم يتم اعتماد حسابك',
        'نأسف، لم يتم اعتماد حسابك في المنصة. يرجى التواصل مع الإدارة لمزيد من التفاصيل.'
    );

    $this->assertDatabaseHas('notifications', [
        'user_id' => $pendingUser->id,
        'title' => 'لم يتم اعتماد حسابك',
    ]);
});

it('purchase completion does NOT send notification (known gap)', function () {
    $order = Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $notificationsBefore = Notification::where('user_id', $this->studentUser->id)->count();

    app(\App\Services\GrantEntitlementService::class)->handle($order);

    $notificationsAfter = Notification::where('user_id', $this->studentUser->id)->count();

    expect($notificationsAfter)->toBe($notificationsBefore);
});

it('exam submission does NOT send notification to student (known gap)', function () {
    $exam = \App\Models\Exam::create([
        'lecture_id' => $this->lecture->id,
        'title' => 'Exam',
        'duration' => 30,
        'sort_order' => 0,
    ]);

    $question = $exam->questions()->create([
        'type' => 'multiple_choice',
        'question' => 'What is 2+2?',
        'degree' => 1,
    ]);

    $choice = $question->choices()->create([
        'answer' => '4',
        'is_correct' => true,
    ]);

    $attempt = \App\Models\ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'started_at' => now(),
    ]);

    $notificationsBefore = Notification::where('user_id', $this->studentUser->id)->count();

    $service = app(\App\Services\ExamService::class);
    $service->submitAttempt($attempt, [
        ['question_id' => $question->id, 'answer' => $choice->id],
    ]);

    $notificationsAfter = Notification::where('user_id', $this->studentUser->id)->count();

    expect($notificationsAfter)->toBe($notificationsBefore);
});

it('notification read_at is nullable', function () {
    $service = app(NotificationService::class);

    $notification = $service->send($this->instructor, 'Unread', 'Body');

    expect($notification->read_at)->toBeNull();

    $notification->update(['read_at' => now()]);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('multiple notifications can be sent to same user', function () {
    $service = app(NotificationService::class);

    $service->send($this->instructor, 'First', 'Body 1');
    $service->send($this->instructor, 'Second', 'Body 2');
    $service->send($this->instructor, 'Third', 'Body 3');

    $count = Notification::where('user_id', $this->instructor->id)->count();

    expect($count)->toBe(3);
});

it('notification does not exist for entitlement expiry (known gap)', function () {
    $entitlement = Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture->id,
        'academic_year' => 'sec_3',
        'order_id' => Order::create([
            'student_id' => $this->student->id,
            'purchasable_id' => $this->course->id,
            'purchasable_type' => Course::class,
            'amount_cents' => 10000,
            'status' => 'completed',
            'paid_at' => now(),
        ])->id,
        'expires_at' => now()->subDay(),
    ]);

    $notifications = Notification::where('user_id', $this->studentUser->id)
        ->where('title', 'like', '%اشتراك%')
        ->count();

    expect($notifications)->toBe(0);
});
