<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaItem extends Model
{
    protected $fillable = [
        'department_id', 'department_code', 'department_name', 'employee_id', 'organizational_unit', 'item_number', 'position_id', 'position_title',
        'salary_grade', 'authorized_annual_salary', 'actual_annual_salary', 'step', 'area_code', 'area_type', 'level',
        'abolished', 'dissolved', 'position_classification',
        'allocated_count', 'filled_count', 'status', 'fiscal_year',
    ];

    protected $casts = [
        'salary_grade' => 'decimal:2',
        'authorized_annual_salary' => 'decimal:2',
        'actual_annual_salary' => 'decimal:2',
        'step' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
