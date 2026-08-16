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
        // Features::teams() removed (2026-08-16) — HRConnect single-company HRIS,
        // team workspace di luar scope PRD (7 modul). Tanpa App\Models\Team,
        // route /teams/create 500 (Class not found). Tabel teams/team_user/team_invitations
        // dari scaffold awal dibiarkan (harmless); route team tidak lagi terdaftar.
    ],
    'profile_photo_disk' => 'public',

    // Intentionally null — adding any value here breaks Sanctum token auth by forcing
    // session-based re-auth on every SPA/XHR request alongside the cookie-based guard.
    // Routes that reference config('jetstream.auth_session') resolve to null.
    'auth_session' => null,
];
