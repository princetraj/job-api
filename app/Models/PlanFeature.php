<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlanFeature extends Model
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
        'plan_id',
        'feature_name',
        'feature_value',
    ];

    public $timestamps = false;

    /**
     * Get the plan that owns this feature.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
