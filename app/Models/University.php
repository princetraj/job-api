<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
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
     * Check if university is approved.
     */
    public function getIsApprovedAttribute()
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Scope to get only approved universities.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to get pending universities.
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope to get rejected universities.
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Get all employee educations from this university.
     */
    public function employeeEducations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * Get count of employees from this university.
     */
    public function getEmployeeCountAttribute()
    {
        return $this->employeeEducations()->distinct('employee_id')->count('employee_id');
    }
}
