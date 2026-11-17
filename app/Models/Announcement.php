<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = ['title', 'content', 'posted_by', 'audience', 'starts_at', 'ends_at', 'is_published'];

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
