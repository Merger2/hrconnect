<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BroadcastRuntime
{
    public static function enabledFor(array $connections): bool
    {
        $connection = self::connection();

        if (! in_array($connection, array_map('strtolower', $connections), true)) {
            return false;
        }

        return self::connectionIsConfigured($connection);
    }

    /**
     * @return array<string, mixed>
     */
    public static function clientConfig(?Request $request = null): array
    {
        $request ??= request();
        $connection = self::connection();
        $enabled = self::enabledFor(config('realtime.broadcast_connections', ['reverb', 'pusher', 'ably']));

        return [
            'connection' => $connection,
            'enabled' => $enabled,
            'authEndpoint' => '/broadcasting/auth',
            'csrfToken' => csrf_token(),
            'reverb' => self::connectionClientConfig('reverb', $request),
            'pusher' => self::connectionClientConfig('pusher', $request),
        ];
    }

    public static function connection(): string
    {
        return strtolower((string) config('broadcasting.default', 'null'));
    }

    private static function connectionIsConfigured(string $connection): bool
    {
        return match ($connection) {
            'reverb' => filled(config('broadcasting.connections.reverb.key')),
            'pusher' => filled(config('broadcasting.connections.pusher.key')),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function connectionClientConfig(string $connection, Request $request): array
    {
        $config = config("broadcasting.connections.{$connection}", []);
        $options = Arr::get($config, 'options', []);
        $scheme = (string) (Arr::get($options, 'scheme') ?: ($request->isSecure() ? 'https' : 'http'));

        return [
            'key' => Arr::get($config, 'key'),
            'host' => Arr::get($options, 'host') ?: $request->getHost(),
            'port' => Arr::get($options, 'port') ?: ($scheme === 'https' ? 443 : 80),
            'scheme' => $scheme,
            'path' => Arr::get($options, 'path') ?: '',
            'cluster' => Arr::get($options, 'cluster') ?: 'mt1',
        ];
    }
}
