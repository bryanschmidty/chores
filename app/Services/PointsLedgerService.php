<?php

namespace App\Services;

use App\Models\ChoreCompletion;
use App\Models\PointsLedger;
use Illuminate\Support\Facades\Log;

class PointsLedgerService
{
    public function recordApproval(ChoreCompletion $completion, int $awardedByUserId): void
    {
        $completion->loadMissing('participants');
        $awardedAt = $completion->approved_at ?? now();
        $entries = [];

        foreach ($completion->participants as $participant) {
            PointsLedger::query()->create([
                'household_id' => $completion->household_id,
                'user_id' => $participant->user_id,
                'chore_completion_id' => $completion->id,
                'awarded_by_user_id' => $awardedByUserId,
                'points' => (int) ($participant->points_awarded ?? 0),
                'awarded_at' => $awardedAt,
                'entry_type' => 'approval',
                'description' => null,
            ]);

            $entries[] = [
                'user_id' => (int) $participant->user_id,
                'points' => (int) ($participant->points_awarded ?? 0),
            ];
        }

        Log::info('points.awarded', [
            'household_id' => (int) $completion->household_id,
            'chore_completion_id' => (int) $completion->id,
            'chore_instance_id' => (int) $completion->chore_instance_id,
            'awarded_by_user_id' => $awardedByUserId,
            'awarded_at' => $awardedAt->toIso8601String(),
            'entries' => $entries,
            'total_points' => collect($entries)->sum('points'),
        ]);
    }
}
