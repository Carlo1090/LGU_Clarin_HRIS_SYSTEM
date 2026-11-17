<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    protected $table = 'disciplinary_records';

    protected $fillable = ['employee_id', 'case_number', 'offense', 'details', 'sanction', 'incident_date', 'resolved_date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
