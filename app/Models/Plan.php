<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
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
        'name',
        'description',
        'type',
        'price',
        'validity_days',
        'is_default',
        // Employee plan features
        'jobs_can_apply',
        'contact_details_can_view',
        'whatsapp_alerts',
        'sms_alerts',
        'employer_can_view_contact_free',
        // Employer plan features
        'jobs_can_post',
        'employee_contact_details_can_view',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'validity_days' => 'integer',
        'is_default' => 'boolean',
        // Employee plan features
        'jobs_can_apply' => 'integer',
        'contact_details_can_view' => 'integer',
        'whatsapp_alerts' => 'boolean',
        'sms_alerts' => 'boolean',
        'employer_can_view_contact_free' => 'boolean',
        // Employer plan features
        'jobs_can_post' => 'integer',
        'employee_contact_details_can_view' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all features for this plan.
     */
    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get all employees subscribed to this plan.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all employers subscribed to this plan.
     */
    public function employers()
    {
        return $this->hasMany(Employer::class);
    }

    /**
     * Get all subscriptions for this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(EmployeePlanSubscription::class);
    }

    /**
     * Scope to get default plans.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get non-default plans.
     */
    public function scopeNonDefault($query)
    {
        return $query->where('is_default', false);
    }

    /**
     * Scope to get plans by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the default plan for a specific type.
     */
    public static function getDefaultPlan($type = 'employee')
    {
        return self::where('type', $type)
                   ->where('is_default', true)
                   ->first();
    }

    /**
     * Check if this plan is the default plan.
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * Check if job applications are unlimited.
     */
    public function hasUnlimitedJobApplications()
    {
        return $this->jobs_can_apply === -1;
    }

    /**
     * Check if contact details views are unlimited.
     */
    public function hasUnlimitedContactViews()
    {
        return $this->contact_details_can_view === -1;
    }

    /**
     * Get jobs limit display text.
     */
    public function getJobsLimitText()
    {
        return $this->jobs_can_apply === -1 ? 'Unlimited' : $this->jobs_can_apply;
    }

    /**
     * Get contact views limit display text.
     */
    public function getContactViewsLimitText()
    {
        return $this->contact_details_can_view === -1 ? 'Unlimited' : $this->contact_details_can_view;
    }

    /**
     * Check if job posts are unlimited.
     */
    public function hasUnlimitedJobPosts()
    {
        return $this->jobs_can_post === -1;
    }

    /**
     * Check if employee contact views are unlimited.
     */
    public function hasUnlimitedEmployeeContactViews()
    {
        return $this->employee_contact_details_can_view === -1;
    }

    /**
     * Get job posts limit display text.
     */
    public function getJobPostsLimitText()
    {
        return $this->jobs_can_post === -1 ? 'Unlimited' : $this->jobs_can_post;
    }

    /**
     * Get employee contact views limit display text.
     */
    public function getEmployeeContactViewsLimitText()
    {
        return $this->employee_contact_details_can_view === -1 ? 'Unlimited' : $this->employee_contact_details_can_view;
    }
}
