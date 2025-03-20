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

    public static function createInvitation(User $inviter, string $email)
    {
        return static::create([
            'inviter_id' => $inviter->id,
            'email' => $email,
            'token' => Str::random(32)
        ]);
    }

    public function isAccepted()
    {
        return !is_null($this->accepted_at);
    }

    public function accept()
    {
        $this->update(['accepted_at' => now()]);
    }
} 