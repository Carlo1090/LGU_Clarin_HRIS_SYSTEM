<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDependent extends Model
{
    protected $table = 'employee_dependents';

    protected $fillable = ['employee_id', 'full_name', 'relationship', 'birth_date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
