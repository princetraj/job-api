<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CVRequest extends Model
{
    use HasFactory;

    protected $table = 'cv_requests';

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
        'id',
        'employee_id',
        'notes',
        'preferred_template',
        'status',
        'price',
        'payment_status',
        'payment_transaction_id',
        'cv_url',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get the employee who made the request
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
