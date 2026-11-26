<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->date('work_date')->nullable();
            $table->string('slot', 8)->nullable();
            $table->dateTime('proposed_at')->nullable();
            $table->text('evidence')->nullable();
            $table->text('decision_comment')->nullable();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['employee_id', 'work_date', 'status'], 'attendance_correction_day_status');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropIndex('attendance_correction_day_status');
            $table->dropConstrainedForeignId('filed_by');
            $table->dropColumn(['work_date', 'slot', 'proposed_at', 'evidence', 'decision_comment']);
        });
    }
};
