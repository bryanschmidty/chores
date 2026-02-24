<?php

namespace App\Http\Controllers;

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Http\Requests\StoreChoreInstanceRequest;
use App\Http\Requests\UpdateChoreInstanceRequest;
use App\Models\ChoreInstance;
use App\Services\ChoreListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ChoreInstanceController extends Controller
{
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

        return redirect()->back()->with('status', 'Ad-hoc chore created.');
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

        return redirect()->back()->with('status', 'Ad-hoc chore updated.');
    }

    public function destroy(Request $request, ChoreInstance $choreInstance): RedirectResponse
    {
        abort_if($request->user()->cannot('delete', $choreInstance), 403);

        $choreInstance->delete();

        return redirect()->back()->with('status', 'Ad-hoc chore deleted.');
    }

    public function claim(Request $request, ChoreInstance $choreInstance): RedirectResponse
    {
        abort_if($request->user()->cannot('claim', $choreInstance), 403);

        $choreInstance->update([
            'assigned_to_user_id' => $request->user()->id,
            'claimed_by_user_id' => $request->user()->id,
            'claimed_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Chore claimed.');
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

        return redirect()->back()->with('status', 'Chore assignment updated.');
    }
}
