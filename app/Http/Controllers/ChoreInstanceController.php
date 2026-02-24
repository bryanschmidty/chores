<?php

namespace App\Http\Controllers;

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Http\Requests\StoreChoreInstanceRequest;
use App\Http\Requests\UpdateChoreInstanceRequest;
use App\Models\ChoreInstance;
use App\Models\User;
use App\Services\ChoreListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChoreInstanceController extends Controller
{
    public function kidIndex(Request $request, ChoreListService $choreListService): View
    {
        abort_unless($request->user()->hasAnyRole(['kid', 'parent', 'supervisor']), 403);

        $instances = $choreListService->defaultKidList($request->user());

        return view('kid.index', [
            'instances' => $instances,
        ]);
    }

    public function kidAll(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['kid', 'parent', 'supervisor']), 403);

        $instances = ChoreInstance::query()
            ->forHousehold($request->user()->household_id)
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->get();

        return view('kid.chores', [
            'instances' => $instances,
        ]);
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
        ]);
    }

    public function parentIndex(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['parent', 'supervisor']), 403);

        $instances = ChoreInstance::query()
            ->forHousehold($request->user()->household_id)
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->latest()
            ->get();

        $householdUsers = User::query()
            ->where('household_id', $request->user()->household_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('parent.instances', [
            'instances' => $instances,
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

        $dueAt = $validated['due_at'] ?? null;

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
            'status' => $dueAt === null ? ChoreInstanceStatus::Due->value : ChoreInstanceStatus::Upcoming->value,
        ]);

        return to_route($this->defaultRouteForUser($request->user()))->with('status', 'Ad-hoc chore created.');
    }

    public function update(UpdateChoreInstanceRequest $request, ChoreInstance $choreInstance): RedirectResponse
    {
        $validated = $request->validated();

        $choreInstance->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'base_points' => $validated['base_points'],
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
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

        $choreInstance->update([
            'assigned_to_user_id' => $request->user()->id,
            'claimed_by_user_id' => $request->user()->id,
            'claimed_at' => now(),
        ]);

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
}
