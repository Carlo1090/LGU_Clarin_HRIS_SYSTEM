<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_deactivation_permissions', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('granted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_deactivation_permissions');
    }
};
