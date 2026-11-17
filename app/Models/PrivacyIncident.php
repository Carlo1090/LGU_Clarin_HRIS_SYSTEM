<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyIncident extends Model
{
    protected $table = 'privacy_incidents';

    protected $fillable = ['employee_id', 'incident_type', 'details', 'severity', 'reported_at', 'resolved_at'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
