<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    protected $table = 'attendance_corrections';

    public const SLOTS = ['am_in', 'am_out', 'pm_in', 'pm_out'];

    protected $fillable = ['attendance_record_id', 'employee_id', 'correction_type', 'reason', 'proposed_time_in', 'proposed_time_out', 'status', 'approved_by', 'approved_at', 'work_date', 'slot', 'proposed_at', 'evidence', 'decision_comment', 'filed_by'];

    /** @return BelongsTo<User, $this> */
    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function scopeForWorkDates(Builder $query, string $from, string $to): Builder
    {
        return $query->where(fn (Builder $dates) => $dates->whereBetween('work_date', [$from, $to])
            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('work_date')->where(fn (Builder $date) => $date
                ->whereBetween('proposed_time_in', [$from.' 00:00:00', $to.' 23:59:59'])
                ->orWhereHas('attendanceRecord', fn (Builder $record) => $record->whereBetween('attendance_date', [$from, $to])))));
    }

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
