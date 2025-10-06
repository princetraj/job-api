<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_percentage',
        'expiry_date',
        'staff_id',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who created this coupon.
     */
    public function staff()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }
}
