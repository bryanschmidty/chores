<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\ChoreCompletion;
use App\Models\FamilyGoal;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Models\ShopItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role.
     */
    public function index(): Response
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
            // If admin has no other family members, redirect to family members page
            if ($user->family->users()->count() === 1) {
                return redirect()->route('admin.family.members');
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

        // Get family stats
        $stats = [
            'total_members' => $family->users()->count(),
            'pending_verifications' => ChoreCompletion::forFamily($family->id)->pending()->count(),
            'total_chores' => $family->chores()->count(),
            'active_chores' => $family->chores()->pending()->count(),
            'overdue_chores' => $family->chores()->overdue()->count(),
            'total_shop_items' => $family->shopItems()->active()->count(),
            'pending_redemptions' => Redemption::forFamily($family->id)->pending()->count(),
        ];

        // Get recent activity
        $recentCompletions = ChoreCompletion::forFamily($family->id)
            ->with(['chore', 'user', 'verifier'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingVerifications = ChoreCompletion::forFamily($family->id)
            ->pending()
            ->with(['chore', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        $pendingRedemptions = Redemption::forFamily($family->id)
            ->pending()
            ->with(['shopItem', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Get today's chores
        $todaysChores = $family->chores()
            ->dueToday()
            ->with(['assignedTo', 'template'])
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
            'overdue' => $user->assignedChores()->overdue()->with(['template'])->get(),
            'today' => $user->assignedChores()->dueToday()->with(['template'])->get(),
            'upcoming' => $user->assignedChores()
                ->pending()
                ->whereDate('next_due_date', '>', today())
                ->with(['template'])
                ->limit(5)
                ->get(),
        ];

        // Get recent completions
        $recentCompletions = $user->choreCompletions()
            ->with(['chore'])
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

        return Inertia::render('Member/Dashboard', [
            'pointsBalance' => $pointsBalance,
            'myChores' => $myChores,
            'recentCompletions' => $recentCompletions,
            'recentTransactions' => $recentTransactions,
            'shopItems' => $shopItems,
            'familyGoals' => $familyGoals,
        ]);
    }
}