# HRConnect — Agent Instructions

Enterprise HRIS (thesis). Laravel 13 + Livewire 4 + Flux UI 2 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

## Key Commands

| Purpose | Command |
|---------|---------|
| Dev server | `composer run dev` (serve + queue + pail + vite) |
| Full test suite | `composer test` (lint + phpunit, SQLite) |
| PostgreSQL tests | `composer test:pgsql` |
| Focused test | `php artisan test --compact --filter=Name` |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Queue worker | `php artisan queue:work --queue=default,payroll_high,notifications` |
| Regenerate API docs | `php artisan scramble:export` (→ `docs/api/api.json`) |

## Setup

- PG extensions before `migrate`: `CREATE EXTENSION IF NOT EXISTS vector; pg_trgm; pgcrypto`
- Flux auth before install: `composer config http-basic.composer.fluxui.dev "${FLUX_USERNAME}" "${FLUX_LICENSE_KEY}"`
- `.npmrc` sets `ignore-scripts=true`
- `CIPHERSWEET_KEY` required (64-char hex)

## Architecture

- **51 API routes** at `/api/v1` (single `routes/api.php`, 13 controllers). Sanctum bearer auth, token never expires. Public: health, login, 2fa-challenge, forgot-password.
- **15 services** in `App\Services`. Business logic lives here, not controllers.
- **8 observers**, **8 policies** (auto-discovery), **4 live notifications**.
- **34 enums**: 16 Status/Indicator have `color()` (5 Flux colors); 18 Classification enums must NOT.
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee.
- **Web routes** auto-loaded via `bootstrap/app.php` `then` block — reads `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php`.
- **Model attributes**: Laravel 13 `#[Fillable]`/`#[Hidden]` syntax.
- **1,115 tests** / 3,697 assertions (SQLite) + **28 PG tests** / 61 assertions.
- **All 8 factories** (high-impact) now exist: Approval, CompanySetting, Device, FamilyDetail, PayrollAdjustment, PayrollItem, ShiftSchedule, KnowledgeBase.

## Current Backend Status

**Gap B-100-6 (Blade-to-API integration)** is the only remaining backend item, deferred — domain Blade views (`resources/views/attendance/`, `leaves/`, etc.) are not yet created. Everything else (P1 tasks, PG guards, FormRequest validation, permission drift, IDOR hardening) is ✅ complete.

See `docs/planning/task.md` (Remaining Gaps section) and `docs/INDEX.md`.

## Design System (DESIGN.md)

**Wajib** — `DESIGN.md` di root adalah source of truth untuk semua UI. Setiap token warna, font, spacing, radius HARUS refer ke DESIGN.md, jangan hardcode nilai sendiri.

### Aturan
- Baca DESIGN.md sebelum nulis satu baris Blade/CSS/JS.
- Setiap hex/px/value harus cocok dengan DESIGN.md YAML.
- Gunakan CSS variable dari `@theme` di `resources/css/app.css` — nilai variable sudah di-set sesuai DESIGN.md, tinggal panggil `bg-canvas`, `text-ink`, `rounded-xl`, dll.
- JANGAN hardcode warna/radius/font size di inline style atau kelas utility langsung. Kalau token tidak ada di CSS variable, cek DESIGN.md dulu — mungkin yang kurang token mapping-nya.
- Brand color (pink, teal, lavender, peach, ochre, mint/coral): landing page (welcome) boleh penuh. Halaman HR fungsional (attendance, leaves, dll.) pakai aksen minimal.
- Layout HR pages: `x-layouts::app.sidebar`.
- Font display: **Outfit** weight 500 (subtitusi Plain Black). Fallback: Inter.

Jika DESIGN.md di-update, update CSS variable di `app.css` dulu sebelum ubah Blade.

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
