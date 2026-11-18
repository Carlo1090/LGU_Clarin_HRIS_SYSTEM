<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $birth_date
 * @property-read Carbon|null $date_hired
 * @property-read Carbon|null $date_regularized
 * @property-read Carbon|null $date_of_original_appointment
 * @property-read Carbon|null $date_of_last_promotion_appointment
 * @property-read array<string, string>|null $custom_fields
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'department_id', 'position_id', 'employee_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'birth_date', 'employment_status', 'date_hired', 'date_regularized', 'photo_path', 'age', 'tin', 'date_of_original_appointment', 'date_of_last_promotion_appointment', 'appointment_status', 'civil_service_eligibility', 'comment_annotation', 'pwd', 'indigenous_people', 'solo_parent_id_number', 'gsis_bp_number', 'custom_fields'];

    protected function casts(): array
    {
        return ['birth_date' => 'date:Y-m-d', 'date_hired' => 'date:Y-m-d', 'date_regularized' => 'date:Y-m-d', 'date_of_original_appointment' => 'date:Y-m-d', 'date_of_last_promotion_appointment' => 'date:Y-m-d', 'age' => 'integer', 'custom_fields' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** @return HasOne<User, $this> */
    public function userProfile(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    /** @return HasOne<PlantillaItem, $this> */
    public function plantillaItem(): HasOne
    {
        return $this->hasOne(PlantillaItem::class);
    }

    /** @return HasMany<EmployeeEducation, $this> */
    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    /** @return HasMany<EmployeeWorkExperience, $this> */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(EmployeeWorkExperience::class);
    }

    /** @return HasMany<EmployeeDependent, $this> */
    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    /** @return HasMany<EmployeeTraining, $this> */
    public function trainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    /** @return HasMany<EmployeeSalaryHistory, $this> */
    public function salaryHistory(): HasMany
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    /** @return HasMany<EmployeeAppointmentChange, $this> */
    public function appointmentChanges(): HasMany
    {
        return $this->hasMany(EmployeeAppointmentChange::class);
    }

    /** @return HasMany<EmployeeDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /** @return HasMany<DisciplinaryRecord, $this> */
    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class);
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /** @return HasMany<OvertimeCtoRecord, $this> */
    public function overtimeCtoRecords(): HasMany
    {
        return $this->hasMany(OvertimeCtoRecord::class);
    }

    /** @return HasMany<TravelOrder, $this> */
    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }

    /** @return HasMany<LeaveCredit, $this> */
    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    /** @return HasMany<LeaveApplication, $this> */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    /** @return HasMany<Payroll, $this> */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /** @return HasMany<PerformanceCommitment, $this> */
    public function performanceCommitments(): HasMany
    {
        return $this->hasMany(PerformanceCommitment::class);
    }

    /** @return HasMany<PerformanceReview, $this> */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /** @return HasMany<PrivacyRequest, $this> */
    public function privacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class);
    }
}
