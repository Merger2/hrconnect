<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperChatMessageRag
 */
class ChatMessageRag extends Model
{
    use HasFactory;

    protected $table = 'chat_messages_rag';

    protected $fillable = [
        'chat_session_id',
        'role',
        'message',
        'sources',
        'tokens_used',
        'response_time_ms',
    ];

    protected $casts = [
        'sources' => 'array',
        'tokens_used' => 'integer',
        'response_time_ms' => 'integer',
    ];

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    public function getSourcesArrayAttribute()
    {
        return $this->sources ?? [];
    }
}
