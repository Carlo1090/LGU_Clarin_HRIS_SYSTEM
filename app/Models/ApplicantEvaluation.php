<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantEvaluation extends Model
{
    protected $table = 'applicant_evaluations';

    protected $fillable = ['applicant_id', 'evaluator_id', 'rating', 'remarks', 'evaluation_date'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
