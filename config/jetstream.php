<?php

use Laravel\Jetstream\Features;

return [
    'stack' => 'livewire',
    'middleware' => ['web'],
    'features' => [
        Features::profilePhotos(),
        Features::api([
            'permissions' => [
                'read' => 'Read',
                'write' => 'Write',
                'delete' => 'Delete',
            ],
        ]),
        // Features::twoFactorAuthentication() removed — Jetstream v5 removed this method.
        // 2FA is still configured via config/fortify.php using Laravel\Fortify\Features.
        Features::accountDeletion(),
        Features::teams(),
    ],
    'profile_photo_disk' => 'public',

    // Intentionally null — adding any value here breaks Sanctum token auth by forcing
    // session-based re-auth on every SPA/XHR request alongside the cookie-based guard.
    // Routes that reference config('jetstream.auth_session') resolve to null.
    'auth_session' => null,
];
