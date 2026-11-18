<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->boolean('must_change_password')->default(false);
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Email-less accounts exist. Assign them email addresses before rolling back.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('must_change_password');
            $table->string('email')->nullable(false)->change();
        });
    }
};
