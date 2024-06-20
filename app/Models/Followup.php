<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Followup extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'lead_id',
        'date',
        'time',
        'time_full',
        'contact_type',
        'branch_id',
        'note',
        'job_status',
        'status',
        'followup_reason_id',
        'user_id'
    ];
    protected $casts = [
        'status' => 'integer',
        'job_status' => 'integer',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function reason()
    {
        return $this->belongsTo(FollowupReason::class, 'followup_reason_id', 'id');
    }
}
