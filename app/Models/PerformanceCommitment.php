<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceCommitment extends Model
{
    protected $table = 'performance_commitments';

    protected $fillable = ['employee_id', 'evaluation_period_id', 'commitment_text', 'target_value', 'accomplishments'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }
}
