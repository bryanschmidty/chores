<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use App\Models\FamilyInvite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    /**
     * Display family management dashboard.
     */
    public function index(): Response
    {
        $family = auth()->user()->family;
        
        $family->load([
            'users' => function ($query) {
                $query->orderBy('role')->orderBy('name');
            },
            'familyInvites' => function ($query) {
                $query->latest();
            },
        ]);

        // Get family statistics
        $familyStats = [
            'total_members' => $family->users()->count(),
            'total_admins' => $family->users()->whereIn('role', ['admin', 'super-admin'])->count(),
            'total_chores' => $family->chores()->count(),
            'pending_chores' => $family->chores()->where('status', 'pending')->count(),
            'total_shop_items' => $family->shopItems()->count(),
            'active_shop_items' => $family->shopItems()->where('is_active', true)->count(),
            'total_goals' => $family->familyGoals()->count(),
            'active_goals' => $family->familyGoals()->where('status', 'active')->count(),
            'pending_invites' => $family->familyInvites()->where('status', 'pending')->count(),
        ];

        return Inertia::render('Admin/Family/Index', [
            'family' => $family,
            'familyStats' => $familyStats,
        ]);
    }

    /**
     * Show the form for editing family settings.
     */
    public function edit(): Response
    {
        $family = auth()->user()->family;

        return Inertia::render('Admin/Family/Edit', [
            'family' => $family,
        ]);
    }

    /**
     * Update family settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'name' => 'required|string|max:255',
            'settings' => 'nullable|array',
            'settings.global_verification' => 'boolean',
            'settings.notification_preferences' => 'nullable|array',
        ]);

        $family->update([
            'name' => $request->name,
            'settings' => array_merge($family->settings ?? [], $request->settings ?? []),
        ]);

        return redirect()->route('admin.family.index')
            ->with('success', 'Family settings updated successfully.');
    }

    /**
     * Show family members management.
     */
    public function members(): Response
    {
        $family = auth()->user()->family;
        
        $members = $family->users()
            ->with(['pointTransactions' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'points_balance' => $user->getPointsBalance(),
                    'created_at' => $user->created_at,
                    'recent_transactions' => $user->pointTransactions,
                ];
            });

        return Inertia::render('Admin/Family/Members', [
            'family' => $family,
            'members' => $members,
        ]);
    }

    /**
     * Promote a member to admin.
     */
    public function promoteMember(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['user' => 'User does not belong to this family.']);
        }

        if ($user->role === 'member') {
            $user->update(['role' => 'admin']);
        }

        return redirect()->back()
            ->with('success', 'Member promoted to admin successfully.');
    }

    /**
     * Demote an admin to member.
     */
    public function demoteMember(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['user' => 'User does not belong to this family.']);
        }

        // Check if this is the last admin (excluding super admins)
        $adminCount = $family->users()->where('role', 'admin')->count();
        if ($user->role === 'admin' && $adminCount <= 1) {
            return redirect()->back()
                ->withErrors(['user' => 'Cannot demote the last admin in the family.']);
        }

        if ($user->role === 'admin') {
            $user->update(['role' => 'member']);
        }

        return redirect()->back()
            ->with('success', 'Admin demoted to member successfully.');
    }

    /**
     * Remove a member from the family.
     */
    public function removeMember(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['user' => 'User does not belong to this family.']);
        }

        // Check if this is the last admin (excluding super admins)
        $adminCount = $family->users()->where('role', 'admin')->count();
        if ($user->isAdmin() && $adminCount <= 1) {
            return redirect()->back()
                ->withErrors(['user' => 'Cannot remove the last admin from the family.']);
        }

        // Cannot remove super admins
        if ($user->isSuperAdmin()) {
            return redirect()->back()
                ->withErrors(['user' => 'Cannot remove super admin users.']);
        }

        $user->update(['family_id' => null, 'role' => 'member']);

        return redirect()->back()
            ->with('success', 'Member removed from family successfully.');
    }

    /**
     * Create a new family invite.
     */
    public function createInvite(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'email' => 'nullable|email|max:255',
            'role' => 'required|in:admin,member',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $invite = $family->familyInvites()->create([
            'invite_code' => $this->generateInviteCode(),
            'email' => $request->email,
            'role' => $request->role,
            'expires_at' => $request->expires_at ?? now()->addDays(7),
        ]);

        return redirect()->back()
            ->with('success', 'Invite created successfully. Share this code: ' . $invite->invite_code);
    }

    /**
     * Cancel/delete a family invite.
     */
    public function cancelInvite(FamilyInvite $invite): RedirectResponse
    {
        $family = auth()->user()->family;

        if ($invite->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['invite' => 'Invite does not belong to your family.']);
        }

        $invite->delete();

        return redirect()->back()
            ->with('success', 'Invite cancelled successfully.');
    }

    /**
     * Generate a unique invite code.
     */
    private function generateInviteCode(): string
    {
        do {
            $code = Str::random(10);
        } while (FamilyInvite::where('invite_code', $code)->exists());

        return $code;
    }
}