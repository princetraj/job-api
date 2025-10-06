<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'employee_id',
        'application_status',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the job for this application.
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the employee who applied.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
