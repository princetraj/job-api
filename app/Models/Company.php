<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'approval_status',
        'created_by',
        'created_by_type',
        'rejection_reason',
    ];

    protected $appends = ['is_approved'];

    /**
     * Check if company is approved.
     */
    public function getIsApprovedAttribute()
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Scope to get only approved companies.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to get pending companies.
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope to get rejected companies.
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }
}
