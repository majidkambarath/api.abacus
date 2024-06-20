<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowupReason extends Model
{
    use HasFactory;
    protected $fillable = [
        'title'
        ];

    public function followups()
    {
        return $this->hasMany(Followup::class);
    }
}
