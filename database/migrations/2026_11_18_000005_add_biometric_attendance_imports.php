<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('biometric_id')->nullable()->unique();
            $table->string('biometric_status')->default('pending');
            $table->foreignId('biometric_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('biometric_verified_at')->nullable();
            $table->string('biometric_note')->nullable();
        });

        Schema::table('attendance_imports', function (Blueprint $table) {
            $table->char('file_sha256', 64)->nullable()->unique();
            $table->unsignedInteger('total_lines')->default(0);
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->integer('clock_offset_seconds')->default(0);
            $table->json('invalid_lines')->nullable();
        });

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('biometric_id');
            $table->dateTime('punched_at')->index();
            $table->smallInteger('raw_3')->nullable();
            $table->smallInteger('raw_4')->nullable();
            $table->smallInteger('raw_5')->nullable();
            $table->smallInteger('raw_6')->nullable();
            $table->timestamp('created_at');
            $table->unique(['biometric_id', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_punches');

        Schema::table('attendance_imports', function (Blueprint $table) {
            $table->dropUnique(['file_sha256']);
            $table->dropColumn(['file_sha256', 'total_lines', 'inserted_count', 'duplicate_count', 'invalid_count', 'clock_offset_seconds', 'invalid_lines']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['biometric_id']);
            $table->dropConstrainedForeignId('biometric_verified_by');
            $table->dropColumn(['biometric_id', 'biometric_status', 'biometric_verified_at', 'biometric_note']);
        });
    }
};
