<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Employee|null $employee
 */
class AttendancePunch extends Model
{
    public const UPDATED_AT = null;

    public const DTR_PUNCH_COUNT = 4;

    protected $fillable = [
        'attendance_import_id',
        'import_batch_id',
        'biometric_id',
        'machine_user_id',
        'punched_at',
        'device_id',
        'punch_state',
        'verify_mode',
        'work_code',
        'raw_3',
        'raw_4',
        'raw_5',
        'raw_6',
        'derived_slot',
        'is_duplicate',
        'flags',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'machine_user_id' => 'integer',
            'device_id' => 'integer',
            'punch_state' => 'integer',
            'verify_mode' => 'integer',
            'work_code' => 'integer',
            'is_duplicate' => 'boolean',
            'flags' => 'array',
        ];
    }

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
