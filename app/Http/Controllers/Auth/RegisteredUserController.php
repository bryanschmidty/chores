<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'family_name' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request) {
            // Create new family
            $family = Family::create([
                'name' => $request->family_name,
                'settings' => [
                    'global_verification' => false,
                    'notification_preferences' => [
                        'email_chore_assigned' => true,
                        'email_chore_completed' => true,
                        'email_chore_verified' => true,
                        'email_overdue_reminder' => true,
                        'browser_chore_assigned' => true,
                        'browser_chore_completed' => true,
                        'browser_chore_verified' => true,
                        'browser_overdue_reminder' => true,
                    ],
                ],
            ]);

            // Create user as admin (first user becomes admin)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'family_id' => $family->id,
                'role' => 'admin',
                'notification_preferences' => [
                    'email_chore_assigned' => true,
                    'email_chore_completed' => true,
                    'email_chore_verified' => true,
                    'email_overdue_reminder' => true,
                    'browser_chore_assigned' => true,
                    'browser_chore_completed' => true,
                    'browser_chore_verified' => true,
                    'browser_overdue_reminder' => true,
                ],
            ]);

            event(new Registered($user));
            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        });
    }
}
