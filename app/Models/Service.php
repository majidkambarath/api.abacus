<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'name',
    ];

    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'lead_service', 'service_id', 'lead_id');
    }

    public function getPriceFromAttribute()
    {
        return $this->pivot->price_from;
    }

    // Define an accessor for price_to
    public function getPriceToAttribute()
    {
        return $this->pivot->price_to;
    }
    public function getIncentiveAmountAttribute()
    {
        return $this->pivot->incentive_amount;
    }
    public function getIncentiveTypeAttribute()
    {
        return $this->pivot->incentive_type;
    }
    public function getStatusAttribute()
    {
        return $this->pivot->status;
    }
}
