<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Employer extends Authenticatable
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
        'company_name',
        'email',
        'contact',
        'address',
        'industry_type',
        'password',
        'plan_id',
        'plan_started_at',
        'plan_expires_at',
        'plan_is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'address' => 'array',
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
     * Get the industry associated with the employer.
     */
    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_type');
    }

    /**
     * Get the plan associated with the employer.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get all jobs posted by the employer.
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get all plan subscriptions for the employer.
     */
    public function planSubscriptions()
    {
        return $this->hasMany(EmployerPlanSubscription::class);
    }

    /**
     * Get the current active subscription.
     */
    public function currentSubscription()
    {
        return $this->hasOne(EmployerPlanSubscription::class)
                    ->where('status', 'active')
                    ->latest('started_at');
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
     * Check if the employer has an active plan.
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
}
