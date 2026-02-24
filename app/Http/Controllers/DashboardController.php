<?php

namespace App\Http\Controllers;

use App\Models\AvailableChore;
use App\Models\Chore;
use App\Models\ChoreCompletion;
use App\Models\FamilyGoal;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role.
     */
    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        // Check if user has a family
        if (!$user->family_id) {
            return redirect()->route('family.setup');
        }

        if ($user->isAdmin()) {
            // If admin has no other family members, redirect to family management page
            if ($user->family->users()->count() === 1) {
                return redirect()->route('admin.family.index');
            }
            return $this->adminDashboard();
        }

        return $this->memberDashboard();
    }

    /**
     * Super admin dashboard.
     */
    private function superAdminDashboard(): Response
    {
        // Super admin can see all families and system-wide stats
        $stats = [
            'total_families' => \App\Models\Family::count(),
            'total_users' => \App\Models\User::count(),
            'total_chores' => Chore::count(),
            'total_completions' => ChoreCompletion::count(),
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Family admin dashboard.
     */
    private function adminDashboard(): Response
    {
        $user = auth()->user();
        $family = $user->family;

        // Count available chores that haven't been assigned yet
        $availableChoreIds = AvailableChore::forFamily($family->id)
            ->forToday()
            ->pluck('chore_id');
        
        $assignedChoreIds = \App\Models\AssignedChore::forFamily($family->id)
            ->whereIn('chore_id', $availableChoreIds)
            ->whereDate('due_date', today())
            ->pluck('chore_id');
        
        $unassignedAvailableChores = $availableChoreIds->diff($assignedChoreIds)->count();

        // Get family stats
        $stats = [
            'total_members' => $family->users()->count(),
            'pending_verifications' => ChoreCompletion::forFamily($family->id)->pending()->count(),
            'total_chores' => $family->chores()->count(),
            'active_chores' => $family->assignedChores()->pending()->count(),
            'total_assigned_chores' => $family->assignedChores()->pending()->count(),
            'overdue_chores' => $family->assignedChores()->overdue()->count(),
            'total_shop_items' => $family->shopItems()->active()->count(),
            'pending_redemptions' => Redemption::forFamily($family->id)->pending()->count(),
            'available_chores' => $unassignedAvailableChores,
        ];

        // Get recent activity
        $recentCompletions = ChoreCompletion::forFamily($family->id)
            ->with(['assignedChore.chore', 'user', 'verifier'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingVerifications = ChoreCompletion::forFamily($family->id)
            ->pending()
            ->with(['assignedChore.chore', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        $pendingRedemptions = Redemption::forFamily($family->id)
            ->pending()
            ->with(['shopItem', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Get today's assigned chores
        $todaysChores = $family->assignedChores()
            ->dueToday()
            ->with(['chore', 'assignedTo'])
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentCompletions' => $recentCompletions,
            'pendingVerifications' => $pendingVerifications,
            'pendingRedemptions' => $pendingRedemptions,
            'todaysChores' => $todaysChores,
        ]);
    }

    /**
     * Family member dashboard.
     */
    private function memberDashboard(): Response
    {
        $user = auth()->user();
        $family = $user->family;

        // Get user's points balance
        $pointsBalance = $user->getPointsBalance();

        // Get user's chores
        $myChores = [
            'overdue' => $user->assignedChores()->overdue()->with(['chore'])->get(),
            'today' => $user->assignedChores()->dueToday()->with(['chore'])->get(),
            'upcoming' => $user->assignedChores()
                ->pending()
                ->whereDate('due_date', '>', today())
                ->with(['chore'])
                ->limit(5)
                ->get(),
        ];

        // Get recent completions
        $recentCompletions = $user->choreCompletions()
            ->with(['assignedChore.chore'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent point transactions
        $recentTransactions = $user->pointTransactions()
            ->with(['relatedModel'])
            ->latest()
            ->limit(10)
            ->get();

        // Get available shop items
        $shopItems = $family->shopItems()
            ->available()
            ->with(['redemptions' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->limit(6)
            ->get();

        // Get active family goals
        $familyGoals = $family->familyGoals()
            ->active()
            ->get();

        // Count available chores that haven't been assigned yet (to anyone)
        $availableChoreIds = AvailableChore::forFamily($family->id)
            ->forToday()
            ->pluck('chore_id');
        
        $assignedChoreIds = \App\Models\AssignedChore::forFamily($family->id)
            ->whereIn('chore_id', $availableChoreIds)
            ->whereDate('due_date', today())
            ->pluck('chore_id');
        
        $unassignedAvailableChores = $availableChoreIds->diff($assignedChoreIds)->count();

        return Inertia::render('Member/Dashboard', [
            'pointsBalance' => $pointsBalance,
            'myChores' => $myChores,
            'recentCompletions' => $recentCompletions,
            'recentTransactions' => $recentTransactions,
            'shopItems' => $shopItems,
            'familyGoals' => $familyGoals,
            'availableChores' => $unassignedAvailableChores,
        ]);
    }
}