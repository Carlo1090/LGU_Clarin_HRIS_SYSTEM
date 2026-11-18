<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->unsignedBigInteger('attendance_record_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('attendance_corrections')->whereNull('attendance_record_id')->exists()) {
            throw new RuntimeException('Resolve unlinked attendance corrections before rolling back.');
        }

        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->unsignedBigInteger('attendance_record_id')->nullable(false)->change();
        });
    }
};
