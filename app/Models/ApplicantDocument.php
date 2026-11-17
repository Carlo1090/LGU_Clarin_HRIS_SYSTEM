<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantDocument extends Model
{
    protected $table = 'applicant_documents';

    protected $fillable = ['applicant_id', 'document_type', 'file_path'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
