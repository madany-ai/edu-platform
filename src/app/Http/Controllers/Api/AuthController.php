<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

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

        return response()->json($result);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(request()->user());

        return response()->json(['message' => 'Logged out']);
    }

    public function me(): JsonResponse
    {
        $user = request()->user();
        $user->load('roles');
        
        if ($user->roles->pluck('name')->contains('student')) {
            $user->load('student');
        }

        return response()->json(new \App\Http\Resources\UserMeResource($user));
    }

    public function updateProfile(\Illuminate\Http\Request $request): JsonResponse
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
            'data' => new \App\Http\Resources\UserMeResource($updatedUser),
        ]);
    }

    public function changePassword(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }
}
