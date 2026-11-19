<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePunch extends Model
{
    public const UPDATED_AT = null;

    public const DTR_PUNCH_COUNT = 4;

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'biometric_id', 'biometric_id');
    }

    /** @param Builder<AttendancePunch> $query */
    public function scopeUnassigned(Builder $query): void
    {
        $query->whereDoesntHave('employee');
    }
}
