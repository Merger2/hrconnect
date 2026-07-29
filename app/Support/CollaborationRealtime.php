<?php

namespace App\Support;

class CollaborationRealtime
{
    public static function enabled(): bool
    {
        if (! (bool) config('realtime.collaboration.enabled', false)) {
            return false;
        }

        $broadcastConnections = config('realtime.collaboration.broadcast_connections', ['reverb', 'pusher', 'ably']);

        return BroadcastRuntime::enabledFor($broadcastConnections);
    }

    public static function pollInterval(): string
    {
        $interval = (string) config('realtime.collaboration.poll_interval', '30s');

        return preg_match('/^\d+(ms|s|m)$/', $interval) === 1 ? $interval : '30s';
    }
}
