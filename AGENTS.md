# HRConnect — Agent Instructions

Enterprise HRIS (thesis). Laravel 13 + Livewire 4 + Tailwind CSS 4 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

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
- Design system: Material Design 3 (custom Tailwind, no Flux). Palette in `resources/css/app.css` `@theme`.
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
- **34 enums**: 16 Status/Indicator have `color()` (MD3 semantic: success/warning/error/info); 18 Classification enums must NOT.
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee.
- **31 models**, **26 factories**, **13 route files**.
- **Web routes** loaded via `bootstrap/app.php` `then` block — reads `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php`. `routes/settings.php` is required from `web.php`.
- **Model attributes**: Laravel 13 `#[Fillable]`/`#[Hidden]` syntax.
- **+1,100 tests** / +3,600 assertions (SQLite) + **~28 PG tests** in `tests/Integration/Postgres/`.

## Reference Repos (Cloned — Do Not Delete)

Two repos serve as pattern source-of-truth for business logic. **Only adopt UX/component patterns, never copy CSS/colors.**

### PasPapan — Primary Reference (Face, GPS, Risk Scoring, Approval, Termination)
- Path: `/home/merger/PasPapan/`
- 79 models, 22 services, 58 Blade components, 1,642 lines app.js, 6,623 lines CSS

| Pattern | File | Port To |
|:--------|:-----|:--------|
| Attendance risk scoring (14 faktor, score 0-100) | `app/Support/AttendanceRiskScorer.php` | HRConnect GeofenceService + FaceService |
| Anti-replay QR (HMAC-SHA256 + nonce + TTL jitter) | `app/Support/DynamicBarcodeTokenService.php` | HRConnect clock-in QR |
| Device attendance lock (`lockForUpdate()` + radius) | `app/Services/Attendance/DeviceAttendanceService.php` | HRConnect AttendanceController |
| Approval lock (`lock()` + `ensureReviewable()`) | `app/Support/ReimbursementApprovalService.php` | HRConnect ReimbursementService |
| Lifecycle termination (not direct status change) | `app/Support/EmployeeLifecycleService.php` | HRConnect EmployeeTerminationService |
| Offboarding checklist (4-task + dependency chain) | `app/Support/HrChecklistService.php` | HRConnect termination flow |
| Face enrollment overlay (992 lines canvas guide) | `app/Livewire/User/FaceEnrollment.php` + view | HRConnect face-registration.blade.php |
| Face verification scan (1,100+ lines Alpine) | `resources/views/livewire/user/scan.blade.php` | HRConnect clock-in.blade.php |
| Face model (separate table for embeddings) | `app/Models/FaceDescriptor.php` | HRConnect FaceDescriptor migration |
| JS utilities (SweetAlert2, Flatpickr, validation) | `resources/js/app.js` (1,642 lines) | HRConnect `resources/js/face-utils.js` |

### Quanta HRIS — Indonesian Payroll (Post-Skripsi)
- Path: `/home/merger/quanta-hris-laravel/`
- 54 views, Filament 3, 10 services payroll Indonesia

| Service | File | Logic |
|:--------|:-----|:------|
| Pph21Service | `app/Services/Pph21Service.php` | TER PMK 168/2023, lookup by bruto bracket |
| BpjsService | `app/Services/BpjsService.php` | Kesehatan 1%, JHT 2%, JP 1% + batas atas |
| LemburService | `app/Services/LemburService.php` | PP 35/2021, formula (gaji+tunjangan)/173 |
| PotonganService | `app/Services/PotonganService.php` | Alfa (per hari), terlambat (3 metode) |
| TunjanganService | `app/Services/TunjanganService.php` | 3 jenis tunjangan + 75% rule compliance |
| HitungGajiService | `app/Services/HitungGajiService.php` | Orchestrator semua komponen |
| Migrations + Seeders | `database/migrations/` | 3 tabel pajak (kategori_ter, golongan_ptkp, tarif_ter) |

### Other Repos — Low Priority
- `/home/merger/laravel-smarthr/` — UI component reference (141 views, 5 Livewire, 0 approval workflow)
- `/home/merger/hrms-livewire/` — Queue progress bar pattern (109 views, 22 Livewire, 0 services)
- `/home/merger/hris/` — Org structure hierarchy (React, no HRIS features)

## Business Logic Gotchas (from Audit)

- **P0-1**: `processContractEnd()` has NO authorization gate — add `Gate::authorize()` before execution
- **P0-2**: `GeofenceService.php:40` — null `branch.radius` casts to 0 (all locations pass). Guard + throw.
- **P0-3**: `AttendanceController.php:220` — WFA with null employee bypasses geofence. Guard return error.
- **P0-4**: `EmployeeController@update` (149-164) allows direct status to resigned/terminated. Force via `EmployeeLifecycleService`.
- **P1-5**: `ReimbursementService.php:93-99` — TOCTOU race: `isApproved()` before `lockForUpdate()`. Use PasPapan `lock()`+`ensureReviewable()`.
- **P1-6**: `PayrollCalculatorService.php:431` — only checks PUBLISHED, not PAID. Add `PayrollStatus::PAID`.
- **P1-7**: `StoreOvertimeRequest.php:29` — missing `after_or_equal:today`. Add rule.
- **Clock-out PIN**: `AttendanceService.php` — PIN bypass streak check. Fix: require PIN on every clock-out.
- **Cannot use `Cache::tags()`**: `CACHE_STORE=database` throws `BadMethodCallException`. Use `Cache::forget('key')`.

## Design System

`DESIGN.md` is the source of truth for UI. CSS variables come from `@theme` in `resources/css/app.css` — use `bg-canvas`, `text-ink`, `rounded-xl`, etc. Never hardcode colors/radius/fonts.
- **CRITICAL: UI follows DESIGN.md DS-1 (canvas #ffffff, body #3a3a3a, Inter font, neutral palette). Do NOT copy PasPapan CSS (green #57944a, cream #fffaf0).**
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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `ai-sdk-development` — TRIGGER when working with ai-sdk which is Laravel official first-party AI SDK. Activate when building, editing AI agents, chatbots, text generation, image generation, audio/TTS, transcription/STT, embeddings, RAG, vector stores, reranking, structured output, streaming, conversation memory, tools, queueing, broadcasting, and provider failover across OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, ElevenLabs, Cohere, Jina, and VoyageAI. Invoke when the user references ai-sdk, the `Laravel\Ai\` namespace, or this project's AI features — not for other AI packages used directly.
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
