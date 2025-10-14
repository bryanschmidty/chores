<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    /**
     * Display a listing of families.
     */
    public function index(): Response
    {
        $families = Family::with(['users', 'chores', 'shopItems', 'familyGoals'])
            ->withCount(['users', 'chores', 'shopItems', 'familyGoals'])
            ->latest()
            ->paginate(15);

        // Get system-wide statistics
        $stats = [
            'total_families' => Family::count(),
            'total_users' => User::count(),
            'total_admins' => User::whereIn('role', ['admin', 'super-admin'])->count(),
            'total_members' => User::where('role', 'member')->count(),
            'active_families' => Family::whereHas('users')->count(),
        ];

        return Inertia::render('SuperAdmin/Families/Index', [
            'families' => $families,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the specified family.
     */
    public function show(Family $family): Response
    {
        $family->load([
            'users' => function ($query) {
                $query->orderBy('role')->orderBy('name');
            },
            'chores' => function ($query) {
                $query->with(['assignedTo', 'createdBy', 'template'])->latest();
            },
            'shopItems' => function ($query) {
                $query->latest();
            },
            'familyGoals' => function ($query) {
                $query->latest();
            },
        ]);

        // Get family statistics
        $familyStats = [
            'total_members' => $family->users()->count(),
            'total_admins' => $family->users()->whereIn('role', ['admin', 'super-admin'])->count(),
            'total_chores' => $family->chores()->count(),
            'pending_chores' => $family->chores()->where('status', 'pending')->count(),
            'completed_chores' => $family->chores()->where('status', 'completed')->count(),
            'overdue_chores' => $family->chores()->where('status', 'overdue')->count(),
            'total_shop_items' => $family->shopItems()->count(),
            'active_shop_items' => $family->shopItems()->where('is_active', true)->count(),
            'total_goals' => $family->familyGoals()->count(),
            'active_goals' => $family->familyGoals()->where('status', 'active')->count(),
            'total_invites' => $family->familyInvites()->count(),
            'pending_invites' => $family->familyInvites()->where('status', 'pending')->count(),
        ];

        return Inertia::render('SuperAdmin/Families/Show', [
            'family' => $family,
            'familyStats' => $familyStats,
        ]);
    }

    /**
     * Show the form for editing the specified family.
     */
    public function edit(Family $family): Response
    {
        return Inertia::render('SuperAdmin/Families/Edit', [
            'family' => $family,
        ]);
    }

    /**
     * Update the specified family.
     */
    public function update(Request $request, Family $family): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'settings' => 'nullable|array',
        ]);

        $family->update([
            'name' => $request->name,
            'settings' => $request->settings ?? $family->settings,
        ]);

        return redirect()->route('super-admin.families.show', $family)
            ->with('success', 'Family updated successfully.');
    }

    /**
     * Remove the specified family.
     */
    public function destroy(Family $family): RedirectResponse
    {
        // Check if family has users
        if ($family->users()->count() > 0) {
            return redirect()->back()
                ->withErrors(['family' => 'Cannot delete family with active members. Remove all members first.']);
        }

        $family->delete();

        return redirect()->route('super-admin.families.index')
            ->with('success', 'Family deleted successfully.');
    }

    /**
     * Show family members management.
     */
    public function members(Family $family): Response
    {
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

        return Inertia::render('SuperAdmin/Families/Members', [
            'family' => $family,
            'members' => $members,
        ]);
    }

    /**
     * Remove a member from the family.
     */
    public function removeMember(Request $request, Family $family): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['user' => 'User does not belong to this family.']);
        }

        // Check if this is the last admin
        $adminCount = $family->users()->whereIn('role', ['admin', 'super-admin'])->count();
        if ($user->isAdmin() && $adminCount <= 1) {
            return redirect()->back()
                ->withErrors(['user' => 'Cannot remove the last admin from the family.']);
        }

        $user->update(['family_id' => null, 'role' => 'member']);

        return redirect()->back()
            ->with('success', 'Member removed from family successfully.');
    }

    /**
     * Promote a member to admin.
     */
    public function promoteMember(Request $request, Family $family): RedirectResponse
    {
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
    public function demoteMember(Request $request, Family $family): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if ($user->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['user' => 'User does not belong to this family.']);
        }

        // Check if this is the last admin
        $adminCount = $family->users()->whereIn('role', ['admin', 'super-admin'])->count();
        if ($user->isAdmin() && $adminCount <= 1) {
            return redirect()->back()
                ->withErrors(['user' => 'Cannot demote the last admin in the family.']);
        }

        if ($user->role === 'admin') {
            $user->update(['role' => 'member']);
        }

        return redirect()->back()
            ->with('success', 'Admin demoted to member successfully.');
    }
}