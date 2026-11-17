<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('upload_batch')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->string('status')->default('present');
            $table->string('source')->default('manual');
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('correction_type');
            $table->text('reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_cto_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 8, 2)->default(0);
            $table->string('type')->default('overtime');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('travel_order_no')->unique();
            $table->string('destination');
            $table->date('departure_date');
            $table->date('return_date');
            $table->text('purpose');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('default_days')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->integer('earned_days')->default(0);
            $table->integer('used_days')->default(0);
            $table->integer('remaining_days')->default(0);
            $table->year('year');
            $table->timestamps();
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested')->default(0);
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->string('deduction_type');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_deductions');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_credits');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('travel_orders');
        Schema::dropIfExists('overtime_cto_records');
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_imports');
    }
};
