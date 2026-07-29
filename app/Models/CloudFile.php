<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudFile extends Model
{
    use HasFactory;

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_COMPANY = 'company';

    public const VISIBILITY_PROJECT = 'project';

    public const VISIBILITY_THREAD = 'thread';

    protected $fillable = [
        'company_id',
        'project_id',
        'chat_thread_id',
        'owner_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'visibility',
        'checksum',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
