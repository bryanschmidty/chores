<?php

namespace App\Services;

use App\Models\ChoreCompletion;
use App\Models\PointsLedger;

class PointsLedgerService
{
    public function recordApproval(ChoreCompletion $completion, int $awardedByUserId): void
    {
        $completion->loadMissing('participants');
        $awardedAt = $completion->approved_at ?? now();

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
        }
    }
}
