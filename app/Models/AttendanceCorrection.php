<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    protected $table = 'attendance_corrections';

    protected $fillable = ['attendance_record_id', 'employee_id', 'correction_type', 'reason', 'proposed_time_in', 'proposed_time_out', 'status', 'approved_by', 'approved_at'];

    /** @return BelongsTo<AttendanceRecord, $this> */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
