<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->integer('open_positions')->default(1);
            $table->date('closing_date')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('application_date');
            $table->string('status')->default('applied');
            $table->timestamps();
        });

        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('file_path');
            $table->timestamps();
        });

        Schema::create('applicant_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('rating', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->date('evaluation_date');
            $table->timestamps();
        });

        Schema::create('recruitment_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('selection_stage');
            $table->string('status')->default('pending');
            $table->date('selected_date')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('performance_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
            $table->text('commitment_text');
            $table->string('target_value')->nullable();
            $table->text('accomplishments')->nullable();
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('final_rating', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->date('review_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_commitments');
        Schema::dropIfExists('evaluation_periods');
        Schema::dropIfExists('recruitment_selections');
        Schema::dropIfExists('applicant_evaluations');
        Schema::dropIfExists('applicant_documents');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('vacancies');
    }
};
