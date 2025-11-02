<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
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
     * Check if job title is approved.
     */
    public function getIsApprovedAttribute()
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Scope to get only approved job titles.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to get pending job titles.
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope to get rejected job titles.
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }
}
