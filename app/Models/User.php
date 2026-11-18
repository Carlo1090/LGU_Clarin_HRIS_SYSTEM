<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function hasVerifiedEmail(): bool
    {
        return $this->email === null || $this->email_verified_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'requires_mfa' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasMany<AttendanceImport, $this> */
    public function attendanceImports(): HasMany
    {
        return $this->hasMany(AttendanceImport::class, 'uploaded_by');
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'approved_by');
    }

    /** @return HasMany<OvertimeCtoRecord, $this> */
    public function overtimeCtoRecords(): HasMany
    {
        return $this->hasMany(OvertimeCtoRecord::class, 'approved_by');
    }

    /** @return HasMany<LeaveApplication, $this> */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class, 'approved_by');
    }

    /** @return HasMany<ApplicantEvaluation, $this> */
    public function applicantEvaluations(): HasMany
    {
        return $this->hasMany(ApplicantEvaluation::class, 'evaluator_id');
    }

    /** @return HasMany<RecruitmentSelection, $this> */
    public function recruitmentSelections(): HasMany
    {
        return $this->hasMany(RecruitmentSelection::class, 'selected_by');
    }

    /** @return HasMany<PerformanceReview, $this> */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'reviewer_id');
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** @return HasMany<LoginActivity, $this> */
    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    /** @return HasMany<Notification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /** @return HasMany<Announcement, $this> */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'posted_by');
    }
}
