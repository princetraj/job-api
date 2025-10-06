<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortlistedJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'job_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee who shortlisted this job.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the job that was shortlisted.
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
