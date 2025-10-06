<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'email',
        'mobile',
        'password',
        'name',
        'gender',
        'dob',
        'address',
        'education_details',
        'experience_details',
        'skills_details',
        'cv_url',
        'plan_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'address' => 'array',
        'education_details' => 'array',
        'experience_details' => 'array',
        'skills_details' => 'array',
        'dob' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the password attribute name for authentication.
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    /**
     * Get the plan associated with the employee.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get all job applications for the employee.
     */
    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Get all shortlisted jobs for the employee.
     */
    public function shortlistedJobs()
    {
        return $this->hasMany(ShortlistedJob::class);
    }

    /**
     * Set the password attribute (hashed).
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = bcrypt($value);
    }

    /**
     * Get the password for authentication.
     */
    public function getPasswordAttribute()
    {
        return $this->attributes['password_hash'];
    }
}
