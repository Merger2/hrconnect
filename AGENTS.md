# HRConnect — Agent Instructions

Enterprise HRIS (thesis). Laravel 13 + Livewire 4 + Flux UI 2 + Tailwind CSS 4 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

## Key Commands

| Purpose | Command |
|---------|---------|
| Dev server | `composer run dev` (serve + queue + pail + vite) |
| Full test suite | `composer test` (lint + phpunit, SQLite) |
| PostgreSQL tests | `composer test:pgsql` (→ `phpunit.pgsql.xml`) |
| Focused test | `php artisan test --compact --filter=Name` |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| Lint check only | `composer lint:check` (CI-safe, no changes) |
| PHPStan | `vendor/bin/phpstan analyse` (level 5, only `app/`) |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Queue worker | `php artisan queue:work --queue=default,payroll_high,notifications` |
| Regenerate API docs | `php artisan scramble:export` (→ `docs/api/api.json`) |

## Setup & Env Quirks

- PG extensions before `migrate`: `CREATE EXTENSION IF NOT EXISTS vector; pg_trgm; pgcrypto`
- Flux auth before install: `composer config http-basic.composer.fluxui.dev "${FLUX_USERNAME}" "${FLUX_LICENSE_KEY}"`
- `.npmrc` sets `ignore-scripts=true` — `npm install` won't run build scripts
- `CIPHERSWEET_KEY` required (64-char hex). `.env.example` has a placeholder; `phpunit.xml` provides a test key.
- Default env: `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database` (encrypted), `HASH_DRIVER=argon2id`, `CACHE_STORE=database`.
- `DB_URL` must be **empty** for SQLite tests (set `""` in phpunit.xml).
- `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`.
- `pgvector/pgvector` is in `dont-discover` — registered manually via `PgvectorSchema::register()` in `AppServiceProvider`.
- `post-update-cmd` runs `boost:update` — needs `.env` present.
- Livewire v4 SFC: `make_command.emoji` set to `false` — no ⚡ prefix in filenames.
- `config/livewire.php` published (Livewire v4 defaults).

## Architecture

- **13 API controllers** at `/api/v1` (single `routes/api.php`). Sanctum bearer auth, token never expires. Public: health, login, 2fa-challenge, forgot-password.
- **15 services** in `App\Services`. Business logic lives here, not controllers.
- **8 observers** (registered manually in `AppServiceProvider`), **8 policies** (auto-discovery), **4 notifications**.
- **34 enums**: 16 Status/Indicator have `color()` (5 Flux colors); 18 Classification enums must NOT.
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee.
- **31 models**, **26 factories**, **13 route files**.
- **Web routes** loaded via `bootstrap/app.php` `then` block — reads `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php`. `routes/settings.php` is required from `web.php`.
- **Model attributes**: Laravel 13 `#[Fillable]`/`#[Hidden]` syntax.
- **+1,100 tests** / +3,600 assertions (SQLite) + **~28 PG tests** in `tests/Integration/Postgres/`.

## Design System

`DESIGN.md` is the source of truth. CSS variables come from `@theme` in `resources/css/app.css` — use `bg-canvas`, `text-ink`, `rounded-xl`, etc. Never hardcode colors/radius/fonts.
- Brand colors (pink, teal, lavender, etc.) → landing page only. HR pages use minimal accents.
- Layout: `x-layouts::app.sidebar`.
- Font: Outfit 500 (display), Inter (fallback).
- If DESIGN.md changes, update `app.css` first.

## CI

`main`/`develop`/`master` pushes + PRs trigger:
- **lint.yml**: PHP 8.4, runs `composer lint` (Pint).
- **tests.yml (sqlite)**: PHP 8.5, `npm i` (ignore-scripts), `composer install --optimize-autoloader`, `./vendor/bin/pest`.
- **tests.yml (postgres)**: PHP 8.5, `pgvector/pgvector:pg16` service, `./vendor/bin/pest --configuration=phpunit.pgsql.xml`.

## Critical Gotchas

- **`CACHE_STORE=database`** → `Cache::tags()` throws `BadMethodCallException`. Use `Cache::forget('key')`.
- **CipherSweet**: Never query encrypted columns directly. Use `whereBlind('nik', 'nik_hash', $value)`. Unique validation: `Rule::encryptedUnique(Employee::class, 'nik_hash')`.
- **PHP 8.5**: `ReflectionProperty::setAccessible()` is deprecated — just use `getProperty()` then `setValue()`/`getValue()`.
- **Defensive migration**: Guard pgvector/pg_trgm/pgcrypto with `if (DB::getDriverName() === 'pgsql')` for SQLite compat.
- **CarbonImmutable**: `Date::use(CarbonImmutable::class)` in AppServiceProvider → mutations return new instances.
- **ApprovalLevel**: compare as `$a->level === ApprovalLevel::L1_SUPERVISOR`, never `=== 1`.
- **Payroll isLocked()**: blocks PUBLISHED + PAID. Corrections via PayrollAdjustment next month, not unpublish.
- **PII split**: `GET /employees/{id}` masks NIK/phone/NPWP/bank. `GET /employees/{id}/pii` reveals (needs `manage_employees` + audit log).
- **Per-page max**: pagination `per_page` capped at 100.
- **Scramble**: `config/scramble.php` `api_path => 'api'` strips prefix from paths (shows `/v1/...`), but server URL includes `/api`. URLs resolve correctly.
