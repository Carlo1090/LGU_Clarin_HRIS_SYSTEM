<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 48);
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->json('workdays');
            $table->char('am_in', 5);
            $table->char('am_out', 5);
            $table->char('pm_in', 5);
            $table->char('pm_out', 5);
            $table->timestamps();
            $table->unique(['scope_key', 'effective_from']);
        });

        Schema::create('attendance_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->json('working_dates')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', fn (Blueprint $table) => $table->dropColumn('working_dates'));
        Schema::dropIfExists('attendance_holidays');
        Schema::dropIfExists('attendance_schedules');
    }
};
