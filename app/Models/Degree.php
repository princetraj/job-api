<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Degree extends Model
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
     * Check if degree is approved.
     */
    public function getIsApprovedAttribute()
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Scope to get only approved degrees.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to get pending degrees.
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope to get rejected degrees.
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Get all employee educations with this degree.
     */
    public function employeeEducations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * Get count of employees with this degree.
     */
    public function getEmployeeCountAttribute()
    {
        return $this->employeeEducations()->distinct('employee_id')->count('employee_id');
    }
}
