<?php

namespace App\Services;

use App\Enums\ChoreInstanceStatus;
use App\Models\ChoreInstance;
use App\Models\ChoreTemplate;
use App\Models\User;
use App\Models\WeeklyClaim;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ChoreListService
{
    public function __construct(private readonly ChoreRecurrenceService $choreRecurrenceService) {}

    /**
     * @return Collection<int, ChoreInstance>
     */
    public function defaultKidList(User $user, ?CarbonInterface $asOf = null): Collection
    {
        $now = $asOf !== null ? CarbonImmutable::instance($asOf) : CarbonImmutable::now();

        $myAssigned = ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->assignedToUser($user->id)
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        $open = ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->openDueOrOverdue()
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        $futureRecurring = ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->futureRecurringWindow($now, 7)
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        return collect([$myAssigned, $open, $futureRecurring])
            ->flatten(1)
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, ChoreInstance>
     */
    public function myChores(User $user, ?CarbonInterface $asOf = null): Collection
    {
        $today = ($asOf !== null ? CarbonImmutable::instance($asOf) : CarbonImmutable::now())->startOfDay();

        return ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->assignedToUser($user->id)
            ->whereNotIn('status', [
                ChoreInstanceStatus::PendingApproval->value,
                ChoreInstanceStatus::Approved->value,
            ])
            ->where(function ($query) use ($today): void {
                $query->whereDate('due_at', $today->toDateString())
                    ->orWhereNull('due_at');
            })
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ChoreInstance>
     */
    public function myCompletedChores(User $user): Collection
    {
        return ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->assignedToUser($user->id)
            ->whereIn('status', [
                ChoreInstanceStatus::PendingApproval->value,
                ChoreInstanceStatus::Approved->value,
            ])
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->orderByDesc('updated_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ChoreInstance>
     */
    public function myWeeklyChores(User $user, ?CarbonInterface $asOf = null): Collection
    {
        $today = ($asOf !== null ? CarbonImmutable::instance($asOf) : CarbonImmutable::now())->startOfDay();
        $weekStart = $today->startOfWeek();
        $weekEnd = $today->endOfWeek();

        return ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->assignedToUser($user->id)
            ->whereDate('due_at', '>=', $weekStart->toDateString())
            ->whereDate('due_at', '<=', $weekEnd->toDateString())
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ChoreInstance>
     */
    public function unclaimedInstancesForToday(User $user, ?CarbonInterface $asOf = null): Collection
    {
        $today = ($asOf !== null ? CarbonImmutable::instance($asOf) : CarbonImmutable::now())->startOfDay();

        return ChoreInstance::query()
            ->forHousehold($user->household_id)
            ->open()
            ->where(function ($query) use ($today): void {
                $query->whereDate('due_at', $today->toDateString())
                    ->orWhereNull('due_at');
            })
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ChoreTemplate>
     */
    public function unclaimedRecurringTemplatesForWeek(User $user, ?CarbonInterface $asOf = null): Collection
    {
        $today = ($asOf !== null ? CarbonImmutable::instance($asOf) : CarbonImmutable::now())->startOfDay();
        $weekStart = $today->startOfWeek();
        $weekEnd = $today->endOfWeek();

        $claimedTemplateIds = WeeklyClaim::query()
            ->where('household_id', $user->household_id)
            ->whereDate('week_start_at', $weekStart->toDateString())
            ->pluck('chore_template_id')
            ->all();

        return ChoreTemplate::query()
            ->where('household_id', $user->household_id)
            ->where('is_active', true)
            ->whereNotIn('id', $claimedTemplateIds)
            ->with('defaultAssignee:id,name')
            ->orderBy('title')
            ->get()
            ->filter(function (ChoreTemplate $template) use ($today, $weekEnd): bool {
                return $this->choreRecurrenceService
                    ->dueDatesInWindow($template, $today, $weekEnd)
                    ->isNotEmpty();
            })
            ->values();
    }
}
