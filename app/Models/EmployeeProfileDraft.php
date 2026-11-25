<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfileDraft extends Model
{
    protected $primaryKey = 'employee_id';

    public $incrementing = false;

    protected $fillable = ['employee_id', 'values'];

    protected $hidden = ['values'];

    protected function casts(): array
    {
        return ['values' => 'encrypted:array'];
    }
}
