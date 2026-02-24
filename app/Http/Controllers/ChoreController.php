<?php

namespace App\Http\Controllers;

use App\Models\Chore;
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
            ->with(['assignedChores'])
            ->latest()
            ->get();

        return Inertia::render('Admin/Chores/Index', [
            'chores' => $chores,
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
            'points' => 'required|integer|min:1|max:1000',
            'frequency' => 'required|in:daily,twice_weekly,weekly,twice_monthly,monthly,adhoc',
            'review_required' => 'boolean',
            'photos_required' => 'required|in:none,before_and_after,only_after',
        ]);

        $chore = Chore::create([
            'family_id' => $family->id,
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'frequency' => $request->frequency,
            'review_required' => $request->boolean('review_required'),
            'photos_required' => $request->photos_required,
        ]);

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore created successfully.');
    }

    /**
     * Update the specified chore.
     */
    public function update(Request $request, Chore $chore): RedirectResponse
    {
        // Check if chore belongs to user's family
        if ($chore->family_id !== auth()->user()->family_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:1|max:1000',
            'frequency' => 'required|in:daily,twice_weekly,weekly,twice_monthly,monthly,adhoc',
            'review_required' => 'boolean',
            'photos_required' => 'required|in:none,before_and_after,only_after',
        ]);

        $chore->update($request->only([
            'name',
            'description',
            'points',
            'frequency',
            'review_required',
            'photos_required',
        ]));

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore updated successfully.');
    }

    /**
     * Remove the specified chore.
     */
    public function destroy(Chore $chore): RedirectResponse
    {
        // Check if chore belongs to user's family
        if ($chore->family_id !== auth()->user()->family_id) {
            abort(403);
        }

        // Check if chore has any assigned chores
        if ($chore->assignedChores()->exists()) {
            return redirect()->back()
                ->withErrors(['chore' => 'Cannot delete chore that has been assigned to family members.']);
        }

        $chore->delete();

        return redirect()->route('admin.chores.index')
            ->with('success', 'Chore deleted successfully.');
    }
}