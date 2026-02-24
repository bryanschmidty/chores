<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChoreTemplateRequest;
use App\Http\Requests\UpdateChoreTemplateRequest;
use App\Models\ChoreTemplate;
use App\Models\User;
use App\Models\WeeklyClaim;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class ChoreTemplateController extends Controller
{
    public function parentIndex(Request $request): View
    {
        abort_if($request->user()->cannot('viewAny', ChoreTemplate::class), 403);

        return view('parent.templates', [
            'templates' => ChoreTemplate::query()
                ->where('household_id', $request->user()->household_id)
                ->with(['defaultAssignee:id,name'])
                ->latest()
                ->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        abort_if($request->user()->cannot('viewAny', ChoreTemplate::class), 403);

        $templates = ChoreTemplate::query()
            ->where('household_id', $request->user()->household_id)
            ->latest()
            ->get();

        return response($templates->toJson(), 200, ['Content-Type' => 'application/json']);
    }

    public function parentEdit(Request $request, ChoreTemplate $choreTemplate): View
    {
        abort_if($request->user()->cannot('update', $choreTemplate), 403);

        $today = CarbonImmutable::today();
        $currentWeekClaim = WeeklyClaim::query()
            ->where('chore_template_id', $choreTemplate->id)
            ->whereDate('week_start_at', '<=', $today->toDateString())
            ->whereDate('week_end_at', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        $householdUsers = User::query()
            ->where('household_id', $request->user()->household_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('parent.chore-edit-recurring', [
            'choreTemplate' => $choreTemplate,
            'currentWeekClaim' => $currentWeekClaim,
            'householdUsers' => $householdUsers,
        ]);
    }

    public function store(StoreChoreTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ChoreTemplate::query()->create([
            ...$validated,
            'household_id' => $request->user()->household_id,
            'created_by_user_id' => $request->user()->id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        Artisan::call('chores:generate-recurring', [
            '--household-id' => $request->user()->household_id,
        ]);

        return to_route('parent.chores')->with('status', 'Chore template created.');
    }

    public function update(UpdateChoreTemplateRequest $request, ChoreTemplate $choreTemplate): RedirectResponse
    {
        $validated = $request->validated();
        $currentWeekClaimUserId = $validated['current_week_claim_user_id'] ?? null;
        unset($validated['current_week_claim_user_id']);

        $choreTemplate->update($validated);

        $today = CarbonImmutable::today();
        $weekStart = $today->startOfWeek()->toDateString();
        $weekEnd = $today->endOfWeek()->toDateString();

        $currentWeekClaim = WeeklyClaim::query()
            ->where('chore_template_id', $choreTemplate->id)
            ->whereDate('week_start_at', $weekStart)
            ->latest('id')
            ->first();

        if ($currentWeekClaimUserId === null) {
            $currentWeekClaim?->delete();

            return to_route('parent.chores')->with('status', 'Chore template updated.');
        }

        if ($currentWeekClaim === null) {
            WeeklyClaim::query()->create([
                'household_id' => $choreTemplate->household_id,
                'chore_template_id' => $choreTemplate->id,
                'assigned_to_user_id' => (int) $currentWeekClaimUserId,
                'claimed_by_user_id' => $request->user()->id,
                'overridden_by_user_id' => $request->user()->id,
                'week_start_at' => $weekStart,
                'week_end_at' => $weekEnd,
            ]);
        } else {
            $currentWeekClaim->update([
                'assigned_to_user_id' => (int) $currentWeekClaimUserId,
                'claimed_by_user_id' => $request->user()->id,
                'overridden_by_user_id' => $request->user()->id,
                'week_end_at' => $weekEnd,
            ]);
        }

        return to_route('parent.chores')->with('status', 'Chore template updated.');
    }

    public function archive(Request $request, ChoreTemplate $choreTemplate): RedirectResponse
    {
        abort_if($request->user()->cannot('update', $choreTemplate), 403);

        $choreTemplate->update([
            'is_active' => ! $choreTemplate->is_active,
        ]);

        return to_route('parent.chores')->with('status', 'Chore template archive status updated.');
    }
}
