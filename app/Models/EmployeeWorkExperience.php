<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkExperience extends Model
{
    protected $table = 'employee_work_experience';

    protected $fillable = ['employee_id', 'company_name', 'position_title', 'employment_type', 'start_date', 'end_date', 'duties'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
