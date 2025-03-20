<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Mail\InvitationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index()
    {
        $invitations = Invitation::where('inviter_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('invitations.index', compact('invitations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $invitation = Invitation::create([
            'email' => $request->email,
            'token' => Str::random(32),
            'inviter_id' => auth()->id(),
            'expires_at' => now()->addDays(7)
        ]);

        Mail::to($request->email)->send(new InvitationEmail($invitation));

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation sent successfully.');
    }

    /**
     * Generate a shareable invitation link
     */
    public function generateLink()
    {
        $invitation = Invitation::create([
            'token' => Str::random(32),
            'inviter_id' => auth()->id(),
            'expires_at' => now()->addDays(7),
            'is_link' => true
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
        $invitation = Invitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('is_link', true)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if (auth()->check()) {
            return redirect()->route('dashboard')
                ->with('error', 'You are already logged in.');
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

        // Create new user account
        $user = \App\Models\User::create([
            'name' => explode('@', $invitation->email)[0],
            'email' => $invitation->email,
            'password' => bcrypt(Str::random(16))
        ]);

        // Mark invitation as accepted
        $invitation->accept();

        // Log the user in
        auth()->login($user);

        return redirect()->route('profile.edit')
            ->with('success', 'Welcome! Please set your password and profile information.');
    }

    public function destroy(Invitation $invitation)
    {
        if ($invitation->inviter_id !== auth()->id()) {
            abort(403);
        }

        $invitation->delete();

        return redirect()->route('invitations.index')
            ->with('success', 'Invitation cancelled successfully');
    }
} 