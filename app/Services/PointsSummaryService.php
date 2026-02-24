<?php

namespace App\Services;

use App\Models\ChoreCompletion;
use App\Models\PointsLedger;
use App\Models\User;
use Illuminate\Support\Collection;

class PointsSummaryService
{
    /**
     * @return Collection<int, object>
     */
    public function leaderboardForHousehold(int $householdId): Collection
    {
        $totalsSubquery = PointsLedger::query()
            ->selectRaw('user_id, SUM(points) as total_points')
            ->where('household_id', $householdId)
            ->groupBy('user_id');

        return User::query()
            ->where('household_id', $householdId)
            ->leftJoinSub($totalsSubquery, 'points_totals', static function ($join): void {
                $join->on('users.id', '=', 'points_totals.user_id');
            })
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('COALESCE(points_totals.total_points, 0) as total_points')
            ->orderByDesc('total_points')
            ->orderBy('users.name')
            ->get();
    }

    /**
     * @return Collection<int, ChoreCompletion>
     */
    public function completionHistoryForHousehold(int $householdId): Collection
    {
        return ChoreCompletion::query()
            ->where('household_id', $householdId)
            ->with([
                'choreInstance:id,household_id,title,status',
                'completedBy:id,name',
                'approvedBy:id,name',
                'participants.user:id,name',
            ])
            ->latest('completed_at')
            ->get();
    }
}
