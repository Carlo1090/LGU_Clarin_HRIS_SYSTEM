<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'department_id', 'position_id', 'employee_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'birth_date', 'employment_status', 'date_hired', 'date_regularized', 'photo_path', 'age', 'tin', 'date_of_original_appointment', 'date_of_last_promotion_appointment', 'appointment_status', 'civil_service_eligibility', 'comment_annotation', 'pwd', 'indigenous_people', 'solo_parent_id_number', 'gsis_bp_number', 'custom_fields'];

    protected function casts(): array
    {
        return ['birth_date' => 'date:Y-m-d', 'date_hired' => 'date:Y-m-d', 'date_regularized' => 'date:Y-m-d', 'date_of_original_appointment' => 'date:Y-m-d', 'date_of_last_promotion_appointment' => 'date:Y-m-d', 'age' => 'integer', 'custom_fields' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function userProfile(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function plantillaItem(): HasOne
    {
        return $this->hasOne(PlantillaItem::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(EmployeeWorkExperience::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function salaryHistory(): HasMany
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    public function appointmentChanges(): HasMany
    {
        return $this->hasMany(EmployeeAppointmentChange::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function overtimeCtoRecords(): HasMany
    {
        return $this->hasMany(OvertimeCtoRecord::class);
    }

    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function performanceCommitments(): HasMany
    {
        return $this->hasMany(PerformanceCommitment::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function privacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class);
    }
}
