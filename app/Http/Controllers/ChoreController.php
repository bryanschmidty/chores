<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\ChoreTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChoreController extends Controller
{
    /**
     * Display a listing of chores.
     */
    public function index(): Response
    {
        $family = auth()->user()->family;
        
        $chores = Chore::forFamily($family->id)
            ->with(['assignedTo', 'createdBy', 'template'])
            ->latest()
            ->paginate(15);

        $templates = ChoreTemplate::forFamily($family->id)
            ->active()
            ->get();

        $familyMembers = $family->users()->orderBy('name')->get();

        return Inertia::render('Admin/Chores/Index', [
            'chores' => $chores,
            'templates' => $templates,
            'familyMembers' => $familyMembers,
        ]);
    }

    /**
     * Show the form for creating a new chore.
     */
    public function create(): Response
    {
        $family = auth()->user()->family;
        
        $templates = ChoreTemplate::forFamily($family->id)
            ->active()
            ->get();

        $familyMembers = $family->users()->orderBy('name')->get();

        return Inertia::render('Admin/Chores/Create', [
            'templates' => $templates,
            'familyMembers' => $familyMembers,
        ]);
    }

    /**
     * Store a newly created chore.
     */
    public function store(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'template_id' => 'nullable|exists:chore_templates,id',
            'assigned_to' => 'required|exists:users,id',
            'points' => 'required|integer|min:1|max:1000',
            'recurrence_type' => 'required|in:none,daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'next_due_date' => 'required|date|after_or_equal:today',
            'requires_verification' => 'boolean',
        ]);

        // Validate that assigned user belongs to the same family
        $assignedUser = User::find($request->assigned_to);
        if ($assignedUser->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['assigned_to' => 'User must be a member of your family.']);
        }

        // If template is provided, validate it belongs to the family
        if ($request->template_id) {
            $template = ChoreTemplate::find($request->template_id);
            if ($template->family_id !== $family->id) {
                return redirect()->back()
                    ->withErrors(['template_id' => 'Template must belong to your family.']);
            }
        }

        $chore = Chore::create([
            'family_id' => $family->id,
            'template_id' => $request->template_id,
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'recurrence_type' => $request->recurrence_type,
            'recurrence_interval' => $request->recurrence_interval,
            'next_due_date' => $request->next_due_date,
            'requires_verification' => $request->boolean('requires_verification'),
        ]);

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore created successfully.');
    }

    /**
     * Display the specified chore.
     */
    public function show(Chore $chore): Response
    {
        $this->authorize('view', $chore);

        $chore->load(['assignedTo', 'createdBy', 'template', 'completions.user', 'completions.verifier']);

        return Inertia::render('Admin/Chores/Show', [
            'chore' => $chore,
        ]);
    }

    /**
     * Show the form for editing the specified chore.
     */
    public function edit(Chore $chore): Response
    {
        $this->authorize('update', $chore);

        $family = auth()->user()->family;
        
        $templates = ChoreTemplate::forFamily($family->id)
            ->active()
            ->get();

        $familyMembers = $family->users()->orderBy('name')->get();

        return Inertia::render('Admin/Chores/Edit', [
            'chore' => $chore,
            'templates' => $templates,
            'familyMembers' => $familyMembers,
        ]);
    }

    /**
     * Update the specified chore.
     */
    public function update(Request $request, Chore $chore): RedirectResponse
    {
        $this->authorize('update', $chore);

        $family = auth()->user()->family;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'template_id' => 'nullable|exists:chore_templates,id',
            'assigned_to' => 'required|exists:users,id',
            'points' => 'required|integer|min:1|max:1000',
            'recurrence_type' => 'required|in:none,daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'next_due_date' => 'required|date',
            'requires_verification' => 'boolean',
            'status' => 'required|in:pending,completed,overdue',
        ]);

        // Validate that assigned user belongs to the same family
        $assignedUser = User::find($request->assigned_to);
        if ($assignedUser->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['assigned_to' => 'User must be a member of your family.']);
        }

        $chore->update($request->only([
            'name',
            'description',
            'template_id',
            'assigned_to',
            'points',
            'recurrence_type',
            'recurrence_interval',
            'next_due_date',
            'requires_verification',
            'status',
        ]));

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore updated successfully.');
    }

    /**
     * Remove the specified chore.
     */
    public function destroy(Chore $chore): RedirectResponse
    {
        $this->authorize('delete', $chore);

        $chore->delete();

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore deleted successfully.');
    }

    /**
     * Create chore from template.
     */
    public function createFromTemplate(Request $request): RedirectResponse
    {
        $family = auth()->user()->family;

        $request->validate([
            'template_id' => 'required|exists:chore_templates,id',
            'assigned_to' => 'required|exists:users,id',
            'next_due_date' => 'required|date|after_or_equal:today',
            'recurrence_type' => 'required|in:none,daily,weekly,monthly,custom',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
        ]);

        $template = ChoreTemplate::find($request->template_id);
        
        if ($template->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['template_id' => 'Template must belong to your family.']);
        }

        $assignedUser = User::find($request->assigned_to);
        if ($assignedUser->family_id !== $family->id) {
            return redirect()->back()
                ->withErrors(['assigned_to' => 'User must be a member of your family.']);
        }

        $chore = Chore::create([
            'family_id' => $family->id,
            'template_id' => $template->id,
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id(),
            'name' => $template->name,
            'description' => $template->description,
            'points' => $template->points,
            'recurrence_type' => $request->recurrence_type,
            'recurrence_interval' => $request->recurrence_interval,
            'next_due_date' => $request->next_due_date,
            'requires_verification' => $family->getSetting('global_verification', false),
        ]);

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore created from template successfully.');
    }

    /**
     * Mark chore as completed (for members).
     */
    public function complete(Request $request, Chore $chore): RedirectResponse
    {
        $this->authorize('complete', $chore);

        $request->validate([
            'before_photo' => 'nullable|image|max:10240',
            'after_photo' => 'nullable|image|max:10240',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if photos are required
        $photoRequirements = $chore->getPhotoRequirements();
        
        if ($photoRequirements === 'both' && (!$request->hasFile('before_photo') || !$request->hasFile('after_photo'))) {
            return redirect()->back()
                ->withErrors(['photos' => 'Both before and after photos are required for this chore.']);
        }
        
        if ($photoRequirements === 'after' && !$request->hasFile('after_photo')) {
            return redirect()->back()
                ->withErrors(['photos' => 'After photo is required for this chore.']);
        }

        // Store photos if provided
        $beforePhoto = null;
        $afterPhoto = null;

        if ($request->hasFile('before_photo')) {
            $beforePhoto = $request->file('before_photo')->store('chore-photos', 'public');
        }

        if ($request->hasFile('after_photo')) {
            $afterPhoto = $request->file('after_photo')->store('chore-photos', 'public');
        }

        // Create completion record
        $completion = $chore->completions()->create([
            'user_id' => auth()->id(),
            'completed_at' => now(),
            'before_photo' => $beforePhoto,
            'after_photo' => $afterPhoto,
            'notes' => $request->notes,
        ]);

        // If no verification required, auto-approve and award points
        if (!$chore->requires_verification) {
            $completion->approve(100, auth()->id());
            
            // Award points
            auth()->user()->pointTransactions()->create([
                'family_id' => $chore->family_id,
                'amount' => $chore->points,
                'type' => 'chore_completion',
                'related_id' => $completion->id,
                'related_type' => 'App\\Models\\ChoreCompletion',
                'description' => "Completed chore: {$chore->name}",
            ]);
        }

        // Update chore status
        $chore->markCompleted();

        return redirect()->route('dashboard')
            ->with('success', 'Chore completed successfully!');
    }
}