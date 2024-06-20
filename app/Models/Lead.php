<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'lead_status',
        'urgency', 'user_id',
        'branch_id', 'status',
        'close_date', 'closed_by',
        'note',
        'lead_stage_id',
        'lead_source_id'
    ];

    protected $casts = [
        'lead_status' => 'integer',
        'status' => 'integer',
        'urgency' => 'integer',
    ];

    // Define relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'lead_service', 'lead_id', 'service_id')
            ->withPivot('price_from', 'price_to', 'incentive_amount', 'incentive_type', 'status'); // Include pivot columns
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function job()
    {
        return $this->hasOne(Job::class);
    }
    public function stage()
    {
        return $this->belongsTo(LeadStage::class, 'lead_stage_id', 'id');
    }
    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id', 'id');
    }
}
