<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\ResolveGoogleUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request, ResolveGoogleUser $resolveGoogleUser): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect('/')
                ->with('error', 'Google sign-in was not completed. Please try again.');
        }

        $user = $resolveGoogleUser->resolve($googleUser);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')
            ->with('status', 'Signed in successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('status', 'Signed out successfully.');
    }

    public function switchLocalUser(Request $request): RedirectResponse
    {
        if (! app()->environment(['local', 'development', 'testing']) || ! config('auth.local_login_switch_enabled')) {
            abort(403);
        }

        $validated = $request->validate([
            'switch_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('household_id', $request->user()?->household_id),
            ],
        ]);

        $switchUserId = (int) $validated['switch_user_id'];

        Auth::loginUsingId($switchUserId);
        $request->session()->put('local_switch_user_id', $switchUserId);
        $request->session()->regenerate();

        return redirect('/')->with('status', 'Switched user.');
    }
}
