<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profile_drafts', function (Blueprint $table): void {
            $table->foreignId('employee_id')->primary()->constrained()->cascadeOnDelete();
            $table->longText('values');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profile_drafts');
    }
};
