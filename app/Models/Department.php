<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    public const PLANTILLA_OFFICES = [
        'MAYOR' => 'Office of the Municipal Mayor',
        'VICE-MAYOR' => 'Office of the Vice Mayor',
        'SANGGUNIANG-BAYAN' => 'Office of the Sangguniang Bayan',
        'SB-SECRETARY' => 'Office of the Secretary to the SB',
        'MPDC' => 'Office of the M.P.D.C.',
        'ENGINEER' => 'Office of the Municipal Engineer',
        'ASSESSOR' => 'Office of the Municipal Assessor',
        'BUDGET' => 'Office of the Municipal Budget',
        'TREASURER' => 'Office of the Municipal Treasurer',
        'ACCOUNTANT' => 'Office of the Municipal Accountant',
        'CIVIL-REGISTRAR' => 'Office of the Municipal Civil Registrar',
        'HEALTH' => 'Office of the Municipal Health Officer',
        'AGRICULTURIST' => 'Office of the Municipal Agriculturist',
        'WELFARE' => 'Office of the Municipal Welfare Officer',
    ];

    protected $fillable = ['parent_department_id', 'name', 'code', 'description'];

    /** @return BelongsTo<Department, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    /** @return HasMany<Department, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    /** @return HasMany<Position, $this> */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /** @return HasMany<PlantillaItem, $this> */
    public function plantillaItems(): HasMany
    {
        return $this->hasMany(PlantillaItem::class);
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /** @return HasMany<Vacancy, $this> */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }
}
