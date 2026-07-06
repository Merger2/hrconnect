# HRConnect — Agent Guide

Enterprise HRIS (thesis project). Laravel 13 + Livewire 4 + Tailwind CSS 4 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

## Instruction files (read first)

| File | Contents |
|------|----------|
| `CLAUDE.md` / `GEMINI.md` | Laravel Boost guidelines — skills activation, Boost MCP tools, search-docs, coding conventions, lint auto-fix |
| `DESIGN.md` | MD3 design tokens, app vs landing theme split, utility classes |
| `docs/planning/task.md` | Outstanding work, audit findings, UX porting plan |

## OpenCode configuration

- `opencode.json` — MCP `laravel-boost` auto-started via `php artisan boost:mcp`. No manual setup. The `provider` block holds a Chenzk API base + a placeholder `apiKey` (`YOUR_CHENZK_API_KEY_HERE`); fill it before use.
- Boost MCP tools available: `database-query`, `database-schema`, `search-docs`, `browser-logs`, `get-absolute-url`, `last-error`, `read-log-entries`.

## Skills (`.agents/skills/`)

Auto-discovered by OpenCode. Activate via the `skill` tool by domain: `ai-sdk-development`, `fortify-development`, `laravel-best-practices`, `livewire-development`, `pest-testing`, `tailwindcss-development`.

## Development priorities

| Priority | Role | Scope |
|----------|------|-------|
| **P1** | **HR-Manager** | Payroll config, master data, employee lifecycle, RAG docs, reports, company settings |
| **P2** | **Finance** | Payroll execution, reimbursement payment, loan, asset, tax reports |
| **P3** | **Manager** | Approval L1, team monitoring |
| **P4** | **Employee** | Clock-in/out, apply leave/overtime/reimbursement, view payslip, RAG chat |

Account creation and all master data live in HR-Manager + Finance — never employee-facing.

**UI reference (mandatory):** Every Blade/Livewire component must reference the cloned repos at `/home/merger/`. Don't build from scratch — adopt patterns from PasPapan (components, layout, Alpine) then convert to Tailwind CSS 4 + Material Symbols + HRConnect MD3 tokens. See `docs/planning/task.md` §UX Porting Plan per-component.

## Status

- Core stack complete: 50 migrations, all core entities, ~80 API endpoints, Livewire 4 SFC + Alpine.js + PWA, Pest tests (SQLite default + PostgreSQL alternate).
- For outstanding items see `docs/planning/task.md`.

## Key commands

| Purpose | Command |
|---------|---------|
| Dev server | `composer run dev` (serve + queue + pail + vite concurrently) |
| Full test | `composer test` (config:clear + lint:check + tests) |
| Single test | `php artisan test --compact --filter=Name` |
| PostgreSQL test | `composer test:pgsql` (→ `phpunit.pgsql.xml`) |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| Lint check only | `composer lint:check` (CI-safe, dry-run) |
| PHPStan | `vendor/bin/phpstan analyse` (level 5, only `app/`) |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Regenerate API docs | `php artisan scramble:export` (→ `docs/api/api.json`) |
| Queue worker | `php artisan queue:work --queue=default,payroll_high,notifications` |

`composer test` runs `config:clear` + `lint:check` first; for fast feedback use `php artisan test --compact --filter=X`.
Pint `--format agent` is the AI-optimized output — always pass it when auto-fixing.

## Setup & environment

- PG extensions required before migrate: `vector`, `pg_trgm`, `pgcrypto`. Guard with `if (DB::getDriverName() === 'pgsql')` for SQLite compatibility.
- `CIPHERSWEET_KEY` mandatory (64-char hex). `.env.example` has a placeholder; `phpunit.xml` has a test key.
- `DB_URL` must be **empty** for SQLite tests.
- `.npmrc` sets `ignore-scripts=true` — `npm install` skips build scripts. Run `npm run build` manually.
- `pgvector/pgvector` is in `dont-discover` — register manually via `PgvectorSchema::register()` in `AppServiceProvider`.
- `post-update-cmd` runs `boost:update` — requires `.env` to exist.
- Livewire v4 SFC: `make_command.emoji` set to `false`.

## Architecture

- **20 API controllers** in `app/Http/Controllers/Api/` (URL prefix `/api/v1` is set via `apiPrefix: 'api/v1'` in `bootstrap/app.php`). Sanctum bearer auth, never expires. Public endpoints: health, login, 2fa-challenge, forgot-password.
- **24 services** in `App\Services` — business logic lives here, not in controllers. Payroll specifics: `BpjsService`, `LemburService`, `PotonganService`, `Pph21Service` (under `App\Services\Payroll`).
- **34 models, 31 factories, 50 migrations, 14 route files**.
- **33 enums** — Status/Indicator enums may expose `color()` (MD3 semantic); check an enum before assuming or adding one. Classification enums must not.
- **8 observers** (registered manually in `AppServiceProvider::registerObservers()`), **14 policies** (auto-discovery), **7 notification classes** (the `Concerns/` subdir is shared trait code, not a class).
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee.
- **Web routes**: `bootstrap/app.php` `then` block loads `routes/{attendance,employee,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement,master-data}.php` as `web` middleware groups. `routes/web.php` only contains `/` redirect, `dashboard`, and `require __DIR__.'/settings.php'`.
- **AppServiceProvider** also wires: `Date::use(CarbonImmutable::class)`, `DB::prohibitDestructiveCommands()` in production, `Password::defaults()` (12+ mixed in prod), and the `api` rate limiter (60/min by user id or IP).
- **Model attributes**: Laravel 13 `#[Fillable]` / `#[Hidden]` syntax.

## Frontend & JS

- Vite entry: `resources/css/app.css` + `resources/js/app.js`. Tailwind CSS 4 via `@tailwindcss/vite`.
- **18 `Alpine.data` registrations** in `app.js` inside the `alpine:init` listener (lines 192–210), plus `tomSelectInput` registered later. Important for `wire:navigate` compatibility — register any new Alpine component there, not inline.
- **Toast event**: `$this->dispatch('toast', variant: 'success', text: '...')` — JS listener is `Livewire.on('toast', ...)` at `app.js:249`, **not** `notify`.
- JS utilities: `window.HRConnectAlert` (toast/confirm/modal via SweetAlert2), `window.L` (Leaflet), `flatpickr`, `TomSelect`.
- `npm run build` for production assets.

## Design system

**Source of truth:** `DESIGN.md`. CSS variables come from `@theme` in `resources/css/app.css`.

- Don't hardcode colors/radius/fonts — use utilities: `bg-canvas`, `text-ink`, `rounded-xl`, etc.
- Fonts: Rubik 500 (display), Inter (body).
- Brand colors (pink, teal, lavender) → landing page ONLY. HR pages use neutrals.
- Layout: `x-layouts::app.sidebar`.
- Theme split is **final**: app (canvas `#ffffff`) vs landing (`.landing-theme` → `#fffaf0`).

## Critical gotchas

- **`CACHE_STORE=database`** → `Cache::tags()` throws `BadMethodCallException`. Use `Cache::forget('key')`.
- **CipherSweet**: Don't query encrypted columns directly. Use `whereBlind('nik', 'nik_hash', $value)`. Unique rule: `Rule::encryptedUnique(Employee::class, 'nik_hash')`.
- **PHP 8.5**: `ReflectionProperty::setAccessible()` deprecated — call `getProperty()` then `setValue()`/`getValue()` directly.
- **CarbonImmutable**: `Date::use(CarbonImmutable::class)` in `AppServiceProvider` → mutations return new instances.
- **ApprovalLevel**: compare as `$a->level === ApprovalLevel::L1_SUPERVISOR`, never `=== 1`.
- **Payroll isLocked()**: blocks PUBLISHED + PAID. Correct via `PayrollAdjustment` next month.
- **PII split**: `GET /employees/{id}` masks NIK/phone/NPWP/bank. `GET /employees/{id}/pii` reveals (requires `manage_employees` + audit log).
- **Per-page max**: `per_page` capped at 100.
- **Scramble**: `api_path => 'api'` strips the prefix (displays `/v1/...`) but server URL includes `/api`. URLs resolve correctly.
- **`RAG_MOCK_MODE`** — **effectively dead.** Declared in `config/services.php` (`gemini.mock_mode`) and `config/hrconnect.php` (`rag_mock_mode`) from `RAG_MOCK_MODE` env, but no `app/` or `tests/` code reads those config keys. Gemini is called for real regardless of this flag.
- **pg_trgm fallback** only in sync `chat()`, not in streaming.

## Reference repos (cloned — don't delete)

Pattern source of truth. **Adopt UX/component patterns only — don't copy CSS/colors.**

| Repo | Path | Use for |
|------|------|--------|
| **PasPapan** | `/home/merger/PasPapan/` | Face scan, GPS, risk scoring, approval, termination, UI components |
| **Quanta HRIS** | `/home/merger/quanta-hris-laravel/` | Indonesian payroll (PPh21, BPJS, overtime, deductions) |
| **ship-ai-with-laravel** | `/home/merger/RAG-repo/ship-ai-with-laravel/` | RAG chat SSE streaming pattern |
| Others | `/home/merger/laravel-smarthr/`, `/home/merger/hrms-livewire/`, etc. | Lower priority |

## CI

`develop`/`main`/`master`/`workos` push + PR trigger:
- **lint.yml**: PHP 8.4, `composer lint` (Pint). Auto-commit step commented out.
- **tests.yml (sqlite)**: PHP 8.5, `composer install --optimize-autoloader`, `./vendor/bin/pest`.
- **tests.yml (postgres)**: PHP 8.5, `pgvector/pgvector:pg16` service, `./vendor/bin/pest --configuration=phpunit.pgsql.xml`.

## RAG knowledge base

- **Backend**: `KnowledgeBaseService` — embed via Gemini `text-embedding-004` (768D) → pgvector cosine top-5 → `HrKnowledgeBaseAgent` (Gemini 2.5 Flash, structured output).
- **Sync API**: `POST /api/v1/knowledgebase/chat` → JSON `{answer, sources, confidence}`.
- **Streaming API**: `POST /api/v1/knowledgebase/chat-stream` → SSE `text/event-stream`.
- **Conversation memory**: `conversationId` is sent back but **not persisted** to DB. Every chat = fresh context.