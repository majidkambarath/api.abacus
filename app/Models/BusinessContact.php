<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessContact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone_number', 'business_id'];

    // Define the relationship
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}

