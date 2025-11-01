<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_educations';

    protected $fillable = [
        'employee_id',
        'education_level_id',
        'degree_id',
        'university_id',
        'field_of_study_id',
        'year_start',
        'year_end',
    ];

    /**
     * Get the employee that owns this education.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the education level.
     */
    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /**
     * Get the degree.
     */
    public function degree()
    {
        return $this->belongsTo(Degree::class);
    }

    /**
     * Get the university.
     */
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    /**
     * Get the field of study.
     */
    public function fieldOfStudy()
    {
        return $this->belongsTo(FieldOfStudy::class);
    }

    /**
     * Scope to get only educations with approved degrees.
     */
    public function scopeApprovedDegree($query)
    {
        return $query->whereHas('degree', function($q) {
            $q->where('approval_status', 'approved');
        });
    }

    /**
     * Scope to get only educations with approved universities.
     */
    public function scopeApprovedUniversity($query)
    {
        return $query->whereHas('university', function($q) {
            $q->where('approval_status', 'approved');
        });
    }

    /**
     * Scope to get only educations with approved fields of study.
     */
    public function scopeApprovedField($query)
    {
        return $query->whereHas('fieldOfStudy', function($q) {
            $q->where('approval_status', 'approved');
        });
    }
}
