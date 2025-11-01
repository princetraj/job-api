<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldOfStudy extends Model
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
     * Check if field of study is approved.
     */
    public function getIsApprovedAttribute()
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Scope to get only approved fields of study.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope to get pending fields of study.
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope to get rejected fields of study.
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Get all employee educations with this field of study.
     */
    public function employeeEducations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * Get count of employees with this field of study.
     */
    public function getEmployeeCountAttribute()
    {
        return $this->employeeEducations()->distinct('employee_id')->count('employee_id');
    }
}
