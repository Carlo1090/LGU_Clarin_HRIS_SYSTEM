<?php

namespace App\Models;

use App\Attendance\Models\BiometricLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
        'machine_id',
        'device_id',
        'punch_state',
        'raw_status',
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
            'machine_id' => 'integer',
            'device_id' => 'integer',
            'punch_state' => 'integer',
            'raw_status' => 'integer',
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

    /** @return BelongsTo<Employee, $this> */
    public function effectiveEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'effective_employee_id');
    }

    /** @param Builder<AttendancePunch> $query */
    public function scopeWithEffectiveEmployee(Builder $query): void
    {
        $query->select('attendance_punches.*')->selectRaw(self::employeeIdSql().' as effective_employee_id');
    }

    /** @return literal-string */
    public static function employeeIdSql(): string
    {
        if (! BiometricLink::historyAvailable()) {
            return '(SELECT id FROM employees WHERE employees.biometric_id = attendance_punches.biometric_id LIMIT 1)';
        }

        return <<<'SQL'
            COALESCE(
                (SELECT employee_id FROM attendance_biometric_links
                 WHERE attendance_biometric_links.biometric_id = attendance_punches.biometric_id
                   AND (effective_from IS NULL OR effective_from <= attendance_punches.punched_at)
                   AND (effective_to IS NULL OR effective_to > attendance_punches.punched_at)
                 LIMIT 1),
                (SELECT id FROM employees
                 WHERE employees.biometric_id = attendance_punches.biometric_id
                   AND NOT EXISTS (
                       SELECT 1 FROM attendance_biometric_links
                       WHERE attendance_biometric_links.biometric_id = attendance_punches.biometric_id
                   )
                 LIMIT 1)
            )
            SQL;
    }

    /** @param Builder<AttendancePunch> $query */
    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull(DB::raw(self::employeeIdSql()));
    }

    /** @param Builder<AttendancePunch> $query */
    public function scopeOwnedBy(Builder $query, int $employeeId): void
    {
        $query->where(DB::raw(self::employeeIdSql()), $employeeId);
    }

    /** @param Builder<AttendancePunch> $query */
    public function scopeMatchesCurrentOwner(Builder $query): void
    {
        $query->whereColumn(DB::raw(self::employeeIdSql()), 'employees.id');
    }
}
