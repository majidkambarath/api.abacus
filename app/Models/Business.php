<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $table = 'business';

    protected $fillable = ['name', 'landphone', 'location_id', 'location_url', 'email', 'address'];

    // Define relationships
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function contacts()
    {
        return $this->hasMany(BusinessContact::class);
    }

    public function photos()
    {
        return $this->hasMany(BusinessPhoto::class, 'business_id', 'id');
    }
}
