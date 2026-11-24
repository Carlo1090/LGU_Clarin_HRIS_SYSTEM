<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_biometric_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('biometric_id');
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_to')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['biometric_id', 'effective_from'], 'attendance_bio_links_machine_start_index');
            $table->index(['employee_id', 'effective_from'], 'attendance_bio_links_employee_start_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_biometric_links');
    }
};
