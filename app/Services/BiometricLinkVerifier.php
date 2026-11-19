<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class BiometricLinkVerifier
{
    public function verifyPending(User $actor): void
    {
        $minimum = (int) config('attendance.minimum_verification_punches', 50);

        Employee::query()->whereNotNull('biometric_id')->where('biometric_status', 'pending')
            ->withMin('attendancePunches as first_punch', 'punched_at')->withCount('attendancePunches')
            ->chunkById(100, function ($employees) use ($actor, $minimum): void {
                foreach ($employees as $employee) {
                    $count = (int) $employee->attendance_punches_count;
                    $verified = false;
                    if ($count === 0) {
                        $note = 'No imported punches for this biometric ID.';
                    } elseif ($employee->date_hired && substr($employee->first_punch, 0, 10) < $employee->date_hired->toDateString()) {
                        $note = 'Imported punches include a date before the employee was hired.';
                    } else {
                        $verified = true;
                        $note = $count < $minimum ? "Only {$count} punch(es) available at verification." : null;
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
