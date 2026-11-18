<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAppointmentChange extends Model
{
    protected $table = 'employee_appointment_changes';

    protected $fillable = ['employee_id', 'appointment_type', 'old_position_id', 'new_position_id', 'effectivity_date', 'remarks'];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function oldPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'old_position_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'new_position_id');
    }
}
