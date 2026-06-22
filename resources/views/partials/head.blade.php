<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/icon-512.svg">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1a3a3a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
<link href="https://fonts.bunny.net/css?family=outfit:500&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
