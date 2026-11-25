<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfileRevision extends Model
{
    public const FIELDS = [
        'birth_date', 'tin', 'gsis_bp_number', 'pwd', 'indigenous_people', 'solo_parent',
        'civil_service_eligibility', 'date_regularized', 'date_of_original_appointment', 'date_of_last_promotion_appointment',
    ];

    public const ALLOW_NA = ['gsis_bp_number', 'date_regularized', 'date_of_original_appointment', 'date_of_last_promotion_appointment'];

    public const DETAILS = [
        'pwd' => 'pwd_id_number', 'indigenous_people' => 'indigenous_people_detail',
        'solo_parent' => 'solo_parent_id_number', 'civil_service_eligibility' => 'eligibility_other',
    ];

    protected $fillable = ['employee_id', 'field', 'status', 'submitted', 'previous', 'resolved', 'submitted_by', 'reviewed_by', 'reviewed_at', 'reason'];

    protected $hidden = ['submitted', 'previous', 'resolved'];

    protected function casts(): array
    {
        return ['submitted' => 'encrypted:array', 'previous' => 'encrypted:array', 'resolved' => 'encrypted:array', 'reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
