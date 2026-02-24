<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Http\Requests\ApproveChoreCompletionRequest;
use App\Http\Requests\RejectChoreCompletionRequest;
use App\Http\Requests\StoreChoreCompletionRequest;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
use App\Models\ChoreInstance;
use App\Services\ChoreRecurrenceService;
use App\Services\PointsCalculationService;
use App\Services\PointsLedgerService;
use App\Services\PointsSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChoreCompletionController extends Controller
{
    public function store(StoreChoreCompletionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $choreInstance = ChoreInstance::query()
            ->where('household_id', $request->user()->household_id)
            ->findOrFail((int) $validated['chore_instance_id']);

        $pendingExists = ChoreCompletion::query()
            ->where('chore_instance_id', $choreInstance->id)
            ->where('approval_status', ApprovalStatus::Pending->value)
            ->exists();

        if ($pendingExists || $choreInstance->status === ChoreInstanceStatus::PendingApproval) {
            throw ValidationException::withMessages([
                'chore_instance_id' => 'This chore already has a pending completion submission.',
            ]);
        }

        if ($choreInstance->status === ChoreInstanceStatus::Approved) {
            throw ValidationException::withMessages([
                'chore_instance_id' => 'This chore has already been approved.',
            ]);
        }

        $helperUserIds = collect($validated['helper_user_ids'] ?? [])
            ->map(static fn (mixed $helperUserId): int => (int) $helperUserId)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($request, $choreInstance, $helperUserIds): void {
            $completion = ChoreCompletion::query()->create([
                'household_id' => $request->user()->household_id,
                'chore_instance_id' => $choreInstance->id,
                'completed_by_user_id' => $request->user()->id,
                'completed_at' => now(),
                'approval_status' => ApprovalStatus::Pending->value,
            ]);

            ChoreCompletionParticipant::query()->create([
                'chore_completion_id' => $completion->id,
                'user_id' => $request->user()->id,
                'is_primary' => true,
            ]);

            foreach ($helperUserIds as $helperUserId) {
                ChoreCompletionParticipant::query()->create([
                    'chore_completion_id' => $completion->id,
                    'user_id' => $helperUserId,
                    'is_primary' => false,
                ]);
            }

            $choreInstance->update([
                'status' => ChoreInstanceStatus::PendingApproval->value,
            ]);
        });

        return redirect()->back()->with('status', 'Chore completion submitted for approval.');
    }

    public function approve(
        ApproveChoreCompletionRequest $request,
        ChoreCompletion $choreCompletion,
        PointsCalculationService $pointsCalculationService,
        PointsLedgerService $pointsLedgerService
    ): RedirectResponse {
        if ($choreCompletion->approval_status !== ApprovalStatus::Pending) {
            throw ValidationException::withMessages([
                'chore_completion' => 'Only pending submissions can be approved.',
            ]);
        }

        $validated = $request->validated();
        $now = now();

        DB::transaction(function () use (
            $request,
            $choreCompletion,
            $pointsCalculationService,
            $pointsLedgerService,
            $validated,
            $now
        ): void {
            $choreCompletion->loadMissing(['participants', 'choreInstance.template']);

            $pointsResult = $pointsCalculationService->calculate(
                $choreCompletion,
                isset($validated['supervisor_adjusted_points']) ? (int) $validated['supervisor_adjusted_points'] : null
            );

            foreach ($choreCompletion->participants as $participant) {
                $participant->update([
                    'points_awarded' => $pointsResult['allocations'][(int) $participant->user_id] ?? 0,
                ]);
            }

            $choreCompletion->update([
                'approval_status' => ApprovalStatus::Approved->value,
                'approved_by_user_id' => $request->user()->id,
                'approved_at' => $now,
                'supervisor_adjusted_points' => $validated['supervisor_adjusted_points'] ?? null,
                'approval_comment' => $validated['approval_comment'] ?? null,
                'rejection_reason' => null,
            ]);

            $choreCompletion->choreInstance->update([
                'status' => ChoreInstanceStatus::Approved->value,
                'adjusted_points' => $pointsResult['final_points'],
            ]);

            if ($choreCompletion->choreInstance->template !== null) {
                $choreCompletion->choreInstance->template->update([
                    'last_completed_at' => $choreCompletion->completed_at,
                ]);
            }

            $pointsLedgerService->recordApproval($choreCompletion->fresh(['participants']), $request->user()->id);
        });

        return redirect()->back()->with('status', 'Chore completion approved.');
    }

    public function reject(
        RejectChoreCompletionRequest $request,
        ChoreCompletion $choreCompletion,
        ChoreRecurrenceService $choreRecurrenceService
    ): RedirectResponse {
        if ($choreCompletion->approval_status !== ApprovalStatus::Pending) {
            throw ValidationException::withMessages([
                'chore_completion' => 'Only pending submissions can be rejected.',
            ]);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($choreCompletion, $validated, $choreRecurrenceService): void {
            $choreCompletion->loadMissing('choreInstance');

            $choreCompletion->update([
                'approval_status' => ApprovalStatus::Rejected->value,
                'rejection_reason' => $validated['rejection_reason'],
                'approved_by_user_id' => null,
                'approved_at' => null,
                'approval_comment' => null,
                'supervisor_adjusted_points' => null,
            ]);

            $dueAt = $choreCompletion->choreInstance->due_at;
            $status = $dueAt === null
                ? ChoreInstanceStatus::Due
                : $choreRecurrenceService->statusForDate($dueAt, now());

            $choreCompletion->choreInstance->update([
                'status' => $status->value,
            ]);
        });

        return redirect()->back()->with('status', 'Chore completion rejected.');
    }

    public function leaderboard(Request $request, PointsSummaryService $pointsSummaryService): Response
    {
        $leaderboard = $pointsSummaryService->leaderboardForHousehold((int) $request->user()->household_id);

        return response($leaderboard->toJson(), 200, ['Content-Type' => 'application/json']);
    }

    public function history(Request $request, PointsSummaryService $pointsSummaryService): Response
    {
        $history = $pointsSummaryService->completionHistoryForHousehold((int) $request->user()->household_id);

        return response($history->toJson(), 200, ['Content-Type' => 'application/json']);
    }
}
