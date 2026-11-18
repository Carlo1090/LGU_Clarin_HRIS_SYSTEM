<?php

namespace App\Models;

use Database\Factories\EvaluationPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    /** @use HasFactory<EvaluationPeriodFactory> */
    use HasFactory;

    protected $table = 'evaluation_periods';

    protected $fillable = ['name', 'start_date', 'end_date', 'status'];

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
}
