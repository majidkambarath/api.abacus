<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDocumentType extends Model
{
    use HasFactory;
    protected $table = 'job_document_types';

    protected $fillable = [
        'title'
    ];

    public function documents()
    {
        return $this->hasMany(JobDocuments::class, 'type_id', 'id');
    }
}
