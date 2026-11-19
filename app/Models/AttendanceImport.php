<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImport extends Model
{
    protected $table = 'attendance_imports';

    protected $fillable = ['file_name', 'upload_batch', 'uploaded_by', 'status', 'imported_at', 'file_sha256', 'total_lines', 'inserted_count', 'duplicate_count', 'invalid_count', 'clock_offset_seconds', 'invalid_lines'];

    protected function casts(): array
    {
        return ['invalid_lines' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
