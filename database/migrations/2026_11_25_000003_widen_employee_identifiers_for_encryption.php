<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->text('tin')->nullable()->change();
            $table->text('gsis_bp_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        $length = DB::getDriverName() === 'sqlite' ? 'length' : 'char_length';
        if (DB::table('employees')->where(function ($query) use ($length): void {
            $query->whereLike('tin', 'hris-encrypted:v1:%')
                ->orWhereLike('gsis_bp_number', 'hris-encrypted:v1:%')
                ->orWhereRaw($length.'(tin) > 64')
                ->orWhereRaw($length.'(gsis_bp_number) > 100');
        })->exists()) {
            throw new RuntimeException('Restore identifiers to their original format before narrowing the columns.');
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->string('tin', 64)->nullable()->change();
            $table->string('gsis_bp_number', 100)->nullable()->change();
        });
    }
};
