<?php

namespace App\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentAssignment extends Model
{
    protected $table = 'attendance_department_assignments';

    protected $fillable = ['employee_id', 'department_id', 'effective_from', 'effective_to', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date:Y-m-d',
            'effective_to' => 'date:Y-m-d',
        ];
    }
}
