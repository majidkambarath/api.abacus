<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'state',
        'contact_number',
        'email_address',
        'branch_manager_id',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'branch_manager_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
