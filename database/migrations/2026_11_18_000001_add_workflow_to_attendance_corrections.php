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
            $table->dateTime('proposed_time_in')->nullable();
            $table->dateTime('proposed_time_out')->nullable();
            $table->string('status')->default('pending');
        });

        DB::table('attendance_corrections')->update(['status' => 'legacy']);
        DB::table('attendance_corrections')->whereNotNull('approved_at')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropColumn(['proposed_time_in', 'proposed_time_out', 'status']);
        });
    }
};
