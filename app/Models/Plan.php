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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'validity_days' => 'integer',
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
}
