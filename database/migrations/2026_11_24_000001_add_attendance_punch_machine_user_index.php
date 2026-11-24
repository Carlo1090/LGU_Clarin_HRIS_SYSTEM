<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->index('machine_user_id', 'attendance_punches_machine_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_punches', function (Blueprint $table) {
            $table->dropIndex('attendance_punches_machine_user_id_index');
        });
    }
};
