<?php

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\Lecture;
use App\Models\LectureVideo;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

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
        'description' => 'Advanced Math',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'Section 1', 'sort_order' => 1]);

    $this->lecture1 = $this->section->lectures()->create(['title' => 'Lecture 1', 'sort_order' => 1]);
    $this->lecture2 = $this->section->lectures()->create(['title' => 'Lecture 2', 'sort_order' => 2]);

    $this->lecture1->video()->create([
        'video_path' => 'hls/test/playlist.m3u8',
        'status' => 'completed',
        'encryption_key' => bin2hex(openssl_random_pseudo_bytes(16)),
    ]);

    $this->lecture2->video()->create([
        'video_path' => 'hls/test2/playlist.m3u8',
        'status' => 'completed',
        'encryption_key' => bin2hex(openssl_random_pseudo_bytes(16)),
    ]);

    $this->order = \App\Models\Order::create([
        'student_id' => $this->student->id,
        'purchasable_id' => $this->course->id,
        'purchasable_type' => Course::class,
        'amount_cents' => 10000,
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $this->lecture1->id,
        'order_id' => $this->order->id,
        'expires_at' => now()->addDays(30),
    ]);

    $this->service = app(\App\Services\VideoAccessService::class);
});

it('validateToken rejects token for lecture B when token was issued for lecture A', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture2, '192.168.1.1'))->toBeFalse();
});

it('validateToken rejects token with different IP address', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture1, '10.0.0.99'))->toBeFalse();
});

it('validateToken rejects expired token (generated 10 minutes ago)', function () {
    $payload = [
        'user_id' => $this->studentUser->id,
        'lecture_id' => $this->lecture1->id,
        'ip' => '192.168.1.1',
        'expires_at' => now()->subMinutes(10)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken rejects token for non-existent user', function () {
    $fakeUserId = 'non-existent-uuid';
    $payload = [
        'user_id' => $fakeUserId,
        'lecture_id' => $this->lecture1->id,
        'ip' => '192.168.1.1',
        'expires_at' => now()->addMinutes(5)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken rejects token when entitlement has been revoked', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeTrue();

    \App\Models\Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $this->lecture1->id)
        ->delete();

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken rejects token when user status changes to rejected', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeTrue();

    $this->studentUser->update(['status' => 'rejected']);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken rejects token when entitlement expires between generation and validation', function () {
    $payload = [
        'user_id' => $this->studentUser->id,
        'lecture_id' => $this->lecture1->id,
        'ip' => '192.168.1.1',
        'expires_at' => now()->addMinutes(5)->timestamp,
    ];
    $token = Crypt::encrypt($payload);

    \App\Models\Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $this->lecture1->id)
        ->update(['expires_at' => now()->subMinutes(1)]);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken rejects token with tampered payload', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    $payload = Crypt::decrypt($token);
    $payload['lecture_id'] = $this->lecture2->id;
    $tamperedToken = Crypt::encrypt($payload);

    expect($this->service->validateToken($tamperedToken, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('validateToken accepts valid token within 5-minute window', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeTrue();
});

it('validateToken rejects token for suspended student', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeTrue();

    $this->studentUser->update(['status' => 'pending']);

    expect($this->service->validateToken($token, $this->lecture1, '192.168.1.1'))->toBeFalse();
});

it('streamKey endpoint rejects missing token', function () {
    $response = $this->getJson("/api/lectures/{$this->lecture1->id}/key");

    $response->assertStatus(400)
        ->assertJsonPath('message', 'Missing token');
});

it('streamKey endpoint rejects invalid token', function () {
    $response = $this->getJson("/api/lectures/{$this->lecture1->id}/key?token=invalid-token");

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Invalid or expired token');
});

it('streamKey endpoint rejects token for wrong lecture', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '127.0.0.1');

    $response = $this->getJson("/api/lectures/{$this->lecture2->id}/key?token={$token}");

    $response->assertStatus(403);
});

it('streamKey endpoint returns encryption key for valid token', function () {
    $token = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '127.0.0.1');

    $response = $this->getJson("/api/lectures/{$this->lecture1->id}/key?token={$token}");

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/octet-stream')
        ->assertHeader('Cache-Control', 'no-cache, private');
});

it('streamKey endpoint returns 404 when lecture has no video', function () {
    $lectureNoVideo = $this->section->lectures()->create(['title' => 'No Video', 'sort_order' => 3]);

    Entitlement::create([
        'student_id' => $this->student->id,
        'lecture_id' => $lectureNoVideo->id,
        'order_id' => $this->order->id,
    ]);

    $token = $this->service->generateSignedToken($this->studentUser, $lectureNoVideo, '127.0.0.1');

    $response = $this->getJson("/api/lectures/{$lectureNoVideo->id}/key?token={$token}");

    $response->assertStatus(404);
});

it('generateSignedToken produces different tokens for different IPs', function () {
    $token1 = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');
    $token2 = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '10.0.0.1');

    expect($token1)->not->toBe($token2);
});

it('generateSignedToken produces different tokens for different lectures', function () {
    $token1 = $this->service->generateSignedToken($this->studentUser, $this->lecture1, '192.168.1.1');
    $token2 = $this->service->generateSignedToken($this->studentUser, $this->lecture2, '192.168.1.1');

    expect($token1)->not->toBe($token2);
});

it('canAccess returns false for student whose entitlement just expired', function () {
    Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $this->lecture1->id)
        ->update(['expires_at' => now()->subSecond()]);

    expect($this->service->canAccess($this->studentUser, $this->lecture1))->toBeFalse();
});

it('canAccess returns true for student with non-expiring entitlement', function () {
    Entitlement::where('student_id', $this->student->id)
        ->where('lecture_id', $this->lecture1->id)
        ->update(['expires_at' => null]);

    expect($this->service->canAccess($this->studentUser, $this->lecture1))->toBeTrue();
});
