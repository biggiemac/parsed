<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            if (!$organization->slug) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isOwner(User $user)
    {
        return $this->owner_id === $user->id;
    }

    public function isAdmin(User $user)
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function categoryRules()
    {
        return $this->hasMany(CategoryRule::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }
}
