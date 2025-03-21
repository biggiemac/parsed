<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $invitation = null;
        if ($request->has('token')) {
            Log::info('Registration page accessed with token', ['token' => $request->token]);
            $invitation = Invitation::where('token', $request->token)
                ->where('expires_at', '>', now())
                ->whereNull('accepted_at')
                ->first();
            
            if ($invitation) {
                Log::info('Valid invitation found', [
                    'invitation_id' => $invitation->id,
                    'organization_id' => $invitation->organization_id,
                    'role' => $invitation->role
                ]);
            } else {
                Log::warning('No valid invitation found for token', ['token' => $request->token]);
            }
        }
        return view('auth.register', compact('invitation'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Registration request received', [
            'has_invitation_token' => $request->has('invitation_token'),
            'invitation_token' => $request->invitation_token ?? null,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Log::info('New user created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'current_organization_id' => $user->current_organization_id
        ]);

        event(new Registered($user));

        // Handle invitation if present
        if ($request->has('invitation_token')) {
            Log::info('Processing invitation for new user', [
                'user_id' => $user->id,
                'invitation_token' => $request->invitation_token
            ]);

            $invitation = Invitation::where('token', $request->invitation_token)
                ->where('expires_at', '>', now())
                ->whereNull('accepted_at')
                ->first();

            if ($invitation) {
                Log::info('Valid invitation found for processing', [
                    'invitation_id' => $invitation->id,
                    'organization_id' => $invitation->organization_id,
                    'role' => $invitation->role,
                    'is_link' => $invitation->is_link
                ]);

                try {
                    // First accept the invitation (this adds to organization and sets current org)
                    $invitation->accept($user);
                    Log::info('Invitation accepted successfully', [
                        'user_id' => $user->id,
                        'organization_id' => $invitation->organization_id,
                        'current_organization_id' => $user->current_organization_id
                    ]);
                    
                    // Log the user in
                    Auth::login($user);
                    Log::info('User logged in after invitation acceptance', [
                        'user_id' => $user->id,
                        'current_organization_id' => $user->current_organization_id,
                        'organization_membership' => $user->organizations()->pluck('organizations.id')
                    ]);
                    
                    return redirect()->route('dashboard')
                        ->with('success', 'Welcome! You have been added to the organization.');
                } catch (\Exception $e) {
                    Log::error('Failed to accept invitation', [
                        'user_id' => $user->id,
                        'invitation_id' => $invitation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // If invitation acceptance fails, still log them in but show an error
                    Auth::login($user);
                    return redirect()->route('dashboard')
                        ->with('error', 'Registration successful but there was an issue adding you to the organization: ' . $e->getMessage());
                }
            } else {
                Log::warning('No valid invitation found during registration', [
                    'user_id' => $user->id,
                    'invitation_token' => $request->invitation_token
                ]);
            }
        }

        // If no invitation or invitation handling failed, proceed with normal registration
        Auth::login($user);
        Log::info('User logged in after normal registration', [
            'user_id' => $user->id,
            'current_organization_id' => $user->current_organization_id,
            'organization_membership' => $user->organizations()->pluck('organizations.id')
        ]);
        return redirect(route('dashboard', absolute: false));
    }
}
