<?php

return [

    'broadcast_connections' => ['reverb', 'pusher', 'ably'],

    'announcements' => [
        'refresh_mode' => env('ANNOUNCEMENT_REFRESH_MODE', 'auto'),
        'poll_interval' => env('ANNOUNCEMENT_POLL_INTERVAL', '60s'),
        'broadcast_connections' => ['reverb', 'pusher', 'ably'],
    ],

    'collaboration' => [
        'enabled' => env('COLLABORATION_REALTIME_ENABLED', false),
        'poll_interval' => env('COLLABORATION_REALTIME_POLL_INTERVAL', '30s'),
        'broadcast_connections' => ['reverb', 'pusher', 'ably'],
    ],

];
