<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_daily', fn (Blueprint $table) => $table->string('schedule_source')->nullable()->change());
        DB::table('attendance_daily')->where('status', 'no_schedule')->whereNull('schedule_id')->update(['schedule_source' => null]);
    }

    public function down(): void
    {
        DB::table('attendance_daily')->whereNull('schedule_source')->update(['schedule_source' => 'department']);
        Schema::table('attendance_daily', fn (Blueprint $table) => $table->string('schedule_source')->nullable(false)->change());
    }
};
