<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        'last_login'
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
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // Check if a user has a specific role
    public function hasRole($role)
    {
        return $this->roles->contains('name', $role);
    }
//    public function branch()
//    {
//        return $this->belongsTo(Branch::class, 'id', 'branch_manager_id');
//    }

    public function managedBranch()
    {
        return $this->hasOne(Branch::class, 'branch_manager_id', 'id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function executiveDocuments()
    {
        return $this->hasMany(UserDocument::class);
    }


}
