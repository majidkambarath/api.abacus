<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'route_id'];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }
}
