<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = ['employee_id', 'attendance_date', 'time_in', 'time_out', 'total_hours', 'status', 'source', 'overtime_hours'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
