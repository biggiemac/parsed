<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Mail\InvitationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        Log::info('Generating invitation link', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        $organization = auth()->user()->currentOrganization;
        if (!$organization) {
            Log::error('No current organization found for user', [
                'user_id' => auth()->id()
            ]);
            return response()->json(['error' => 'Please select an organization first.'], 400);
        }

        if (!$organization->isAdmin(auth()->user()) && !$organization->isOwner(auth()->user())) {
            Log::error('User does not have permission to generate invitation links', [
                'user_id' => auth()->id(),
                'organization_id' => $organization->id
            ]);
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

        Log::info('Invitation link created', [
            'invitation_id' => $invitation->id,
            'organization_id' => $organization->id,
            'token' => $invitation->token,
            'role' => $request->role
        ]);

        return response()->json([
            'link' => route('invitations.join', $invitation->token)
        ]);
    }

    /**
     * Handle joining via a shareable link
     */
    public function joinViaLink($token)
    {
        Log::info('Join via link accessed', [
            'token' => $token,
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method()
        ]);

        $invitation = Invitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('is_link', true)
            ->whereNull('accepted_at')
            ->firstOrFail();

        Log::info('Invitation found for join link', [
            'invitation_id' => $invitation->id,
            'organization_id' => $invitation->organization_id,
            'role' => $invitation->role
        ]);

        if (auth()->check()) {
            Log::info('User already logged in, accepting invitation', [
                'user_id' => auth()->id()
            ]);
            // If user is logged in, add them to the organization
            try {
                $invitation->accept(auth()->user());
                return redirect()->route('dashboard')
                    ->with('success', 'You have been added to the organization.');
            } catch (\Exception $e) {
                Log::error('Failed to accept invitation for logged in user', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage()
                ]);
                return redirect()->route('dashboard')
                    ->with('error', $e->getMessage());
            }
        }

        Log::info('Showing registration form for new user', [
            'invitation_id' => $invitation->id
        ]);

        // Show registration form with invitation context
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
            // First accept the invitation (this adds to organization and sets current org)
            $invitation->accept($user);
            
            // Then log the user in
            auth()->login($user);
            
            return redirect()->route('dashboard')
                ->with('success', 'Welcome! You have been added to the organization.');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', $e->getMessage());
        }
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