<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'inviter_id',
        'organization_id',
        'role',
        'expires_at',
        'accepted_at',
        'is_link'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_link' => 'boolean'
    ];

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public static function createInvitation(Organization $organization, User $inviter, string $email, string $role = 'member')
    {
        return static::create([
            'organization_id' => $organization->id,
            'inviter_id' => $inviter->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7)
        ]);
    }

    public static function createLinkInvitation(Organization $organization, User $inviter, string $role = 'member')
    {
        return static::create([
            'organization_id' => $organization->id,
            'inviter_id' => $inviter->id,
            'email' => null,
            'is_link' => true,
            'role' => $role,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(30)
        ]);
    }

    public function isAccepted()
    {
        return !is_null($this->accepted_at);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function accept(User $user)
    {
        Log::info('Starting invitation acceptance', [
            'invitation_id' => $this->id,
            'user_id' => $user->id,
            'organization_id' => $this->organization_id,
            'role' => $this->role
        ]);

        if ($this->isExpired()) {
            Log::warning('Invitation expired', [
                'invitation_id' => $this->id,
                'expires_at' => $this->expires_at
            ]);
            throw new \Exception('This invitation has expired.');
        }

        if ($this->isAccepted()) {
            Log::warning('Invitation already accepted', [
                'invitation_id' => $this->id,
                'accepted_at' => $this->accepted_at
            ]);
            throw new \Exception('This invitation has already been accepted.');
        }

        // Add user to organization with the specified role
        $this->organization->users()->attach($user->id, ['role' => $this->role]);
        Log::info('User attached to organization', [
            'user_id' => $user->id,
            'organization_id' => $this->organization_id,
            'role' => $this->role
        ]);
        
        // Always set this organization as the current organization
        $user->setCurrentOrganization($this->organization);
        Log::info('Current organization set for user', [
            'user_id' => $user->id,
            'organization_id' => $this->organization_id,
            'current_organization_id' => $user->current_organization_id
        ]);

        $this->update(['accepted_at' => now()]);
        Log::info('Invitation marked as accepted', [
            'invitation_id' => $this->id,
            'accepted_at' => now()
        ]);
    }
} 