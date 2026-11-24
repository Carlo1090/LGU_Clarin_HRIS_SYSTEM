<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('department_schedules') && ! Schema::hasIndex('department_schedules', 'department_schedules_effective_index')) {
            Schema::table('department_schedules', function (Blueprint $table) {
                $table->index(
                    ['department_id', 'effective_from', 'effective_to'],
                    'department_schedules_effective_index',
                );
            });
        }

        if (Schema::hasTable('employee_schedule_overrides') && ! Schema::hasIndex('employee_schedule_overrides', 'employee_schedules_effective_index')) {
            Schema::table('employee_schedule_overrides', function (Blueprint $table) {
                $table->index(
                    ['employee_id', 'effective_from', 'effective_to'],
                    'employee_schedules_effective_index',
                );
            });
        }

        if (Schema::hasTable('attendance_punches')) {
            if (! Schema::hasColumn('attendance_punches', 'machine_id')) {
                Schema::table('attendance_punches', function (Blueprint $table) {
                    $table->unsignedInteger('machine_id')->nullable()->after('machine_user_id');
                });
            }
            if (! Schema::hasColumn('attendance_punches', 'raw_status')) {
                Schema::table('attendance_punches', function (Blueprint $table) {
                    $table->smallInteger('raw_status')->nullable()->after('punch_state');
                });
            }
            if (! Schema::hasIndex('attendance_punches', 'attendance_punches_machine_time_unique')) {
                Schema::table('attendance_punches', function (Blueprint $table) {
                    $table->unique(
                        ['machine_id', 'punched_at'],
                        'attendance_punches_machine_time_unique',
                    );
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('department_schedules')) {
            Schema::table('department_schedules', function (Blueprint $table) {
                $table->dropIndex('department_schedules_effective_index');
            });
        }
        if (Schema::hasTable('employee_schedule_overrides')) {
            Schema::table('employee_schedule_overrides', function (Blueprint $table) {
                $table->dropIndex('employee_schedules_effective_index');
            });
        }
    }
};
