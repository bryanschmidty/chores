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

        // Precedence: when local switch is enabled and a switch-user session is present,
        // honor the explicit switch choice and do not force LOCAL_LOGIN_USER_ID.
        if ((bool) config('auth.local_login_switch_enabled')) {
            $switchedUserId = (int) $request->session()->get('local_switch_user_id', 0);

            if ($switchedUserId > 0) {
                if (Auth::id() !== $switchedUserId) {
                    Auth::loginUsingId($switchedUserId);
                }

                return $next($request);
            }
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
