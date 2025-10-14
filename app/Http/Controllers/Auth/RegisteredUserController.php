<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyInvite;
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
        $inviteCode = $request->get('invite');
        $invite = null;

        if ($inviteCode) {
            $invite = FamilyInvite::where('invite_code', $inviteCode)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with('family')
                ->first();
        }

        return Inertia::render('Auth/Register', [
            'invite' => $invite,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // First validate basic fields
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'family_name' => 'nullable|string|max:255',
            'invite_code' => 'nullable|string',
        ]);

        // Validate invite code if provided
        if ($request->invite_code) {
            $invite = FamilyInvite::where('invite_code', $request->invite_code)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if (!$invite) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'invite_code' => ['Invalid or expired invite code.'],
                ]);
            }
        } else {
            // If no invite code, family name is required
            if (empty($request->family_name)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'family_name' => ['The family name field is required when creating a new family.'],
                ]);
            }
        }

        return DB::transaction(function () use ($request) {
            $invite = null;
            $family = null;
            $role = 'member';

            // Check if user is joining via invite
            if ($request->invite_code) {
                // Re-fetch the invite (already validated above)
                $invite = FamilyInvite::where('invite_code', $request->invite_code)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->with('family')
                    ->first();

                $family = $invite->family;
                $role = $invite->role ?? 'member'; // Use the role from the invite, default to member
            } else {
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

                $role = 'admin'; // First user becomes admin
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'family_id' => $family->id,
                'role' => $role,
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

            // Mark invite as accepted if applicable
            if ($invite) {
                $invite->accept($user);
            }

            event(new Registered($user));
            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        });
    }
}
