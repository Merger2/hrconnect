<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperAnnouncement
 */
class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'priority',
        'created_by',
        'modal_behavior',
        'is_active',
        'published_at',
        'expired_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function views(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_views')
            ->withPivot('viewed_at')
            ->withTimestamps();
    }

    public function dismissedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_views')
            ->wherePivotNotNull('dismissed_at')
            ->withPivot('dismissed_at')
            ->withTimestamps();
    }

    public function viewedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_views')
            ->wherePivotNotNull('seen_at')
            ->withPivot('seen_at')
            ->withTimestamps();
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            });
    }

    public function scopeVisible($query)
    {
        return $query->published()->where('is_active', true);
    }

    public function scopeVisibleForUser($query, $userId)
    {
        return $query->visible()
            ->whereDoesntHave('dismissedByUsers', fn ($q) => $q->where('user_id', $userId));
    }
}
