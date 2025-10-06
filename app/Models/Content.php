<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'title',
        'slug',
        'body',
        'type',
        'status',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
