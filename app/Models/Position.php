<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['department_id', 'title', 'code', 'job_grade', 'is_active'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function plantillaItems(): HasMany
    {
        return $this->hasMany(PlantillaItem::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }
}
