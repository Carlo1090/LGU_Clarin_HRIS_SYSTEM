<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table): void {
            $table->boolean('commutation')->default(false)->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table): void {
            $table->dropColumn('commutation');
        });
    }
};
