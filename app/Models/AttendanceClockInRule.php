<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceClockInRule extends Model
{
    protected $fillable = ['employee_id', 'effective_from', 'starts_at', 'grace_minutes', 'late_after'];
}
