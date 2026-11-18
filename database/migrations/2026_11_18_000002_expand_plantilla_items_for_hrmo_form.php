<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PLANTILLA_COLUMNS = [
        'department_code', 'department_name', 'organizational_unit', 'position_title', 'salary_grade',
        'authorized_annual_salary', 'actual_annual_salary', 'step', 'area_code', 'area_type', 'level',
        'abolished', 'dissolved', 'position_classification',
    ];

    private const EMPLOYEE_COLUMNS = [
        'age', 'tin', 'date_of_original_appointment', 'date_of_last_promotion_appointment', 'appointment_status',
        'civil_service_eligibility', 'comment_annotation', 'pwd', 'indigenous_people', 'solo_parent_id_number', 'gsis_bp_number',
    ];

    public function up(): void
    {
        Schema::table('plantilla_items', function (Blueprint $table): void {
            $table->string('department_code')->nullable();
            $table->string('department_name')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('organizational_unit')->nullable();
            $table->string('position_title')->nullable();
            $table->decimal('salary_grade', 5, 2)->nullable();
            $table->decimal('authorized_annual_salary', 15, 2)->nullable();
            $table->decimal('actual_annual_salary', 15, 2)->nullable();
            $table->decimal('step', 4, 2)->nullable();
            $table->string('area_code', 50)->nullable();
            $table->string('area_type', 1)->nullable();
            $table->string('level', 1)->nullable();
            $table->string('abolished')->nullable();
            $table->string('dissolved')->nullable();
            $table->string('position_classification', 100)->nullable();
            $table->foreignId('department_id')->nullable()->change();
            $table->foreignId('position_id')->nullable()->change();
            $table->year('fiscal_year')->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('tin', 64)->nullable();
            $table->date('date_of_original_appointment')->nullable();
            $table->date('date_of_last_promotion_appointment')->nullable();
            $table->string('appointment_status', 2)->nullable();
            $table->string('civil_service_eligibility')->nullable();
            $table->text('comment_annotation')->nullable();
            $table->string('pwd', 1)->nullable();
            $table->string('indigenous_people', 1)->nullable();
            $table->string('solo_parent_id_number', 255)->nullable();
            $table->string('gsis_bp_number', 100)->nullable();
        });

        $departmentCodes = DB::table('departments')->pluck('code', 'id');
        $departmentNames = DB::table('departments')->pluck('name', 'id');
        foreach (DB::table('plantilla_items')->select('id', 'department_id')->cursor() as $item) {
            DB::table('plantilla_items')->where('id', $item->id)->update([
                'department_code' => $departmentCodes[$item->department_id] ?? null,
                'department_name' => $departmentNames[$item->department_id] ?? null,
            ]);
        }

        $hasDuplicateItemNumber = DB::table('plantilla_items')
            ->select('department_code', 'item_number')
            ->whereNotNull('department_code')->whereNotNull('item_number')
            ->groupBy('department_code', 'item_number')->havingRaw('COUNT(*) > 1')->exists();
        if ($hasDuplicateItemNumber) {
            throw new RuntimeException('Resolve duplicate Plantilla item numbers within each department before migrating.');
        }

        Schema::table('plantilla_items', function (Blueprint $table): void {
            $table->unique(['department_code', 'item_number'], 'plantilla_items_department_item_number_unique');
            $table->unique('employee_id', 'plantilla_items_employee_id_unique');
        });
    }

    public function down(): void
    {
        $hasFormData = DB::table('plantilla_items')->where(function ($query): void {
            foreach (array_diff(self::PLANTILLA_COLUMNS, ['department_code', 'department_name']) as $column) {
                $query->orWhereNotNull($column);
            }
        })->exists();
        $hasEmployeeData = DB::table('employees')->where(function ($query): void {
            foreach (self::EMPLOYEE_COLUMNS as $column) {
                $query->orWhereNotNull($column);
            }
        })->exists();

        if ($hasFormData || $hasEmployeeData || DB::table('plantilla_items')->whereNull('department_id')->orWhereNull('position_id')->orWhereNull('fiscal_year')->exists()) {
            throw new RuntimeException('Plantilla form data exists. Export and preserve it before rolling back this migration.');
        }

        Schema::table('plantilla_items', function (Blueprint $table): void {
            $table->dropUnique('plantilla_items_department_item_number_unique');
            $table->dropUnique('plantilla_items_employee_id_unique');
            $table->dropColumn(self::PLANTILLA_COLUMNS);
            $table->foreignId('department_id')->nullable(false)->change();
            $table->foreignId('position_id')->nullable(false)->change();
            $table->year('fiscal_year')->nullable(false)->change();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn(self::EMPLOYEE_COLUMNS);
        });
    }
};
