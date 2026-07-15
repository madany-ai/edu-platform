<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('oldpassword123'),
        'status' => 'active',
    ]);
});

it('sends reset link for valid email', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.',
        ]);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

it('rejects reset for non-existent email', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'unknown@example.com',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'البريد الإلكتروني غير مسجل في النظام.',
        ]);
});

it('validates email field is required', function () {
    $response = $this->postJson('/api/auth/forgot-password', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates email format', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('resets password with valid token', function () {
    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمة المرور الجديدة.',
        ]);

    // Old password should no longer work
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $this->user->id,
    ]);

    // New password should work
    $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $this->user->fresh()->password));
});

it('rejects reset with invalid token', function () {
    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => 'invalid-token-12345',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.',
        ]);

    // Password should still be old
    $this->assertTrue(\Illuminate\Support\Facades\Hash::check('oldpassword123', $this->user->fresh()->password));
});

it('rejects reset with mismatched email and token', function () {
    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'other@example.com', // wrong email
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.',
        ]);
});

it('validates required fields for reset', function () {
    $response = $this->postJson('/api/auth/reset-password', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'token', 'password']);
});

it('validates password minimum length', function () {
    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('validates password confirmation matches', function () {
    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $response = $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('invalidates all tokens after password reset', function () {
    // Create some existing tokens
    $this->user->createToken('existing-token-1');
    $this->user->createToken('existing-token-2');
    expect($this->user->fresh()->tokens)->toHaveCount(2);

    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    expect($this->user->fresh()->tokens)->toHaveCount(0);
});

it('allows login with new password after reset', function () {
    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
});

it('rejects login with old password after reset', function () {
    $broker = Password::broker('users');
    $token = $broker->createToken($this->user);

    $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'oldpassword123',
        'cf-turnstile-response' => 'dummy-token',
    ]);

    $response->assertStatus(401);
});
