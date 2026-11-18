<?php

namespace App\Models;

use Database\Factories\PayrollFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payroll extends Model
{
    /** @use HasFactory<PayrollFactory> */
    use HasFactory;

    protected $table = 'payrolls';

    protected $fillable = ['employee_id', 'payroll_period_id', 'gross_pay', 'net_pay', 'status'];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<PayrollPeriod, $this> */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /** @return HasMany<PayrollDeduction, $this> */
    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    /** @return HasOne<Payslip, $this> */
    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }
}
