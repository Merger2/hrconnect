# HRConnect — Agent Instructions

Enterprise HRIS (thesis). Laravel 13 + Livewire 4 + Flux UI 2 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

## Key Commands

| Purpose | Command |
|---------|---------|
| Dev server | `composer run dev` (serve + queue + pail + vite) |
| Full test suite | `composer test` (lint:check + phpunit, SQLite) |
| PostgreSQL tests | `composer test:pgsql` |
| Focused test | `php artisan test --compact --filter=Name` |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Queue worker | `php artisan queue:work --queue=default,payroll_high,notifications` |

## Setup Prerequisites

- PostgreSQL extensions before `migrate`: `CREATE EXTENSION IF NOT EXISTS vector; pg_trgm; pgcrypto`
- Flux UI auth before `composer install`: `composer config http-basic.composer.fluxui.dev "${FLUX_USERNAME}" "${FLUX_LICENSE_KEY}"`
- `.npmrc` sets `ignore-scripts=true` — npm runs no postinstall
- `CIPHERSWEET_KEY` required (64-char hex), set in `.env`

## Architecture

- **51 API routes** at `/api/v1` (single `routes/api.php`, 13 controllers). Sanctum bearer auth, token never expires. Public: health, login, 2fa-challenge, forgot-password. Employee routes gated by `view_employees`/`manage_employees`. Payroll exports gated by `process_payroll`. Write endpoints throttled 5-10 req/min.
- **15 services** in `App\Services` (business logic, not in controllers)
- **7 observers** (app/Observers/), **8 policies** (auto-discovery Laravel 11+), **8 notifications**
- **34 enums**: 16 Status/Indicator have `color()` (5 Flux colors: success/warning/danger/info/zinc); 18 Classification enums must NOT have `color()`
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee
- **Model attributes**: Laravel 13 `#[Fillable]`/`#[Hidden]` syntax (not `$fillable`/`$hidden`)
- **428 tests** / 3,469 assertions. 2 CI jobs: SQLite + PostgreSQL (pgvector/pgvector:pg16)
- **Docs** in `docs/`. Source of truth: `docs/planning/task.md` (status tracker), `docs/architecture/erd.dbml` (ERD), `docs/INDEX.md` (index)

## Critical Gotchas

- **`CACHE_STORE=database`** → `Cache::tags()` throws `BadMethodCallException`. Use `Cache::forget('key')` per-key.
- **CipherSweet**: NEVER query encrypted columns directly. Use `whereBlind('nik', 'nik_hash', $value)`. Blind indexes in polymorphic `blind_indexes` table (NOT main table). Unique validation: `Rule::encryptedUnique(Employee::class, 'nik_hash')`.
- **PHP 8.5**: `ReflectionProperty::setAccessible()` is DEPRECATED — just use `getProperty()` then `setValue()`/`getValue()`.
- **Defensive migration**: Guard pgvector/pg_trgm/pgcrypto with `if (DB::getDriverName() === 'pgsql')` for SQLite test compat.
- **CarbonImmutable**: `Date::use(CarbonImmutable::class)` in AppServiceProvider → mutations return new instances.
- **ApprovalLevel**: compare as `$a->level === ApprovalLevel::L1_SUPERVISOR`, never `=== 1` (enum vs int = always false).
- **Payroll isLocked()**: blocks PUBLISHED + PAID. Corrections via PayrollAdjustment next month, not unpublish.
- **PII split**: `GET /employees/{id}` masks NIK/phone/NPWP/bank. `GET /employees/{id}/pii` reveals (needs `manage_employees` + audit log).
- **Per-page max**: pagination `per_page` capped at 100.

## 3 Core Thesis Features

1. **Face Recognition** — face-api.js (FaceNet 128D) → pgvector `<=>` cosine distance, threshold 0.15. Fallback: Face → PIN → Manual.
2. **GPS Geofencing** — Haversine in `GeofenceService`. Block if `is_mocked` or `accuracy > 100m`. WFA skips GPS (note ≥20 chars + approval).
3. **KnowledgeBase RAG** — polymorphic `knowledgeable`, `embedding vector(768)` (Gemini text-embedding-004), Q&A via Gemini 2.5 Flash. Fallback to `pg_trgm`.

## Deferred to V2

Loan/Kasbon (tables exist, service incomplete), Asset Management, Performance Review, WhatsApp Notifications.
