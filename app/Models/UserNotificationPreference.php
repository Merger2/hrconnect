<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserNotificationPreference
 */
class UserNotificationPreference extends Model
{
    use HasFactory;

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_TELEGRAM = 'telegram';

    public const CHANNEL_WEBHOOK = 'webhook';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_DIGEST = 'digest';

    protected $fillable = ['user_id', 'event_key', 'channels', 'digest_enabled', 'digest_frequency', 'external_routes'];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'external_routes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function allowedChannels(): array
    {
        return [
            self::CHANNEL_IN_APP,
            self::CHANNEL_EMAIL,
            self::CHANNEL_TELEGRAM,
            self::CHANNEL_WEBHOOK,
            self::CHANNEL_WHATSAPP,
            self::CHANNEL_DIGEST,
        ];
    }
}
