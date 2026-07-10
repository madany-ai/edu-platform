<?php

namespace App\Domain\Auth\Controllers;

use App\Domain\Shared\Controllers\Controller;
use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Requests\RegisterRequest;
use App\Domain\Auth\Services\AuthService;
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
        return response()->json(request()->user());
    }
}
