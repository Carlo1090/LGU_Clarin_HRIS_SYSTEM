<?php

namespace App\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeScheduleOverride extends Model
{
    protected $fillable = [
        'employee_id',
        'am_in',
        'am_out',
        'pm_in',
        'pm_out',
        'grace_minutes',
        'crosses_midnight',
        'effective_from',
        'effective_to',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'grace_minutes' => 'integer',
            'crosses_midnight' => 'boolean',
            'effective_from' => 'date:Y-m-d',
            'effective_to' => 'date:Y-m-d',
        ];
    }
}
