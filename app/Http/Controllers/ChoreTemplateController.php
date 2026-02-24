<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChoreTemplateRequest;
use App\Http\Requests\UpdateChoreTemplateRequest;
use App\Models\ChoreTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ChoreTemplateController extends Controller
{
    public function parentIndex(Request $request): View
    {
        abort_if($request->user()->cannot('viewAny', ChoreTemplate::class), 403);

        $templates = ChoreTemplate::query()
            ->where('household_id', $request->user()->household_id)
            ->with(['defaultAssignee:id,name'])
            ->latest()
            ->get();

        return view('parent.templates', [
            'templates' => $templates,
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

    public function store(StoreChoreTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ChoreTemplate::query()->create([
            ...$validated,
            'household_id' => $request->user()->household_id,
            'created_by_user_id' => $request->user()->id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return to_route('parent.templates')->with('status', 'Chore template created.');
    }

    public function update(UpdateChoreTemplateRequest $request, ChoreTemplate $choreTemplate): RedirectResponse
    {
        $choreTemplate->update($request->validated());

        return to_route('parent.templates')->with('status', 'Chore template updated.');
    }

    public function archive(Request $request, ChoreTemplate $choreTemplate): RedirectResponse
    {
        abort_if($request->user()->cannot('update', $choreTemplate), 403);

        $choreTemplate->update([
            'is_active' => ! $choreTemplate->is_active,
        ]);

        return to_route('parent.templates')->with('status', 'Chore template archive status updated.');
    }
}
