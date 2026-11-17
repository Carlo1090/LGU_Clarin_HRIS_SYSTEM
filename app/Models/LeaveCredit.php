<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCredit extends Model
{
    protected $table = 'leave_credits';

    protected $fillable = ['employee_id', 'leave_type_id', 'earned_days', 'used_days', 'remaining_days', 'year'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
