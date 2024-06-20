<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    protected $fillable = ['start_date', 'end_date', 'lead_id', 'user_id', 'status', 'branch_id', 'close_date', 'closed_by'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function followups()
    {
        return $this->hasMany(Followup::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by', 'id');
    }
    public function documents()
    {
        return $this->hasMany(JobDocuments::class);
    }
}
