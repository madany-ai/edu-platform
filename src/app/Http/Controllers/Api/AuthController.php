<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserMeResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json($result, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if ($result === null) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة.',
            ], 401);
        }

        if ($result === 'pending') {
            return response()->json([
                'message' => 'حسابك قيد المراجعة. يرجى انتظار الموافقة من قبل الإدارة.',
            ], 403);
        }

        if ($result === 'rejected') {
            return response()->json([
                'message' => 'لم يتم الموافقة على حسابك. يرجى التواصل مع الإدارة.',
            ], 403);
        }

        return response()->json([
            'user' => $result['user'],
            'access_token' => $result['access_token']
        ])->cookie('refresh_token', $result['refresh_token'], 60 * 24 * 30, '/', null, true, true, false, 'Strict');
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $this->authService->logout($request->user());
        }

        if ($refreshTokenString = $request->cookie('refresh_token')) {
            if ($token = PersonalAccessToken::findToken($refreshTokenString)) {
                $token->delete();
            }
        }

        return response()->json(['message' => 'Logged out'])->withoutCookie('refresh_token');
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $refreshTokenString = $request->cookie('refresh_token');
        if (!$refreshTokenString) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = PersonalAccessToken::findToken($refreshTokenString);
        if (!$token || $token->expires_at < now() || !$token->can('issue-access-token')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $token->tokenable;
        $token->delete();

        $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', ['issue-access-token'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
            'message' => 'Token refreshed successfully',
        ])->cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, '/', null, true, true, false, 'Strict');
    }

    public function me(): JsonResponse
    {
        $user = request()->user();
        $user->load('roles');

        if ($user->roles->pluck('name')->contains('student')) {
            $user->load('student');
        }

        return response()->json(new UserMeResource($user));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'father_phone' => 'sometimes|nullable|string|max:20',
            'mother_phone' => 'sometimes|nullable|string|max:20',
            'school_name' => 'sometimes|nullable|string|max:255',
        ]);

        $updatedUser = $this->authService->updateProfile($user, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث البيانات بنجاح.',
            'data' => new UserMeResource($updatedUser),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            $validated = $request->validate([
                'current_password' => 'required|current_password',
                'password' => 'required|string|min:6|confirmed',
            ]);
        } else {
            $validated = $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }
}
