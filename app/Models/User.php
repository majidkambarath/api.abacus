<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_number',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'string',
    ];

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if a user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Get the branch that the user manages.
     */
    public function managedBranch()
    {
        return $this->hasOne(Branch::class, 'branch_manager_id', 'id');
    }

    /**
     * The branches that belong to the user.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id');
    }

    /**
     * Get the leads for the user.
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get the jobs for the user.
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get the executive documents for the user.
     */
    public function executiveDocuments()
    {
        return $this->hasMany(UserDocument::class);
    }
}
