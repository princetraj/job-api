<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CV extends Model
{
    use HasFactory;

    protected $table = 'cvs';

    protected $fillable = [
        'employee_id',
        'title',
        'type',
        'file_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: CV belongs to an employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Set this CV as active and deactivate all others for the employee
     */
    public function setAsActive()
    {
        // Deactivate all other CVs for this employee
        self::where('employee_id', $this->employee_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        // Activate this CV
        $this->update(['is_active' => true]);
    }
}
