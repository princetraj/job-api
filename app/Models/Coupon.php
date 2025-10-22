<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Coupon extends Model
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
        'code',
        'name',
        'discount_percentage',
        'coupon_for',
        'expiry_date',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who created this coupon.
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Get the admin who approved this coupon.
     */
    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Get all employees assigned to this coupon.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'coupon_users', 'coupon_id', 'user_id')
            ->wherePivot('user_type', 'employee')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    /**
     * Get all employers assigned to this coupon.
     */
    public function employers()
    {
        return $this->belongsToMany(Employer::class, 'coupon_users', 'coupon_id', 'user_id')
            ->wherePivot('user_type', 'employer')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    /**
     * Get all assigned users (both employees and employers).
     */
    public function assignedUsers()
    {
        return $this->hasMany(CouponUser::class, 'coupon_id');
    }

    /**
     * Check if coupon is valid.
     */
    public function isValid()
    {
        return $this->status === 'approved'
            && ($this->expiry_date === null || $this->expiry_date >= now());
    }

    /**
     * Check if a specific user is assigned to this coupon.
     */
    public function isAssignedToUser($userId, $userType)
    {
        return $this->assignedUsers()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->exists();
    }
}
