<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function forgotPassword(): JsonResponse
    {
        request()->validate(['email' => 'required|email']);

        $status = Password::broker('users')->sendResetLink(
            request()->only('email'),
            function ($user, $token) {
                $this->notificationService->send(
                    $user,
                    'إعادة تعيين كلمة المرور',
                    "لقد طلبت إعادة تعيين كلمة المرور. استخدم هذا الكود: {$token}\nرابط إعادة التعيين: /reset-password?token={$token}&email={$user->email}",
                );
            }
        );

        return response()->json([
            'message' => 'إذا كان البريد الإلكتروني مسجلاً، فسيتم إرسال رابط إعادة تعيين كلمة المرور.',
        ], 200);
    }

    public function resetPassword(): JsonResponse
    {
        request()->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker('users')->reset(
            request()->only('email', 'token', 'password'),
            function ($user, $password) {
                $user->password = \Illuminate\Support\Facades\Hash::make($password);
                $user->save();

                // Invalidate all existing tokens (security: force re-login)
                $user->tokens()->delete();
            }
        );

        return response()->json([
            'message' => $status === Password::PASSWORD_RESET
                ? 'تم إعادة تعيين كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمة المرور الجديدة.'
                : 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.',
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }
}
