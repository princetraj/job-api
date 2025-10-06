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
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'address' => 'array',
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
