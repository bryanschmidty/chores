<?php

namespace App\Http\Controllers;

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Http\Requests\StoreChoreInstanceRequest;
use App\Http\Requests\UpdateChoreInstanceRequest;
use App\Models\ChoreInstance;
use App\Models\ChoreTemplate;
use App\Models\PointsLedger;
use App\Models\User;
use App\Models\WeeklyClaim;
use App\Services\ChoreListService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChoreInstanceController extends Controller
{
    public function kidIndex(Request $request, ChoreListService $choreListService): View
    {
        abort_unless($request->user()->hasAnyRole(['kid', 'parent', 'supervisor']), 403);

        $filter = (string) $request->query('filter', 'my_chores');
        if (! in_array($filter, ['my_chores', 'my_weekly_chores', 'my_completed_chores', 'unclaimed'], true)) {
            $filter = 'my_chores';
        }

        $myChores = collect();
        $myWeeklyChores = collect();
        $myCompletedChores = collect();
        $unclaimedToday = collect();
        $unclaimedChoreCount = 0;
        $myPoints = (int) PointsLedger::query()
            ->where('household_id', $request->user()->household_id)
            ->where('user_id', $request->user()->id)
            ->sum('points');

        if ($filter === 'my_chores') {
            $myChores = $choreListService->myChores($request->user());
        }

        if ($filter === 'my_weekly_chores') {
            $myWeeklyChores = $choreListService->myWeeklyChores($request->user());
        }

        if ($filter === 'my_completed_chores') {
            $myCompletedChores = $choreListService->myCompletedChores($request->user());
        }

        if ($filter === 'unclaimed') {
            $unclaimedToday = $choreListService->unclaimedInstancesForToday($request->user());
            $unclaimedChoreCount = $unclaimedToday->count();
        } else {
            $unclaimedChoreCount = $choreListService->unclaimedInstancesForToday($request->user())->count();
        }

        return view('kid.index', [
            'filter' => $filter,
            'myChores' => $myChores,
            'myWeeklyChores' => $myWeeklyChores,
            'myCompletedChores' => $myCompletedChores,
            'unclaimedToday' => $unclaimedToday,
            'unclaimedChoreCount' => $unclaimedChoreCount,
            'myPoints' => $myPoints,
        ]);
    }

    public function kidAll(): RedirectResponse
    {
        return to_route('kid.index', ['filter' => 'unclaimed']);
    }

    public function kidShow(Request $request, ChoreInstance $choreInstance): View
    {
        abort_if($request->user()->cannot('view', $choreInstance), 403);

        $helpers = User::query()
            ->where('household_id', $request->user()->household_id)
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->get();

        return view('kid.chore-show', [
            'choreInstance' => $choreInstance->load(['assignee:id,name', 'createdBy:id,name']),
            'helpers' => $helpers,
            'backFilter' => (string) $request->query('filter', 'my_chores'),
        ]);
    }

    public function parentIndex(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['parent', 'supervisor']), 403);

        $templates = ChoreTemplate::query()
            ->where('household_id', $request->user()->household_id)
            ->with([
                'defaultAssignee:id,name',
                'weeklyClaims' => function ($query): void {
                    $today = CarbonImmutable::today();
                    $query->whereDate('week_start_at', '<=', $today->toDateString())
                        ->whereDate('week_end_at', '>=', $today->toDateString())
                        ->with(['assignedTo:id,name'])
                        ->latest('id');
                },
            ])
            ->latest()
            ->get();

        $instances = ChoreInstance::query()
            ->forHousehold($request->user()->household_id)
            ->where('source_type', ChoreSourceType::AdHoc->value)
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->latest()
            ->get();

        $householdUsers = User::query()
            ->where('household_id', $request->user()->household_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('parent.instances', [
            'templates' => $templates,
            'instances' => $instances,
            'householdUsers' => $householdUsers,
        ]);
    }

    public function parentCreate(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['parent', 'supervisor']), 403);

        $householdUsers = User::query()
            ->where('household_id', $request->user()->household_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('parent.chore-create', [
            'householdUsers' => $householdUsers,
        ]);
    }

    public function parentEdit(Request $request, ChoreInstance $choreInstance): View
    {
        abort_if($request->user()->cannot('update', $choreInstance), 403);
        abort_if($choreInstance->source_type !== ChoreSourceType::AdHoc, 404);

        $householdUsers = User::query()
            ->where('household_id', $request->user()->household_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('parent.chore-edit-one-time', [
            'choreInstance' => $choreInstance,
            'householdUsers' => $householdUsers,
        ]);
    }

    public function index(Request $request): Response
    {
        $instances = app(ChoreListService::class)->defaultKidList($request->user());

        return response($instances->toJson(), 200, ['Content-Type' => 'application/json']);
    }

    public function store(StoreChoreInstanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $isOpen = (bool) ($validated['is_open'] ?? false);
        $assignedToUserId = $validated['assigned_to_user_id'] ?? null;

        if ($user->hasRole('kid') && ! $isOpen && $assignedToUserId === null) {
            $assignedToUserId = $user->id;
        }

        if ($isOpen) {
            $assignedToUserId = null;
        }

        $dueAt = $this->normalizeDueAt($validated['due_at'] ?? null);

        ChoreInstance::query()->create([
            'household_id' => $user->household_id,
            'chore_template_id' => null,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $assignedToUserId,
            'source_type' => ChoreSourceType::AdHoc->value,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_at' => $dueAt,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'base_points' => $validated['base_points'],
            'status' => $this->statusForDueDate($dueAt),
        ]);

        return to_route($this->defaultRouteForUser($request->user()))->with('status', 'Ad-hoc chore created.');
    }

    public function update(UpdateChoreInstanceRequest $request, ChoreInstance $choreInstance): RedirectResponse
    {
        $validated = $request->validated();
        $dueAt = $this->normalizeDueAt($validated['due_at'] ?? null);

        $status = $choreInstance->status;
        if (in_array($status, [ChoreInstanceStatus::Due, ChoreInstanceStatus::Upcoming, ChoreInstanceStatus::Overdue], true)) {
            $status = ChoreInstanceStatus::from($this->statusForDueDate($dueAt));
        }

        $choreInstance->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'base_points' => $validated['base_points'],
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'due_at' => $dueAt,
            'status' => $status->value,
        ]);

        return to_route($this->defaultRouteForUser($request->user()))->with('status', 'Ad-hoc chore updated.');
    }

    public function destroy(Request $request, ChoreInstance $choreInstance): RedirectResponse
    {
        abort_if($request->user()->cannot('delete', $choreInstance), 403);

        $choreInstance->delete();

        return to_route($this->defaultRouteForUser($request->user()))->with('status', 'Ad-hoc chore deleted.');
    }

    public function claim(Request $request, ChoreInstance $choreInstance): RedirectResponse
    {
        abort_if($request->user()->cannot('claim', $choreInstance), 403);

        $user = $request->user();

        DB::transaction(function () use ($choreInstance, $user): void {
            $now = CarbonImmutable::now();
            $claimedAt = $now->toDateTimeString();

            if ($choreInstance->source_type === ChoreSourceType::Recurring && $choreInstance->chore_template_id !== null) {
                $weekStart = $now->startOfWeek()->toDateString();
                $weekEnd = $now->endOfWeek()->toDateString();

                $weeklyClaim = WeeklyClaim::query()->firstOrCreate(
                    [
                        'chore_template_id' => $choreInstance->chore_template_id,
                        'week_start_at' => $weekStart,
                    ],
                    [
                        'household_id' => $user->household_id,
                        'assigned_to_user_id' => $user->id,
                        'claimed_by_user_id' => $user->id,
                        'overridden_by_user_id' => null,
                        'week_end_at' => $weekEnd,
                    ]
                );

                if (! $weeklyClaim->wasRecentlyCreated && (int) $weeklyClaim->assigned_to_user_id !== (int) $user->id) {
                    abort(403);
                }

                if (! $weeklyClaim->wasRecentlyCreated) {
                    $weeklyClaim->update([
                        'claimed_by_user_id' => $user->id,
                        'week_end_at' => $weekEnd,
                    ]);
                }

                ChoreInstance::query()
                    ->where('household_id', $choreInstance->household_id)
                    ->where('chore_template_id', $choreInstance->chore_template_id)
                    ->whereDate('due_at', '>=', $now->toDateString())
                    ->whereDate('due_at', '<=', $weekEnd)
                    ->where(function ($query) use ($user): void {
                        $query->whereNull('assigned_to_user_id')
                            ->orWhere('assigned_to_user_id', $user->id);
                    })
                    ->update([
                        'assigned_to_user_id' => $user->id,
                        'claimed_by_user_id' => $user->id,
                        'claimed_at' => $claimedAt,
                    ]);

                return;
            }

            $choreInstance->update([
                'assigned_to_user_id' => $user->id,
                'claimed_by_user_id' => $user->id,
                'claimed_at' => $claimedAt,
            ]);
        });

        return to_route('kid.index')->with('status', 'Chore claimed.');
    }

    public function assign(Request $request, ChoreInstance $choreInstance): RedirectResponse
    {
        abort_if($request->user()->cannot('assign', $choreInstance), 403);

        $validated = $request->validate([
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('household_id', $request->user()->household_id),
            ],
        ]);

        $choreInstance->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
        ]);

        return to_route($this->defaultRouteForUser($request->user()))->with('status', 'Chore assignment updated.');
    }

    private function defaultRouteForUser(User $user): string
    {
        if ($user->hasRole('parent')) {
            return 'parent.chores';
        }

        if ($user->hasRole('supervisor')) {
            return 'supervisor.queue';
        }

        return 'kid.index';
    }

    private function normalizeDueAt(mixed $dueAt): ?CarbonImmutable
    {
        if ($dueAt === null || $dueAt === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $dueAt)->startOfDay();
    }

    private function statusForDueDate(?CarbonImmutable $dueAt): string
    {
        if ($dueAt === null) {
            return ChoreInstanceStatus::Due->value;
        }

        $today = CarbonImmutable::today();

        if ($dueAt->greaterThan($today)) {
            return ChoreInstanceStatus::Upcoming->value;
        }

        if ($dueAt->equalTo($today)) {
            return ChoreInstanceStatus::Due->value;
        }

        return ChoreInstanceStatus::Overdue->value;
    }
}
