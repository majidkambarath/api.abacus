<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JobDocuments extends Model
{
    use HasFactory;

    protected $table = 'job_documents';


    protected $fillable = ['user_id', 'job_id', 'mime_type', 'file_name', 'type_id'];

    // Define the relationship to the Executive model
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
    public function type()
    {
        return $this->belongsTo(JobDocumentType::class);
    }

}
