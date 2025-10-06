<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CVRequest extends Model
{
    use HasFactory;

    protected $table = 'cv_requests';

    protected $fillable = [
        'id',
        'employee_id',
        'notes',
        'preferred_template',
        'status',
        'cv_url',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get the employee who made the request
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
