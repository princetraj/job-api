<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all employees with this skill.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_skill');
    }
}
