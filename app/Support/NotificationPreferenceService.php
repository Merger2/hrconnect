<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class NotificationPreferenceService
{
    /**
     * @param  array<int, string>  $channels
     * @param  array<string, string>  $externalRoutes
     */
    public function setPreference(
        User $user,
        string $eventKey,
        array $channels,
        bool $digestEnabled = false,
        ?string $digestFrequency = null,
        array $externalRoutes = [],
    ): UserNotificationPreference {
        $channels = $this->normalizeChannels($channels);

        return UserNotificationPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'event_key' => $eventKey,
            ],
            [
                'channels' => $channels,
                'digest_enabled' => $digestEnabled || in_array(UserNotificationPreference::CHANNEL_DIGEST, $channels, true),
                'digest_frequency' => $digestFrequency,
                'external_routes' => $this->normalizeExternalRoutes($externalRoutes),
            ],
        );
    }

    /**
     * @param  array<int, string>  $defaultChannels
     * @return array<int, string>
     */
    public function channelsFor(User $user, string $eventKey, array $defaultChannels = [UserNotificationPreference::CHANNEL_IN_APP]): array
    {
        $preference = $this->preferenceFor($user, $eventKey);

        return $preference
            ? $this->normalizeChannels((array) $preference->channels)
            : $this->normalizeChannels($defaultChannels);
    }

    /**
     * @param  array<int, string>  $defaultChannels
     * @return array<int, string>
     */
    public function laravelChannelsFor(User $user, string $eventKey, array $defaultChannels = ['database']): array
    {
        return collect($this->channelsFor($user, $eventKey, $this->fromLaravelChannels($defaultChannels)))
            ->flatMap(fn (string $channel): array => match ($channel) {
                UserNotificationPreference::CHANNEL_IN_APP => ['database'],
                UserNotificationPreference::CHANNEL_EMAIL => ['mail'],
                default => [],
            })
            ->unique()
            ->values()
            ->all();
    }

    public function prefers(User $user, string $eventKey, string $channel): bool
    {
        return in_array($channel, $this->channelsFor($user, $eventKey), true);
    }

    /**
     * @return array<string, string>
     */
    public function externalRoutesFor(User $user, string $eventKey): array
    {
        $preference = $this->preferenceFor($user, $eventKey);

        return (array) ($preference?->external_routes ?? []);
    }

    public function preferenceFor(User $user, string $eventKey): ?UserNotificationPreference
    {
        return $user->notificationPreferences()
            ->where('event_key', $eventKey)
            ->first();
    }

    /**
     * @return Collection<int, UserNotificationPreference>
     */
    public function digestRecipients(string $eventKey, string $frequency): Collection
    {
        return UserNotificationPreference::query()
            ->with('user')
            ->where('event_key', $eventKey)
            ->where('digest_enabled', true)
            ->where('digest_frequency', $frequency)
            ->get();
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function normalizeChannels(array $channels): array
    {
        $allowed = UserNotificationPreference::allowedChannels();
        $normalized = collect($channels)
            ->map(fn (string $channel): string => match ($channel) {
                'database' => UserNotificationPreference::CHANNEL_IN_APP,
                'mail' => UserNotificationPreference::CHANNEL_EMAIL,
                default => $channel,
            })
            ->unique()
            ->values();

        $unsupported = $normalized->diff($allowed)->values();

        if ($unsupported->isNotEmpty()) {
            throw new InvalidArgumentException('Unsupported notification channels: '.$unsupported->implode(', '));
        }

        return $normalized->all();
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function fromLaravelChannels(array $channels): array
    {
        return collect($channels)
            ->map(fn (string $channel): string => match ($channel) {
                'database' => UserNotificationPreference::CHANNEL_IN_APP,
                'mail' => UserNotificationPreference::CHANNEL_EMAIL,
                default => $channel,
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $routes
     * @return array<string, string>
     */
    protected function normalizeExternalRoutes(array $routes): array
    {
        return collect($routes)
            ->only([
                UserNotificationPreference::CHANNEL_WHATSAPP,
                UserNotificationPreference::CHANNEL_TELEGRAM,
                UserNotificationPreference::CHANNEL_WEBHOOK,
            ])
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => trim((string) $value))
            ->all();
    }
}
