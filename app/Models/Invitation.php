<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        if ($this->isExpired()) {
            throw new \Exception('This invitation has expired.');
        }

        if ($this->isAccepted()) {
            throw new \Exception('This invitation has already been accepted.');
        }

        // Add user to organization with the specified role
        $this->organization->members()->attach($user->id, ['role' => $this->role]);
        
        // Set as current organization if user has none
        if (!$user->current_organization_id) {
            $user->setCurrentOrganization($this->organization);
        }

        $this->update(['accepted_at' => now()]);
    }
} 