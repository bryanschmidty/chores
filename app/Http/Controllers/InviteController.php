<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class InviteController extends Controller
{
    /**
     * Show family invite registration form.
     */
    public function familyInvite(string $encryptedFamilyId): Response
    {
        try {
            $familyId = Crypt::decryptString($encryptedFamilyId);
            $family = Family::findOrFail($familyId);
        } catch (\Exception $e) {
            abort(404, 'Invalid invite link.');
        }

        return Inertia::render('Auth/FamilyInvite', [
            'family' => $family,
            'encryptedFamilyId' => $encryptedFamilyId,
        ]);
    }

    /**
     * Show user-specific invite registration form.
     */
    public function userInvite(string $encryptedUserId): Response
    {
        try {
            $userId = Crypt::decryptString($encryptedUserId);
            $user = User::findOrFail($userId);
        } catch (\Exception $e) {
            abort(404, 'Invalid invite link.');
        }

        return Inertia::render('Auth/UserInvite', [
            'user' => $user,
            'family' => $user->family,
            'encryptedUserId' => $encryptedUserId,
        ]);
    }

    /**
     * Register user via family invite.
     */
    public function storeFamilyInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'encrypted_family_id' => 'required|string',
        ]);

        try {
            $familyId = Crypt::decryptString($request->encrypted_family_id);
            $family = Family::findOrFail($familyId);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Invalid invite link.']);
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'family_id' => $family->id,
            'role' => 'member',
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

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to the ' . $family->name . ' family!');
    }

    /**
     * Register user via user-specific invite.
     */
    public function storeUserInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'encrypted_user_id' => 'required|string',
        ]);

        try {
            $userId = Crypt::decryptString($request->encrypted_user_id);
            $user = User::findOrFail($userId);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Invalid invite link.']);
        }

        // Update user password and login
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to the ' . $user->family->name . ' family, ' . $user->name . '!');
    }
}