<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['parent_department_id', 'name', 'code', 'description'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
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
