<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('department_schedules')) {
        Schema::create('department_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->time('am_in');
            $table->time('am_out');
            $table->time('pm_in');
            $table->time('pm_out');
            $table->unsignedInteger('grace_minutes')->default(0);
            $table->boolean('crosses_midnight')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'effective_from', 'effective_to'], 'department_schedules_effective_index');
        });
        }

        if (! Schema::hasTable('employee_schedule_overrides')) {
        Schema::create('employee_schedule_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->time('am_in');
            $table->time('am_out');
            $table->time('pm_in');
            $table->time('pm_out');
            $table->unsignedInteger('grace_minutes')->default(0);
            $table->boolean('crosses_midnight')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('reason');
            $table->timestamps();

            $table->index(['employee_id', 'effective_from', 'effective_to'], 'employee_schedules_effective_index');
        });
        }

        if (! Schema::hasTable('attendance_daily')) {
        Schema::create('attendance_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('am_in_at')->nullable();
            $table->dateTime('am_out_at')->nullable();
            $table->dateTime('pm_in_at')->nullable();
            $table->dateTime('pm_out_at')->nullable();
            $table->unsignedInteger('late_minutes_am')->default(0);
            $table->unsignedInteger('late_minutes_pm')->default(0);
            $table->unsignedInteger('undertime_minutes')->default(0);
            $table->string('status');
            $table->json('flags')->nullable();
            $table->string('schedule_source');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });
        }

        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->unsignedInteger('machine_id')->nullable()->after('machine_user_id');
            $table->smallInteger('raw_status')->nullable()->after('punch_state');
        });

        DB::table('attendance_punches')->update([
            'machine_id' => DB::raw('machine_user_id'),
            'raw_status' => DB::raw('punch_state'),
        ]);

        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->unique(['machine_id', 'punched_at'], 'attendance_punches_machine_time_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->dropUnique('attendance_punches_machine_time_unique');
            $table->dropColumn(['machine_id', 'raw_status']);
        });

        Schema::dropIfExists('attendance_daily');
        Schema::dropIfExists('employee_schedule_overrides');
        Schema::dropIfExists('department_schedules');
    }
};
