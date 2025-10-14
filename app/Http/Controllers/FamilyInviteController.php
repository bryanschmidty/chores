<?php

namespace App\Http\Controllers;

use App\Models\FamilyInvite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FamilyInviteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of family invites.
     */
    public function index(): Response
    {
        $invites = FamilyInvite::where('family_id', auth()->user()->family_id)
            ->with(['createdBy', 'acceptedBy'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Family/Invites/Index', [
            'invites' => $invites,
        ]);
    }

    /**
     * Show the form for creating a new family invite.
     */
    public function create(): Response
    {
        return Inertia::render('Family/Invites/Create');
    }

    /**
     * Store a newly created family invite.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'nullable|email|max:255',
            'expiry_days' => 'integer|min:1|max:30',
        ]);

        $family = auth()->user()->family;

        $invite = FamilyInvite::createForFamily(
            $family,
            auth()->user(),
            $request->email,
            $request->expiry_days ?? 7
        );

        return redirect()->route('family.invites.index')
            ->with('success', 'Invite created successfully. Share this code: ' . $invite->invite_code);
    }

    /**
     * Display the specified family invite.
     */
    public function show(FamilyInvite $invite): Response
    {
        $this->authorize('view', $invite);

        $invite->load(['createdBy', 'acceptedBy', 'family']);

        return Inertia::render('Family/Invites/Show', [
            'invite' => $invite,
        ]);
    }

    /**
     * Cancel/delete the specified family invite.
     */
    public function destroy(FamilyInvite $invite): RedirectResponse
    {
        $this->authorize('delete', $invite);

        $invite->delete();

        return redirect()->route('family.invites.index')
            ->with('success', 'Invite cancelled successfully.');
    }

    /**
     * Accept a family invite.
     */
    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'invite_code' => 'required|string|exists:family_invites,invite_code',
        ]);

        $invite = FamilyInvite::where('invite_code', $request->invite_code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invite) {
            return redirect()->back()
                ->withErrors(['invite_code' => 'Invalid or expired invite code.']);
        }

        // Check if user already has a family
        if (auth()->user()->family_id) {
            return redirect()->back()
                ->withErrors(['invite_code' => 'You are already a member of a family.']);
        }

        // Accept the invite
        $invite->accept(auth()->user());

        // Add user to family
        auth()->user()->update([
            'family_id' => $invite->family_id,
            'role' => 'member',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Successfully joined the ' . $invite->family->name . ' family!');
    }

    /**
     * Check if an invite code is valid.
     */
    public function check(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'invite_code' => 'required|string',
        ]);

        $invite = FamilyInvite::where('invite_code', $request->invite_code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('family')
            ->first();

        if (!$invite) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired invite code.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'family_name' => $invite->family->name,
            'expires_at' => $invite->expires_at,
        ]);
    }
}