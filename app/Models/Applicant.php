<?php

namespace App\Models;

use Database\Factories\ApplicantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Applicant extends Model
{
    /** @use HasFactory<ApplicantFactory> */
    use HasFactory;

    protected $table = 'applicants';

    protected $fillable = ['vacancy_id', 'first_name', 'middle_name', 'last_name', 'email', 'phone', 'application_date', 'status'];

    /** @return BelongsTo<Vacancy, $this> */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /** @return HasMany<ApplicantDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    /** @return HasMany<ApplicantEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(ApplicantEvaluation::class);
    }

    /** @return HasOne<RecruitmentSelection, $this> */
    public function selection(): HasOne
    {
        return $this->hasOne(RecruitmentSelection::class);
    }
}
