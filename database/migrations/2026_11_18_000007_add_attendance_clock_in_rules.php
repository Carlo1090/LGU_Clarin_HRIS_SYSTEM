<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_clock_in_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->char('starts_at', 5)->nullable();
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->char('late_after', 8)->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_clock_in_rules');
    }
};
