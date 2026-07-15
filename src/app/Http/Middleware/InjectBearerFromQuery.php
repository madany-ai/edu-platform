<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectBearerFromQuery
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('access_token');

        if ($token && !$request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
