<?php

namespace App\Services;

use App\Enums\ChoreSourceType;
use App\Models\ChoreCompletion;

class PointsCalculationService
{
    /**
     * @return array{final_points:int, primary_user_id:int, allocations:array<int, int>}
     */
    public function calculate(ChoreCompletion $completion, ?int $supervisorAdjustedPoints = null): array
    {
        $completion->loadMissing(['participants', 'choreInstance']);

        $participantIds = $completion->participants
            ->pluck('user_id')
            ->map(static fn (mixed $userId): int => (int) $userId)
            ->values()
            ->all();

        $primaryUserId = (int) ($completion->participants
            ->firstWhere('is_primary', true)?->user_id ?? $completion->completed_by_user_id);

        $finalPoints = $this->resolveFinalPoints($completion, $supervisorAdjustedPoints);
        $allocations = [];

        if ($participantIds === []) {
            return [
                'final_points' => $finalPoints,
                'primary_user_id' => $primaryUserId,
                'allocations' => $allocations,
            ];
        }

        $participantCount = count($participantIds);

        if ($participantCount > $finalPoints) {
            foreach ($participantIds as $participantId) {
                $allocations[$participantId] = $participantId === $primaryUserId ? $finalPoints : 0;
            }

            return [
                'final_points' => $finalPoints,
                'primary_user_id' => $primaryUserId,
                'allocations' => $allocations,
            ];
        }

        $baseShare = intdiv($finalPoints, $participantCount);
        $remainder = $finalPoints % $participantCount;

        foreach ($participantIds as $participantId) {
            $allocations[$participantId] = $baseShare;
        }

        $allocations[$primaryUserId] = ($allocations[$primaryUserId] ?? 0) + $remainder;

        return [
            'final_points' => $finalPoints,
            'primary_user_id' => $primaryUserId,
            'allocations' => $allocations,
        ];
    }

    private function resolveFinalPoints(ChoreCompletion $completion, ?int $supervisorAdjustedPoints): int
    {
        if ($supervisorAdjustedPoints !== null) {
            return max($supervisorAdjustedPoints, 0);
        }

        $instance = $completion->choreInstance;
        $basePoints = (int) $instance->base_points;
        $completedAt = $completion->completed_at;
        $deadlineAt = $instance->deadline_at;
        $sourceType = $instance->source_type;
        $sourceTypeValue = $sourceType instanceof ChoreSourceType ? $sourceType->value : (string) $sourceType;

        if (
            $sourceTypeValue === ChoreSourceType::AdHoc->value
            && $deadlineAt !== null
            && $completedAt !== null
            && $completedAt->greaterThan($deadlineAt)
        ) {
            return intdiv($basePoints, 2);
        }

        return $basePoints;
    }
}
