<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['photo', 'type', 'business_id', 'name'];

    // Define the relationship
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
