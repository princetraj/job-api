<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'uploaded_by_type',
        'uploaded_by_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get the uploader (polymorphic)
     */
    public function uploadedBy()
    {
        return $this->morphTo();
    }
}
