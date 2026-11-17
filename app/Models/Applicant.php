<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Applicant extends Model
{
    use HasFactory;

    protected $table = 'applicants';

    protected $fillable = ['vacancy_id', 'first_name', 'middle_name', 'last_name', 'email', 'phone', 'application_date', 'status'];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ApplicantEvaluation::class);
    }

    public function selection(): HasOne
    {
        return $this->hasOne(RecruitmentSelection::class);
    }
}
