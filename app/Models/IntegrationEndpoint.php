<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationEndpoint extends Model
{
    use HasFactory, HasUlids;

    public const PROVIDER_CUSTOM = 'custom';

    public const PROVIDER_ACCOUNTING = 'accounting';

    public const PROVIDER_PAYROLL = 'payroll';

    public const PROVIDER_SLACK = 'slack';

    public const PROVIDER_TELEGRAM = 'telegram';

    public const PROVIDER_WHATSAPP = 'whatsapp';

    public const PROVIDER_GOOGLE_CALENDAR = 'google_calendar';

    public const PROVIDER_SSO = 'sso';

    protected $fillable = [
        'client_id',
        'name',
        'event_keys',
        'url',
        'secret',
        'headers',
        'active',
    ];

    protected $casts = [
        'event_keys' => 'array',
        'headers' => 'array',
        'secret' => 'encrypted',
        'active' => 'boolean',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(IntegrationDelivery::class);
    }
}
