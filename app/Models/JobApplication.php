<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobApplication extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'job_id',
        'employee_id',
        'application_status',
        'applied_at',
        'interview_date',
        'interview_time',
        'interview_location',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'interview_date' => 'date',
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
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
