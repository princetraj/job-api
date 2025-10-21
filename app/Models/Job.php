<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Job extends Model
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
        'employer_id',
        'title',
        'description',
        'salary',
        'location_id',
        'category_id',
        'is_featured',
        'featured_end_date',
        'last_viewed_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'featured_end_date' => 'datetime',
        'last_viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employer that posted this job.
     */
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the location for this job.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the category for this job.
     */
    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    /**
     * Get all applications for this job.
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Get all employees who shortlisted this job.
     */
    public function shortlistedBy()
    {
        return $this->hasMany(ShortlistedJob::class);
    }
}
