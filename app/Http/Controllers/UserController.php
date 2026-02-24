<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        // Ensure the user belongs to the same family as the authenticated user
        if ($user->family_id !== auth()->user()->family_id) {
            abort(403, 'Unauthorized access to user.');
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Ensure the user belongs to the same family as the authenticated user
        if ($user->family_id !== auth()->user()->family_id) {
            abort(403, 'Unauthorized access to user.');
        }

        // Cannot edit super admins
        if ($user->isSuperAdmin()) {
            return redirect()->back()
                ->withErrors(['user' => 'Cannot edit super admin users.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,member',
        ]);

        // Check if this is the last admin being demoted (excluding super admins)
        $adminCount = auth()->user()->family->users()->where('role', 'admin')->count();
        if ($user->role === 'admin' && $request->role === 'member' && $adminCount <= 1) {
            return redirect()->back()
                ->withErrors(['role' => 'Cannot demote the last admin in the family.']);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        return redirect()->back()
            ->with('success', 'User updated successfully.');
    }
}










