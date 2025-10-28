<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CommissionTransaction extends Model
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
        'staff_id',
        'payment_id',
        'order_id',
        'coupon_id',
        'amount_earned',
        'transaction_amount',
        'discount_amount',
        'discount_percentage',
        'type',
    ];

    protected $casts = [
        'amount_earned' => 'decimal:2',
        'transaction_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who earned this commission.
     */
    public function staff()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }

    /**
     * Get the payment associated with this commission.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the plan order associated with this commission.
     */
    public function order()
    {
        return $this->belongsTo(PlanOrder::class, 'order_id');
    }

    /**
     * Get the coupon associated with this commission.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
