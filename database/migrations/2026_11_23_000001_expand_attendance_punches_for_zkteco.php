<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->unsignedInteger('machine_user_id')->nullable()->after('biometric_id');
            $table->unsignedInteger('device_id')->nullable()->after('punched_at');
            $table->smallInteger('punch_state')->nullable()->after('device_id');
            $table->smallInteger('verify_mode')->nullable()->after('punch_state');
            $table->smallInteger('work_code')->nullable()->after('verify_mode');
            $table->foreignId('import_batch_id')->nullable()->after('attendance_import_id')
                ->constrained('attendance_imports')->restrictOnDelete();
            $table->string('derived_slot')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->json('flags')->nullable();
        });

        DB::table('attendance_punches')->update([
            'machine_user_id' => DB::raw('biometric_id'),
            'device_id' => DB::raw('raw_3'),
            'punch_state' => DB::raw('raw_4'),
            'verify_mode' => DB::raw('raw_5'),
            'work_code' => DB::raw('raw_6'),
            'import_batch_id' => DB::raw('attendance_import_id'),
        ]);

        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->unsignedInteger('machine_user_id')->nullable(false)->change();
            $table->dropForeign(['attendance_import_id']);
            $table->foreign('attendance_import_id')
                ->references('id')->on('attendance_imports')->restrictOnDelete();
            $table->dropUnique(['biometric_id', 'punched_at']);
            $table->unique(['machine_user_id', 'punched_at', 'device_id'], 'attendance_punches_machine_time_device_unique');
        });

        Schema::table('attendance_imports', function (Blueprint $table) {
            $table->dropUnique(['file_sha256']);
            $table->index('file_sha256');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Attendance punch history cannot be safely rolled back after storing terminal-specific punches.');
    }
};
