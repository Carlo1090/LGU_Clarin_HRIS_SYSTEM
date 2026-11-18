<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $table = 'payslips';

    protected $fillable = ['payroll_id', 'employee_id', 'file_path', 'generated_at'];

    /** @param Builder<Payslip> $query */
    public function scopeReleasedForEmployee(Builder $query, int $employeeId): void
    {
        $query->where('employee_id', $employeeId)
            ->whereNotNull('generated_at')
            ->whereHas('payroll', fn ($payroll) => $payroll
                ->where('employee_id', $employeeId)
                ->whereIn('status', ['approved', 'paid'])
                ->whereHas('payrollPeriod', fn ($period) => $period->where('released_at', '<=', now())));
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
