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

        if (! Schema::hasColumn('attendance_punches', 'machine_id')) {
            Schema::table('attendance_punches', fn (Blueprint $table) => $table->unsignedInteger('machine_id')->nullable()->after('machine_user_id'));
        }
        if (! Schema::hasColumn('attendance_punches', 'raw_status')) {
            Schema::table('attendance_punches', fn (Blueprint $table) => $table->smallInteger('raw_status')->nullable()->after('punch_state'));
        }

        if (! Schema::hasIndex('attendance_punches', 'attendance_punches_machine_time_unique')) {
            // Keep all terminal records and reserve the unique alias for the first punch at each timestamp.
            $canonical = DB::query()->fromSub(
                DB::table('attendance_punches')->selectRaw('MIN(id) as id')->groupBy('machine_user_id', 'punched_at'),
                'canonical',
            )->select('id');
            DB::table('attendance_punches')->whereIn('id', $canonical)->update(['machine_id' => DB::raw('machine_user_id')]);
            DB::table('attendance_punches')->whereNotIn('id', $canonical)->whereNotNull('machine_id')->update(['machine_id' => null]);

            Schema::table('attendance_punches', function (Blueprint $table) {
                $table->unique(['machine_id', 'punched_at'], 'attendance_punches_machine_time_unique');
            });
        }
        DB::table('attendance_punches')->whereNull('raw_status')->update(['raw_status' => DB::raw('punch_state')]);
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
