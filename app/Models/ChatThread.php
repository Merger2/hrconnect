<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperChatThread
 */
class ChatThread extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_PERSONAL = 'direct';

    public const TYPE_GROUP = 'group';

    public const TYPE_PROJECT = 'project';

    protected $fillable = [
        'company_id',
        'created_by',
        'type',
        'title',
        'is_archived',
        'metadata',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        // chat_thread_user TIDAK punya kolom `role` (hanya chat_thread_id,
        // employee_id, last_read_at) — withPivot(['role', ...]) memicu
        // SQLSTATE 42703 Undefined column saat members eager-loaded.
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
