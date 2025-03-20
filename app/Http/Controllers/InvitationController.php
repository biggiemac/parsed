<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Mail\InvitationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index()
    {
        $organization = auth()->user()->currentOrganization;
        if (!$organization) {
            return redirect()->route('organizations.select')
                ->with('error', 'Please select an organization first.');
        }

        $invitations = Invitation::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('invitations.index', compact('invitations', 'organization'));
    }

    public function store(Request $request)
    {
        $organization = auth()->user()->currentOrganization;
        if (!$organization) {
            return redirect()->route('organizations.select')
                ->with('error', 'Please select an organization first.');
        }

        if (!$organization->isAdmin(auth()->user()) && !$organization->isOwner(auth()->user())) {
            abort(403, 'You do not have permission to invite members to this organization.');
        }

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:member,admin'
        ]);

        $invitation = Invitation::createInvitation(
            $organization,
            auth()->user(),
            $request->email,
            $request->role
        );

        Mail::to($request->email)->send(new InvitationEmail($invitation));

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation sent successfully.');
    }

    /**
     * Generate a shareable invitation link
     */
    public function generateLink(Request $request)
    {
        $organization = auth()->user()->currentOrganization;
        if (!$organization) {
            return response()->json(['error' => 'Please select an organization first.'], 400);
        }

        if (!$organization->isAdmin(auth()->user()) && !$organization->isOwner(auth()->user())) {
            return response()->json(['error' => 'You do not have permission to generate invitation links.'], 403);
        }

        $request->validate([
            'role' => 'required|in:member,admin'
        ]);

        $invitation = Invitation::createLinkInvitation(
            $organization,
            auth()->user(),
            $request->role
        );

        return response()->json([
            'link' => route('invitations.join', $invitation->token)
        ]);
    }

    /**
     * Handle joining via a shareable link
     */
    public function joinViaLink($token)
    {
        $invitation = Invitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('is_link', true)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if (auth()->check()) {
            // If user is logged in, add them to the organization
            try {
                $invitation->accept(auth()->user());
                return redirect()->route('dashboard')
                    ->with('success', 'You have been added to the organization.');
            } catch (\Exception $e) {
                return redirect()->route('dashboard')
                    ->with('error', $e->getMessage());
            }
        }

        return view('auth.register', [
            'invitation' => $invitation
        ]);
    }

    public function accept($token)
    {
        $invitation = Invitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->firstOrFail();

        if (auth()->check()) {
            // If user is logged in, add them to the organization
            try {
                $invitation->accept(auth()->user());
                return redirect()->route('dashboard')
                    ->with('success', 'You have been added to the organization.');
            } catch (\Exception $e) {
                return redirect()->route('dashboard')
                    ->with('error', $e->getMessage());
            }
        }

        // Create new user account
        $user = \App\Models\User::create([
            'name' => explode('@', $invitation->email)[0],
            'email' => $invitation->email,
            'password' => bcrypt(Str::random(16))
        ]);

        // Accept invitation and add to organization
        try {
            $invitation->accept($user);
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', $e->getMessage());
        }

        // Log the user in
        auth()->login($user);

        return redirect()->route('profile.edit')
            ->with('success', 'Welcome! Please set your password and profile information.');
    }

    public function destroy(Invitation $invitation)
    {
        $organization = auth()->user()->currentOrganization;
        if (!$organization || $invitation->organization_id !== $organization->id) {
            abort(404);
        }

        if (!$organization->isAdmin(auth()->user()) && !$organization->isOwner(auth()->user())) {
            abort(403);
        }

        $invitation->delete();

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation cancelled successfully');
    }
} 