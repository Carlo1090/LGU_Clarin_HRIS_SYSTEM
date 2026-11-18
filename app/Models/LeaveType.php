<?php

namespace App\Models;

use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    protected $table = 'leave_types';

    protected $fillable = ['name', 'code', 'default_days', 'is_paid', 'description', 'is_active'];

    /** @return HasMany<LeaveCredit, $this> */
    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    /** @return HasMany<LeaveApplication, $this> */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }
}
