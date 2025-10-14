<?php

namespace App\Http\Controllers;

use App\Models\ChoreTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChoreTemplateController extends Controller
{
    /**
     * Display a listing of chore templates.
     */
    public function index(): Response
    {
        $templates = ChoreTemplate::forFamily(auth()->user()->family_id)
            ->with(['chores' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->latest()
            ->paginate(12);

        return Inertia::render('Admin/ChoreTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new chore template.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/ChoreTemplates/Create');
    }

    /**
     * Store a newly created chore template.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:1|max:1000',
            'photo_requirements' => 'required|in:none,after,both',
        ]);

        ChoreTemplate::create([
            'family_id' => auth()->user()->family_id,
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'photo_requirements' => $request->photo_requirements,
        ]);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Chore template created successfully.');
    }

    /**
     * Display the specified chore template.
     */
    public function show(ChoreTemplate $template): Response
    {
        $this->authorize('view', $template);

        $template->load(['chores.assignedTo', 'chores.createdBy']);

        return Inertia::render('Admin/ChoreTemplates/Show', [
            'template' => $template,
        ]);
    }

    /**
     * Show the form for editing the specified chore template.
     */
    public function edit(ChoreTemplate $template): Response
    {
        $this->authorize('update', $template);

        return Inertia::render('Admin/ChoreTemplates/Edit', [
            'template' => $template,
        ]);
    }

    /**
     * Update the specified chore template.
     */
    public function update(Request $request, ChoreTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:1|max:1000',
            'photo_requirements' => 'required|in:none,after,both',
            'is_active' => 'boolean',
        ]);

        $template->update($request->only([
            'name',
            'description',
            'points',
            'photo_requirements',
            'is_active',
        ]));

        return redirect()->route('admin.templates.index')
            ->with('success', 'Chore template updated successfully.');
    }

    /**
     * Remove the specified chore template.
     */
    public function destroy(ChoreTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        // Check if template has active chores
        if ($template->chores()->where('status', 'pending')->exists()) {
            return redirect()->back()
                ->withErrors(['template' => 'Cannot delete template with active chores.']);
        }

        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Chore template deleted successfully.');
    }

    /**
     * Toggle template active status.
     */
    public function toggle(ChoreTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->update(['is_active' => !$template->is_active]);

        $status = $template->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Template {$status} successfully.");
    }
}