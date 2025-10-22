<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CouponUser extends Model
{
    use HasFactory;

    protected $table = 'coupon_users';

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
        'coupon_id',
        'user_id',
        'user_type',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the coupon.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * Get the admin who assigned this user.
     */
    public function assigner()
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    /**
     * Get the user (polymorphic).
     */
    public function user()
    {
        if ($this->user_type === 'employee') {
            return $this->belongsTo(Employee::class, 'user_id');
        } elseif ($this->user_type === 'employer') {
            return $this->belongsTo(Employer::class, 'user_id');
        }
        return null;
    }
}
