<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $attendanceDate = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'employee_id' => Employee::factory(),
            'attendance_date' => $attendanceDate->format('Y-m-d'),
            'time_in' => (clone $attendanceDate)->setTime(8, 0)->format('Y-m-d H:i:s'),
            'time_out' => (clone $attendanceDate)->setTime(17, 0)->format('Y-m-d H:i:s'),
            'total_hours' => 8,
            'status' => 'present',
            'source' => 'manual',
            'overtime_hours' => 0,
        ];
    }
}
