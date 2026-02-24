<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyClaimRequest;
use App\Http\Requests\UpdateWeeklyClaimRequest;
use App\Models\User;
use App\Models\WeeklyClaim;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class WeeklyClaimController extends Controller
{
    public function store(StoreWeeklyClaimRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $assignedToUser = User::query()->findOrFail($validated['assigned_to_user_id']);

        if (! $assignedToUser->hasRole('kid')) {
            return redirect()->back()->withErrors([
                'assigned_to_user_id' => 'Weekly claims can only be assigned to users with the kid role.',
            ]);
        }

        $weekStart = CarbonImmutable::now()->startOfWeek()->toDateString();
        $weekEnd = CarbonImmutable::now()->endOfWeek()->toDateString();

        $existingClaim = WeeklyClaim::query()
            ->where('chore_template_id', $validated['chore_template_id'])
            ->whereDate('week_start_at', $weekStart)
            ->first();

        if ($existingClaim !== null) {
            if (! $user->hasRole('supervisor')) {
                abort(403);
            }

            $existingClaim->update([
                'assigned_to_user_id' => $assignedToUser->id,
                'claimed_by_user_id' => $user->id,
                'overridden_by_user_id' => $user->id,
                'week_end_at' => $weekEnd,
            ]);

            return redirect()->back()->with('status', 'Weekly claim overridden.');
        }

        WeeklyClaim::query()->create([
            'household_id' => $user->household_id,
            'chore_template_id' => $validated['chore_template_id'],
            'assigned_to_user_id' => $assignedToUser->id,
            'claimed_by_user_id' => $user->id,
            'overridden_by_user_id' => null,
            'week_start_at' => $weekStart,
            'week_end_at' => $weekEnd,
        ]);

        return redirect()->back()->with('status', 'Weekly claim created.');
    }

    public function update(UpdateWeeklyClaimRequest $request, WeeklyClaim $weeklyClaim): RedirectResponse
    {
        abort_if($request->user()->cannot('update', $weeklyClaim), 403);

        $assignedToUser = User::query()->findOrFail((int) $request->validated('assigned_to_user_id'));

        if (! $assignedToUser->hasRole('kid')) {
            return redirect()->back()->withErrors([
                'assigned_to_user_id' => 'Weekly claims can only be assigned to users with the kid role.',
            ]);
        }

        $weeklyClaim->update([
            'assigned_to_user_id' => $assignedToUser->id,
            'claimed_by_user_id' => $request->user()->id,
            'overridden_by_user_id' => $request->user()->id,
        ]);

        return redirect()->back()->with('status', 'Weekly claim updated.');
    }
}
