<?php

namespace App\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDaily extends Model
{
    protected $table = 'attendance_daily';

    protected $fillable = [
        'employee_id',
        'work_date',
        'am_in_at',
        'am_out_at',
        'pm_in_at',
        'pm_out_at',
        'late_minutes_am',
        'late_minutes_pm',
        'undertime_minutes',
        'status',
        'flags',
        'schedule_source',
        'schedule_id',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date:Y-m-d',
            'am_in_at' => 'datetime',
            'am_out_at' => 'datetime',
            'pm_in_at' => 'datetime',
            'pm_out_at' => 'datetime',
            'late_minutes_am' => 'integer',
            'late_minutes_pm' => 'integer',
            'undertime_minutes' => 'integer',
            'flags' => 'array',
            'schedule_id' => 'integer',
        ];
    }
}
