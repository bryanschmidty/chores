<?php

namespace App\Http\Controllers;

use App\Models\AssignedChore;
use App\Models\Chore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssignChoreController extends Controller
{
    /**
     * Display the assign chores page.
     */
    public function index(Request $request): Response
    {
        $family = auth()->user()->family;
        
        // Get week start date (Monday)
        $weekStartDate = $request->get('week', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));
        $weekStart = Carbon::parse($weekStartDate);
        $weekEnd = $weekStart->copy()->addDays(6); // Saturday

        // Get family members
        $familyMembers = $family->users()
            ->where('role', 'member')
            ->orderBy('name')
            ->get();

        // Get available chores for this week
        $availableChores = $this->getAvailableChores($family, $weekStart);

        // Get assignments for this week
        $assignments = AssignedChore::forFamily($family->id)
            ->dueThisWeek($weekStartDate)
            ->with(['chore', 'assignedTo'])
            ->get()
            ->groupBy('assigned_to');

        return Inertia::render('Admin/AssignChores/Index', [
            'familyMembers' => $familyMembers,
            'availableChores' => $availableChores,
            'assignments' => $assignments,
            'weekStartDate' => $weekStartDate,
            'weekEndDate' => $weekEnd->format('Y-m-d'),
        ]);
    }

    /**
     * Assign a chore to a family member.
     */
    public function assign(Request $request)
    {
        $family = auth()->user()->family;

        $request->validate([
            'chore_id' => 'required|exists:chores,id',
            'assigned_to' => 'required|exists:users,id',
            'week_start_date' => 'required|date',
        ]);

        $chore = Chore::find($request->chore_id);
        
        // Validate chore belongs to family
        if ($chore->family_id !== $family->id) {
            abort(403);
        }

        $assignedUser = User::find($request->assigned_to);
        
        // Validate user belongs to family and is a member
        if ($assignedUser->family_id !== $family->id || $assignedUser->role !== 'member') {
            return redirect()->back()
                ->withErrors(['assigned_to' => 'User must be a member of your family.']);
        }

        $weekStart = Carbon::parse($request->week_start_date);
        $weekEnd = $weekStart->copy()->addDays(6);

        // Create assigned chore records based on frequency
        $this->createAssignedChoreRecords($chore, $assignedUser, $weekStart);

        return redirect()->back()
            ->with('success', 'Chore assigned successfully.');
    }

    /**
     * Remove an assigned chore.
     */
    public function unassign(AssignedChore $assignedChore): RedirectResponse
    {
        // Check if assigned chore belongs to user's family
        if ($assignedChore->family_id !== auth()->user()->family_id) {
            abort(403);
        }

        // Only allow unassigning if not completed
        if ($assignedChore->status === 'completed') {
            return redirect()->back()
                ->withErrors(['chore' => 'Cannot remove completed chore assignment.']);
        }

        $assignedChore->delete();

        return redirect()->back()
            ->with('success', 'Chore assignment removed successfully.');
    }

    /**
     * Create and assign an ad hoc chore.
     */
    public function createAdhoc(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:1|max:1000',
            'assigned_to' => 'required|exists:users,id',
            'review_required' => 'boolean',
            'photos_required' => 'required|in:none,before_and_after,only_after',
            'due_date' => 'required|date|after_or_equal:today',
        ]);

        $assignedUser = User::find($request->assigned_to);
        
        // Validate user belongs to family and is a member
        if ($assignedUser->family_id !== $family->id || $assignedUser->role !== 'member') {
            return redirect()->back()
                ->withErrors(['assigned_to' => 'User must be a member of your family.']);
        }

        // Create ad hoc chore
        $chore = Chore::create([
            'family_id' => $family->id,
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'frequency' => 'adhoc',
            'review_required' => $request->boolean('review_required'),
            'photos_required' => $request->photos_required,
        ]);

        // Create single assigned chore record
        AssignedChore::create([
            'chore_id' => $chore->id,
            'family_id' => $family->id,
            'assigned_to' => $request->assigned_to,
            'assigned_by' => auth()->id(),
            'due_date' => $request->due_date,
            'week_start_date' => Carbon::parse($request->due_date)->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
            'status' => 'pending',
        ]);

        return redirect()->back()
            ->with('success', 'Ad hoc chore created and assigned successfully.');
    }

    /**
     * Search for previous ad hoc chores.
     */
    public function searchAdhoc(Request $request)
    {
        $family = auth()->user()->family;

        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $chores = Chore::forFamily($family->id)
            ->where('frequency', 'adhoc')
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'description', 'points', 'review_required', 'photos_required']);

        return response()->json($chores);
    }

    /**
     * Get available chores for the week.
     */
    private function getAvailableChores($family, Carbon $weekStart): array
    {
        $availableChores = [];
        $weekEnd = $weekStart->copy()->addDays(6);

        // Get all chores for the family
        $chores = Chore::forFamily($family->id)->get();

        // Get all assigned chores for this week to filter them out
        $assignedChoreIds = AssignedChore::forFamily($family->id)
            ->dueThisWeek($weekStart->format('Y-m-d'))
            ->pluck('chore_id')
            ->toArray();

        foreach ($chores as $chore) {
            // Skip ad hoc chores - they're created on demand
            if ($chore->frequency === 'adhoc') {
                continue;
            }

            // Skip chores that have already been assigned this week
            if (in_array($chore->id, $assignedChoreIds)) {
                continue;
            }

            // Check if chore should be available this week
            if ($this->shouldShowChoreThisWeek($chore, $weekStart, $weekEnd)) {
                $frequency = $chore->getFrequencyText();
                
                if (!isset($availableChores[$frequency])) {
                    $availableChores[$frequency] = [];
                }

                $availableChores[$frequency][] = [
                    'id' => $chore->id,
                    'name' => $chore->name,
                    'points' => $chore->points,
                    'frequency' => $chore->frequency,
                ];
            }
        }

        return $availableChores;
    }

    /**
     * Check if a chore should be shown for the given week.
     */
    private function shouldShowChoreThisWeek(Chore $chore, Carbon $weekStart, Carbon $weekEnd): bool
    {
        switch ($chore->frequency) {
            case 'daily':
            case 'twice_weekly':
            case 'weekly':
                // These are always shown
                return true;

            case 'twice_monthly':
                // Check if 2 weeks have passed since last completion
                $lastCompletion = AssignedChore::where('chore_id', $chore->id)
                    ->where('status', 'completed')
                    ->latest('due_date')
                    ->first();

                if (!$lastCompletion) {
                    return true; // Never been completed, show it
                }

                $twoWeeksAgo = $weekStart->copy()->subWeeks(2);
                return $lastCompletion->due_date <= $twoWeeksAgo;

            case 'monthly':
                // Show on first week of each month
                return $weekStart->day <= 7;

            default:
                return false;
        }
    }

    /**
     * Create assigned chore records based on frequency.
     */
    private function createAssignedChoreRecords(Chore $chore, User $assignedUser, Carbon $weekStart): void
    {
        $records = [];

        switch ($chore->frequency) {
            case 'daily':
                // Monday through Saturday (6 days)
                for ($i = 0; $i < 6; $i++) {
                    $dueDate = $weekStart->copy()->addDays($i);
                    $records[] = [
                        'chore_id' => $chore->id,
                        'family_id' => $chore->family_id,
                        'assigned_to' => $assignedUser->id,
                        'assigned_by' => auth()->id(),
                        'due_date' => $dueDate->format('Y-m-d'),
                        'week_start_date' => $weekStart->format('Y-m-d'),
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                break;

            case 'twice_weekly':
                // Tuesday and Friday
                $records[] = [
                    'chore_id' => $chore->id,
                    'family_id' => $chore->family_id,
                    'assigned_to' => $assignedUser->id,
                    'assigned_by' => auth()->id(),
                    'due_date' => $weekStart->copy()->addDay()->format('Y-m-d'), // Tuesday
                    'week_start_date' => $weekStart->format('Y-m-d'),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $records[] = [
                    'chore_id' => $chore->id,
                    'family_id' => $chore->family_id,
                    'assigned_to' => $assignedUser->id,
                    'assigned_by' => auth()->id(),
                    'due_date' => $weekStart->copy()->addDays(4)->format('Y-m-d'), // Friday
                    'week_start_date' => $weekStart->format('Y-m-d'),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                break;

            case 'weekly':
                // Monday
                $records[] = [
                    'chore_id' => $chore->id,
                    'family_id' => $chore->family_id,
                    'assigned_to' => $assignedUser->id,
                    'assigned_by' => auth()->id(),
                    'due_date' => $weekStart->format('Y-m-d'),
                    'week_start_date' => $weekStart->format('Y-m-d'),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                break;

            case 'twice_monthly':
                // Friday of this week
                $records[] = [
                    'chore_id' => $chore->id,
                    'family_id' => $chore->family_id,
                    'assigned_to' => $assignedUser->id,
                    'assigned_by' => auth()->id(),
                    'due_date' => $weekStart->copy()->addDays(4)->format('Y-m-d'), // Friday
                    'week_start_date' => $weekStart->format('Y-m-d'),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                break;

            case 'monthly':
                // First Monday of the month
                $records[] = [
                    'chore_id' => $chore->id,
                    'family_id' => $chore->family_id,
                    'assigned_to' => $assignedUser->id,
                    'assigned_by' => auth()->id(),
                    'due_date' => $weekStart->format('Y-m-d'),
                    'week_start_date' => $weekStart->format('Y-m-d'),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                break;
        }

        // Insert all records
        if (!empty($records)) {
            AssignedChore::insert($records);
        }
    }
}
