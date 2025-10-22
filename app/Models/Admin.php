<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Admin extends Authenticatable
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
        'name',
        'email',
        'password',
        'role',
        'manager_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
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
     * Get all coupons created by this admin.
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'staff_id');
    }

    /**
     * Get all commission transactions for this admin.
     */
    public function commissionTransactions()
    {
        return $this->hasMany(CommissionTransaction::class, 'staff_id');
    }

    /**
     * Get the manager that this staff member reports to.
     */
    public function manager()
    {
        return $this->belongsTo(Admin::class, 'manager_id');
    }

    /**
     * Get all staff members managed by this manager.
     */
    public function staff()
    {
        return $this->hasMany(Admin::class, 'manager_id');
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
