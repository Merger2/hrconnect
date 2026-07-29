<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class IntegrationClient extends Model
{
    use HasFactory, HasUlids;

    public const ABILITY_ATTENDANCE_READ = 'integration:attendance.read';

    public const ABILITY_ATTENDANCE_WRITE = 'integration:attendance.write';

    public const ABILITY_EMPLOYEES_READ = 'integration:employees.read';

    public const ABILITY_SCHEDULES_READ = 'integration:schedules.read';

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'api_key_hash',
        'secret_encrypted',
        'abilities',
        'allowed_sources',
        'allowed_ips',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'revoked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'allowed_sources' => 'array',
            'allowed_ips' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: self, 1: string, 2: string}
     */
    public static function issue(array $attributes): array
    {
        [$apiKey, $secret] = self::generateCredentials();

        $client = self::query()->create([
            ...$attributes,
            'api_key_hash' => self::hashApiKey($apiKey),
            'secret_encrypted' => Crypt::encryptString($secret),
            'abilities' => array_values($attributes['abilities'] ?? []),
        ]);

        return [$client, $apiKey, $secret];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function generateCredentials(): array
    {
        return [
            'ppk_'.Str::random(48),
            'pps_'.Str::random(64),
        ];
    }

    public static function hashApiKey(string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }

    public function rotateCredentials(): array
    {
        [$apiKey, $secret] = self::generateCredentials();

        $this->forceFill([
            'api_key_hash' => self::hashApiKey($apiKey),
            'secret_encrypted' => Crypt::encryptString($secret),
            'last_used_at' => null,
            'last_used_ip' => null,
            'revoked_at' => null,
        ])->save();

        return [$apiKey, $secret];
    }

    public function secret(): string
    {
        return Crypt::decryptString($this->secret_encrypted);
    }

    public function isUsable(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function allowsSource(?string $source): bool
    {
        $sources = array_values(array_filter($this->allowed_sources ?? []));

        return $sources === []
            || ($source !== null && in_array($source, $sources, true));
    }

    public function allowsIp(?string $ip): bool
    {
        $ips = array_values(array_filter($this->allowed_ips ?? []));

        return $ips === []
            || ($ip !== null && in_array($ip, $ips, true));
    }

    public function markUsed(?string $ip): void
    {
        $this->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ])->save();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
