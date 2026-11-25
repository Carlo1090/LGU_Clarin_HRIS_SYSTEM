<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = ['title', 'content', 'posted_by', 'audience', 'starts_at', 'ends_at', 'is_published'];

    public const TIMEZONE = 'Asia/Manila';

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'is_published' => 'boolean',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeVisible(Builder $query): void
    {
        $now = now();
        $query->where('audience', 'all')
            ->where('is_published', true)
            ->where(fn (Builder $window) => $window->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $window) => $window->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    /** @param Builder<self> $query */
    public function scopeWithVisibleAt(Builder $query): void
    {
        $query->selectRaw('id, title, content, CASE
                WHEN starts_at > COALESCE(published_at, created_at) THEN starts_at
                ELSE COALESCE(published_at, created_at, starts_at)
            END AS visible_at')
            ->withCasts(['visible_at' => 'immutable_datetime']);
    }

    protected static function booted(): void
    {
        static::saving(function (self $announcement): void {
            if ($announcement->isDirty('is_published')) {
                $announcement->published_at = $announcement->is_published ? now() : null;
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
