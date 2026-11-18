<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSchedule extends Model
{
    protected $fillable = [
        'scope_key', 'department_id', 'employee_id', 'effective_from', 'effective_until',
        'workdays', 'am_in', 'am_out', 'pm_in', 'pm_out',
    ];

    protected function casts(): array
    {
        return ['workdays' => 'array'];
    }

    public function hours(): string
    {
        return "{$this->am_in}–{$this->am_out} / {$this->pm_in}–{$this->pm_out}";
    }

    /** @param list<string> $punches */
    public function variances(array $punches): ?array
    {
        if (count($punches) !== AttendancePunch::DTR_PUNCH_COUNT) {
            return null;
        }
        [$amIn, $amOut, $pmIn, $pmOut] = array_map(fn (string $time) => substr($time, 0, 5), $punches);
        if ($amIn >= $this->am_out || $amOut > $this->pm_in || $pmIn < $this->am_out || $pmOut <= $this->pm_in) {
            return null;
        }

        return [
            'late' => max(0, $this->minutes($amIn) - $this->minutes($this->am_in))
                + max(0, $this->minutes($pmIn) - $this->minutes($this->pm_in)),
            'undertime' => max(0, $this->minutes($this->am_out) - $this->minutes($amOut))
                + max(0, $this->minutes($this->pm_out) - $this->minutes($pmOut)),
        ];
    }

    private function minutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }
}
