<?php

namespace App\Attendance\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BiometricLink extends Model
{
    protected $table = 'attendance_biometric_links';

    protected $fillable = ['employee_id', 'biometric_id', 'effective_from', 'effective_to', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function effectiveAt(Builder $query, DateTimeInterface $punchedAt): void
    {
        $query->where(fn (Builder $start) => $start->whereNull('effective_from')->orWhere('effective_from', '<=', $punchedAt))
            ->where(fn (Builder $end) => $end->whereNull('effective_to')->orWhere('effective_to', '>', $punchedAt));
    }
}
