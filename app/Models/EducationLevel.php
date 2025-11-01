<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'order',
    ];

    /**
     * Scope to get only active education levels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orderBy('order');
    }

    /**
     * Get all employee educations with this education level.
     */
    public function employeeEducations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /**
     * Get count of employees with this education level.
     */
    public function getEmployeeCountAttribute()
    {
        return $this->employeeEducations()->distinct('employee_id')->count('employee_id');
    }
}
