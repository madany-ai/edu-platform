<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== \App\Enums\UserStatus::Active) {
            return response()->json([
                'message' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.',
            ], 403);
        }

        return $next($request);
    }
}
