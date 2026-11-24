<?php

namespace Tests\Feature;

use App\Attendance\Contracts\EmployeeDirectory;
use App\Attendance\Models\AttendanceDaily;
use App\Attendance\Models\DepartmentSchedule;
use App\Attendance\Models\EmployeeScheduleOverride;
use App\Attendance\Support\HrisEmployeeDirectory;
use App\Models\AttendanceImport;
use App\Models\AttendancePunch;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScheduleSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_step_one_attendance_schedule_models_persist(): void
    {
        $department = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $department->id]);

        $departmentSchedule = DepartmentSchedule::query()->create([
            'department_id' => $department->id,
            'am_in' => '08:00:00',
            'am_out' => '12:00:00',
            'pm_in' => '13:00:00',
            'pm_out' => '17:00:00',
            'grace_minutes' => 10,
            'crosses_midnight' => false,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);
        $override = EmployeeScheduleOverride::query()->create([
            'employee_id' => $employee->id,
            'am_in' => '07:30:00',
            'am_out' => '11:30:00',
            'pm_in' => '12:30:00',
            'pm_out' => '16:30:00',
            'grace_minutes' => 5,
            'crosses_midnight' => false,
            'effective_from' => '2026-11-01',
            'effective_to' => null,
            'reason' => 'Approved alternate schedule',
        ]);
        $daily = AttendanceDaily::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-11-24',
            'am_in_at' => '2026-11-24 07:30:00',
            'am_out_at' => '2026-11-24 11:30:00',
            'pm_in_at' => '2026-11-24 12:30:00',
            'pm_out_at' => '2026-11-24 16:30:00',
            'late_minutes_am' => 0,
            'late_minutes_pm' => 0,
            'undertime_minutes' => 0,
            'status' => 'complete',
            'flags' => [],
            'schedule_source' => 'override',
            'schedule_id' => $override->id,
        ]);

        $this->assertDatabaseHas('department_schedules', [
            'id' => $departmentSchedule->id,
            'department_id' => $department->id,
        ]);
        $this->assertDatabaseHas('employee_schedule_overrides', [
            'id' => $override->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('attendance_daily', [
            'id' => $daily->id,
            'employee_id' => $employee->id,
            'work_date' => '2026-11-24',
        ]);
    }

    public function test_attendance_punch_machine_time_alias_and_raw_status_are_stored(): void
    {
        $batch = AttendanceImport::query()->create([
            'file_name' => 'step-one.dat',
            'status' => 'completed',
        ]);
        $punch = AttendancePunch::query()->create([
            'attendance_import_id' => $batch->id,
            'import_batch_id' => $batch->id,
            'biometric_id' => 15,
            'machine_user_id' => 15,
            'machine_id' => 15,
            'punched_at' => '2026-11-24 08:00:00',
            'device_id' => 1,
            'punch_state' => 3,
            'raw_status' => 3,
            'created_at' => now(),
        ]);

        $this->assertSame(15, $punch->machine_id);
        $this->assertSame(3, $punch->raw_status);

        try {
            AttendancePunch::query()->create([
                'attendance_import_id' => $batch->id,
                'import_batch_id' => $batch->id,
                'biometric_id' => 15,
                'machine_user_id' => 15,
                'machine_id' => 15,
                'punched_at' => '2026-11-24 08:00:00',
                'device_id' => 2,
                'punch_state' => 0,
                'raw_status' => 0,
                'created_at' => now(),
            ]);
            $this->fail('Duplicate (machine_id, punched_at) punch was inserted.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertDatabaseCount('attendance_punches', 1);
        }
    }

    public function test_attendance_module_employee_directory_binding_uses_hris_directory(): void
    {
        $department = Department::factory()->create(['name' => 'Office of Testing']);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'biometric_id' => 15,
        ]);

        $directory = $this->app->make(EmployeeDirectory::class);

        $this->assertInstanceOf(HrisEmployeeDirectory::class, $directory);
        $this->assertSame(15, $directory->find($employee->id)['biometric_id']);
        $this->assertSame('Office of Testing', $directory->departmentOf($employee->id)['name']);
    }
}
