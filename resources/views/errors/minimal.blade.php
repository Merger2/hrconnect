<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#f8fafc">
        <title>@yield('title') | {{ config('app.name') }}</title>
        <style>
            :root {
                color-scheme: light dark;
                --bg: #f6faf4;
                --surface: #ffffff;
                --border: #e2e8f0;
                --text: #0f172a;
                --text-muted: #475569;
                --accent: #57944a;
                --accent-soft: #edf7ea;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #020617;
                    --surface: #0f172a;
                    --border: #1e293b;
                    --text: #f8fafc;
                    --text-muted: #cbd5e1;
                    --accent: #9bd28d;
                    --accent-soft: rgba(155, 210, 141, 0.12);
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: max(1rem, env(safe-area-inset-top)) 1rem max(1rem, env(safe-area-inset-bottom));
                font-family: Figtree, "Segoe UI", Arial, sans-serif;
                background:
                    linear-gradient(180deg, var(--accent-soft), transparent 42%),
                    var(--bg);
                color: var(--text);
            }

            .card {
                width: min(100%, 34rem);
                border: 1px solid var(--border);
                border-radius: 2rem;
                background: var(--surface);
                padding: 1.5rem;
                box-shadow: 0 24px 64px -44px rgba(15, 23, 42, 0.65);
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1.25rem;
            }

            .mark {
                display: inline-grid;
                width: 3rem;
                height: 3rem;
                place-items: center;
                border-radius: 1.25rem;
                background: var(--accent);
                color: white;
                font-weight: 900;
            }

            .brand-name {
                margin: 0;
                color: var(--text);
                font-size: 1rem;
                font-weight: 800;
            }

            .brand-copy {
                margin: 0.125rem 0 0;
                color: var(--text-muted);
                font-size: 0.875rem;
            }

            .code {
                display: inline-flex;
                min-height: 2.5rem;
                align-items: center;
                justify-content: center;
                padding: 0.5rem 0.875rem;
                border: 1px solid color-mix(in srgb, var(--accent) 28%, transparent);
                border-radius: 999px;
                background: color-mix(in srgb, var(--accent) 10%, transparent);
                color: var(--accent);
                font-size: 0.8125rem;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            h1 {
                margin: 1rem 0 0;
                font-size: clamp(1.5rem, 7vw, 2rem);
                line-height: 1.15;
                letter-spacing: 0;
            }

            p {
                margin: 0.75rem 0 0;
                color: var(--text-muted);
                line-height: 1.7;
            }
        </style>
    </head>
    <body>
        <main class="card" aria-labelledby="minimal-error-title">
            <div class="brand" aria-label="{{ config('app.name') }}">
                <span class="mark">{{ strtoupper(mb_substr(config('app.name'), 0, 1)) }}</span>
                <div>
                    <p class="brand-name">{{ config('app.name') }}</p>
                    <p class="brand-copy">{{ __('System status') }}</p>
                </div>
            </div>
            <div class="code">@yield('code')</div>
            <h1 id="minimal-error-title">@yield('message')</h1>
            <p>{{ __('The requested page is currently unavailable. Please return to the app and try again.') }}</p>
        </main>
    </body>
</html>
