<?php

namespace App\Models;

use Database\Factories\LeaveApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplication extends Model
{
    /** @use HasFactory<LeaveApplicationFactory> */
    use HasFactory;

    protected $table = 'leave_applications';

    protected $fillable = ['employee_id', 'leave_type_id', 'start_date', 'end_date', 'days_requested', 'reason', 'status', 'approved_by', 'working_dates'];

    protected function casts(): array
    {
        return ['working_dates' => 'array'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<LeaveType, $this> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
