<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    protected $table = 'performance_reviews';

    protected $fillable = ['employee_id', 'evaluation_period_id', 'reviewer_id', 'final_rating', 'remarks', 'review_date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
