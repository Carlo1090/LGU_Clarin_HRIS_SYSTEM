<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceCommitment extends Model
{
    protected $table = 'performance_commitments';

    protected $fillable = ['employee_id', 'evaluation_period_id', 'commitment_text', 'target_value', 'accomplishments'];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<EvaluationPeriod, $this> */
    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }
}
