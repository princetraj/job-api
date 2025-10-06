<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'payment_id',
        'amount_earned',
        'type',
    ];

    protected $casts = [
        'amount_earned' => 'decimal:2',
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
}
