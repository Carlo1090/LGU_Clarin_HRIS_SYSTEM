<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('employment_status')->default('active');
            $table->date('date_hired')->nullable();
            $table->date('date_regularized')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('level');
            $table->string('school_name');
            $table->string('degree')->nullable();
            $table->string('course')->nullable();
            $table->year('year_graduated')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_work_experience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('position_title');
            $table->string('employment_type')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('duties')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('relationship');
            $table->date('birth_date')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('institution');
            $table->date('training_date');
            $table->integer('hours')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_salary_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('salary_grade')->nullable();
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->date('effectivity_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_appointment_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('appointment_type');
            $table->foreignId('old_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('new_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->date('effectivity_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('file_path');
            $table->string('uploaded_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('case_number')->unique();
            $table->string('offense');
            $table->text('details')->nullable();
            $table->string('sanction')->nullable();
            $table->date('incident_date');
            $table->date('resolved_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_records');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_appointment_changes');
        Schema::dropIfExists('employee_salary_history');
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('employee_dependents');
        Schema::dropIfExists('employee_work_experience');
        Schema::dropIfExists('employee_education');
        Schema::dropIfExists('employees');
    }
};
