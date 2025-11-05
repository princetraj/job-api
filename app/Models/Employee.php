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
        'account_status',
        'mobile',
        'password',
        'name',
        'gender',
        'dob',
        'description',
        'address',
        'experience_details',
        'skills_details',
        'cv_url',
        'plan_id',
        'plan_started_at',
        'plan_expires_at',
        'plan_is_active',
        'profile_photo_url',
        'profile_photo_status',
        'profile_photo_rejection_reason',
        'created_by_admin_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $appends = ['public_profile_photo_url', 'profile_photo_full_url'];

    protected $casts = [
        'address' => 'array',
        'experience_details' => 'array',
        'skills_details' => 'array',
        'dob' => 'date',
        'plan_started_at' => 'datetime',
        'plan_expires_at' => 'datetime',
        'plan_is_active' => 'boolean',
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
     * Get the admin who created this employee (if created by admin).
     */
    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
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
     * Get all plan subscriptions for the employee.
     */
    public function planSubscriptions()
    {
        return $this->hasMany(EmployeePlanSubscription::class);
    }

    /**
     * Get the current active subscription.
     */
    public function currentSubscription()
    {
        return $this->hasOne(EmployeePlanSubscription::class)
                    ->where('status', 'active')
                    ->latest('started_at');
    }

    /**
     * Check if the employee has an active plan.
     */
    public function hasActivePlan()
    {
        return $this->plan_is_active &&
               $this->plan_id &&
               ($this->plan_expires_at === null || $this->plan_expires_at->isFuture());
    }

    /**
     * Check if the plan is expired.
     */
    public function isPlanExpired()
    {
        return $this->plan_expires_at !== null && $this->plan_expires_at->isPast();
    }

    /**
     * Get all skills for the employee.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'employee_skill');
    }

    /**
     * Get all education records for the employee.
     */
    public function educations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * Get education with related data (degree, university, field).
     */
    public function educationsWithDetails()
    {
        return $this->hasMany(EmployeeEducation::class)
                    ->with(['degree', 'university', 'fieldOfStudy']);
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

    /**
     * Get the public profile photo URL (only if approved).
     * This is used when employers view employee profiles.
     */
    public function getPublicProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_status === 'approved') {
            return $this->getProfilePhotoFullUrlAttribute();
        }
        return null;
    }

    /**
     * Get the full URL for the profile photo based on ASSET_URL from ENV.
     * Returns the complete URL including the domain from environment configuration.
     */
    public function getProfilePhotoFullUrlAttribute()
    {
        if (!$this->profile_photo_url) {
            return null;
        }

        $assetUrl = config('app.asset_url') ?: config('app.url');

        // Remove trailing slash from asset URL
        $assetUrl = rtrim($assetUrl, '/');

        // Profile photo URL already starts with /storage/
        return $assetUrl . $this->profile_photo_url;
    }
}
