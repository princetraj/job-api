<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'plan_id',
        'amount',
        'original_amount',
        'discount_amount',
        'coupon_id',
        'payment_method',
        'transaction_id',
        'payment_status',
        'status',
        'coupon_code',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the plan for this payment.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the coupon used in this payment.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the user (polymorphic relationship).
     */
    public function user()
    {
        return $this->morphTo();
    }

    /**
     * Get commission transactions for this payment.
     */
    public function commissionTransactions()
    {
        return $this->hasMany(CommissionTransaction::class);
    }
}
