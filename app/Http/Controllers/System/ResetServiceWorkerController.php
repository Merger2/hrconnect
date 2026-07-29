<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ResetServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        return response(<<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Reset App Cache</title>
</head>
<body>
    <p>Resetting app cache...</p>
    <script>
        (async () => {
            try {
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(registrations.map((registration) => registration.unregister()));
                }

                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
                }
            } finally {
                window.location.replace('/login?sw-reset=done');
            }
        })();
    </script>
</body>
</html>
HTML)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Clear-Site-Data', '"cache", "storage"');
    }
}
