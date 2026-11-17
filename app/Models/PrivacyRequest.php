<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyRequest extends Model
{
    protected $table = 'privacy_requests';

    protected $fillable = ['employee_id', 'request_type', 'status', 'requested_at', 'processed_at'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
