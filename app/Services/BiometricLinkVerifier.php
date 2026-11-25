<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class BiometricLinkVerifier
{
    public function verifyPending(User $actor, ?int $employeeId = null): void
    {
        $minimum = (int) config('attendance.minimum_verification_punches', 50);

        Employee::query()->whereNotNull('biometric_id')->where('biometric_status', 'pending')
            ->when($employeeId !== null, fn ($query) => $query->whereKey($employeeId))
            ->withMin(['attendancePunches as first_punch' => fn ($punches) => $punches->matchesCurrentOwner()], 'punched_at')
            ->withCount(['attendancePunches' => fn ($punches) => $punches->matchesCurrentOwner()])
            ->chunkById(100, function ($employees) use ($actor, $minimum): void {
                foreach ($employees as $employee) {
                    $count = (int) $employee->attendance_punches_count;
                    $verified = false;
                    if ($count === 0) {
                        $note = 'No imported punches match this ID. Check the ID or import the employee’s attendance logs.';
                    } elseif ($employee->date_hired && substr($employee->getAttribute('first_punch'), 0, 10) < $employee->date_hired->toDateString()) {
                        $note = 'Some punches are dated before the hire date. Check the saved ID and hire date.';
                    } else {
                        $verified = true;
                        $note = $count < $minimum ? trans_choice('Only :count matching punch was found.|Only :count matching punches were found.', $count) : null;
                    }

                    Employee::query()->whereKey($employee->id)->where('biometric_id', $employee->biometric_id)
                        ->where('biometric_status', 'pending')->update([
                            'biometric_status' => $verified ? 'verified' : 'pending',
                            'biometric_note' => $note,
                            'biometric_verified_at' => $verified ? now() : null,
                            'biometric_verified_by' => $verified ? $actor->id : null,
                        ]);
                }
            });
    }
}
