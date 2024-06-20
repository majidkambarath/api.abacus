<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStageHistory extends Model
{
    use HasFactory;

    protected $table = 'lead_stage_history';

    protected $fillable = [
        'lead_id',
        'stage_id',
        'user_id'
    ];

    public function lead(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Lead::class, 'id', 'lead_id');
        // Changed the foreign key order assuming 'id' is the primary key in the 'Lead' table
    }

    public function stage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LeadStage::class, 'id', 'stage_id');
        // Changed the foreign key order assuming 'id' is the primary key in the 'LeadStage' table
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
        // Changed the foreign key order assuming 'id' is the primary key in the 'User' table
    }
}

