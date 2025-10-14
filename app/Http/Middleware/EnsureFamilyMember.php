<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilyMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admins can access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has a family
        if (!$user->family_id) {
            return redirect()->route('family.setup');
        }

        return $next($request);
    }
}