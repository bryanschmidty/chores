<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AssignedChore;
use App\Models\AvailableChore;
use App\Models\Chore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChoresController extends Controller
{
    /**
     * Display available chores and assigned chores for the member or admin.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $family = $user->family;
        $today = today();
        $isAdmin = $user->isAdmin();

        // Get available chores for today that haven't been assigned
        $availableChoreIds = AvailableChore::forFamily($family->id)
            ->forToday()
            ->with(['chore'])
            ->get()
            ->pluck('chore_id');

        $assignedChoreIds = AssignedChore::forFamily($family->id)
            ->whereIn('chore_id', $availableChoreIds)
            ->whereDate('due_date', $today)
            ->pluck('chore_id');

        $availableChores = AvailableChore::forFamily($family->id)
            ->forToday()
            ->with(['chore'])
            ->get()
            ->filter(function ($availableChore) use ($assignedChoreIds) {
                return !$assignedChoreIds->contains($availableChore->chore_id);
            })
            ->map(function ($availableChore) {
                return [
                    'id' => $availableChore->id,
                    'chore_id' => $availableChore->chore_id,
                    'chore' => [
                        'id' => $availableChore->chore->id,
                        'name' => $availableChore->chore->name,
                        'description' => $availableChore->chore->description,
                        'points' => $availableChore->chore->points,
                        'frequency' => $availableChore->chore->frequency,
                        'review_required' => $availableChore->chore->review_required,
                        'photos_required' => $availableChore->chore->photos_required,
                    ],
                ];
            })
            ->values();

        // Get assigned chores - for admins, show all family assigned chores; for members, show only their own
        if ($isAdmin) {
            $assignedChores = $family->assignedChores()
                ->with(['chore', 'assignedTo'])
                ->orderBy('due_date')
                ->get()
                ->groupBy(function ($assignedChore) {
                    if ($assignedChore->status === 'completed') {
                        return 'completed';
                    }
                    if ($assignedChore->isOverdue()) {
                        return 'overdue';
                    }
                    if ($assignedChore->isDueToday()) {
                        return 'today';
                    }
                    if ($assignedChore->due_date && $assignedChore->due_date->isFuture()) {
                        return 'upcoming';
                    }
                    return 'past';
                });
        } else {
            $assignedChores = $user->assignedChores()
                ->with(['chore'])
                ->orderBy('due_date')
                ->get()
                ->groupBy(function ($assignedChore) {
                    if ($assignedChore->status === 'completed') {
                        return 'completed';
                    }
                    if ($assignedChore->isOverdue()) {
                        return 'overdue';
                    }
                    if ($assignedChore->isDueToday()) {
                        return 'today';
                    }
                    if ($assignedChore->due_date && $assignedChore->due_date->isFuture()) {
                        return 'upcoming';
                    }
                    return 'past';
                });
        }

        // Get family members (kids) for admin assignment modal
        $familyMembers = $isAdmin ? $family->users()
            ->where('role', 'member')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                ];
            })
            ->values() : collect();

        return Inertia::render('Member/Chores', [
            'availableChores' => $availableChores,
            'assignedChores' => [
                'overdue' => $assignedChores->get('overdue', collect()),
                'today' => $assignedChores->get('today', collect()),
                'upcoming' => $assignedChores->get('upcoming', collect()),
                'completed' => $assignedChores->get('completed', collect())->take(10),
            ],
            'isAdmin' => $isAdmin,
            'familyMembers' => $familyMembers,
        ]);
    }

    /**
     * Assign an available chore to a user (self for members, or specified user for admins).
     */
    public function assign(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $family = $user->family;
        $isAdmin = $user->isAdmin();

        $request->validate([
            'available_chore_id' => 'required|exists:available_chores,id',
            'assigned_to' => $isAdmin ? 'required|exists:users,id' : 'nullable',
        ]);

        $availableChore = AvailableChore::with('chore')->findOrFail($request->available_chore_id);

        // Verify the available chore belongs to the user's family
        if ($availableChore->family_id !== $family->id) {
            abort(403, 'This chore does not belong to your family.');
        }

        // Determine who to assign to
        $assignedToId = $isAdmin ? $request->assigned_to : $user->id;

        // Verify the assigned user belongs to the family and is a member (for admin assignments)
        if ($isAdmin) {
            $assignedUser = \App\Models\User::findOrFail($assignedToId);
            if ($assignedUser->family_id !== $family->id || $assignedUser->role !== 'member') {
                return redirect()->back()
                    ->withErrors(['assigned_to' => 'User must be a member of your family.']);
            }
        }

        // Check if this chore is already assigned to the user today
        $alreadyAssigned = AssignedChore::where('chore_id', $availableChore->chore_id)
            ->where('assigned_to', $assignedToId)
            ->whereDate('due_date', today())
            ->exists();

        if ($alreadyAssigned) {
            return redirect()->back()
                ->withErrors(['chore' => 'This chore is already assigned to this user today.']);
        }

        // Create assigned chore record
        $weekStart = today()->startOfWeek(Carbon::MONDAY);

        AssignedChore::create([
            'chore_id' => $availableChore->chore_id,
            'family_id' => $family->id,
            'assigned_to' => $assignedToId,
            'assigned_by' => $user->id,
            'due_date' => today()->format('Y-m-d'),
            'week_start_date' => $weekStart->format('Y-m-d'),
            'status' => 'pending',
        ]);

        return redirect()->back()
            ->with('success', 'Chore assigned successfully!');
    }
}
