<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LocalLoginOverride
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment(['local', 'development'])) {
            return $next($request);
        }

        $localLoginUserId = config('auth.local_login_user_id');

        if (blank($localLoginUserId)) {
            return $next($request);
        }

        $resolvedUserId = (int) $localLoginUserId;

        if ($resolvedUserId <= 0 || Auth::id() === $resolvedUserId) {
            return $next($request);
        }

        Auth::loginUsingId($resolvedUserId);

        return $next($request);
    }
}
