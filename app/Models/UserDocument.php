<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'file_path', 'mime_type', 'file_name'];

    // Define the relationship to the Executive model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
