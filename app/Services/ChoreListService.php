<?php

namespace App\Services;

use App\Models\ChoreInstance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ChoreListService
{
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
}
