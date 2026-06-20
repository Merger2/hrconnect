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
- **585 tests** / 3,836 assertions across 58 files. 2 CI jobs: SQLite + PostgreSQL (pgvector/pgvector:pg16). Comprehensive gap audit completed — see P0-6 in `docs/planning/task.md`. ProfileService tests added (T-15), Overtime/Reimbursement store policy calls added (T-23/24), Command tests added (P1-15).
- **Docs** in `docs/`. Source of truth: `docs/planning/task.md` (status tracker), `docs/architecture/erd.dbml` (ERD), `docs/INDEX.md` (index)

## Known Test Coverage Gaps (from P0-6 three-round audit)

### Zero Coverage (now 7 items after recent work)
- **Pinecone search**: stub returns `[]`, no tests
- **Jobs**: 9 tests cover basic dispatch only; no failed/retry/log edge cases
- **Events/mail/Listeners**: no `app/Events/`, `app/Listeners/`, `app/Mail/` dirs exist
- **Blade-to-API integration**: zero tests
- **Web routes** (Fortify auth, dashboard, settings): 25+ GET routes, 0 tests
- **Middleware `DeviceDetection`**: UA-parsing middleware, 0 tests
- **Middleware `GeofenceValidation`**: middleware-layer, 0 tests

### Thin Coverage (<10 assertions)
- PayslipPdfService (4), PayrollExportService (6), EmployeeTerminationService (6), OvertimeService unit (3), EmbeddingService (6), FaceRecognitionService (6), GeminiClient (7), PayrollCalculatorService scattered

### FormRequest Validation Gaps (6/28)
- `UpdateProfileRequest`, `ListAttendanceRequest`, `ListLeaveRequest`, `ListOvertimeRequest`, `ListPayrollRequest`, `ListReimbursementRequest` — validation rules never tested

### Policy Gaps (resolved — 8/8 now have direct tests)
- ✅ `AttendancePolicy`, `OvertimePolicy` — 15 boundary tests added (view self/other/team, create, update/delete status gates, approveLevel1/2)

### Permission Drift
- `MANAGE_REIMBURSEMENTS` in enum but unassigned to any role

### Factory Gaps (12 missing)
- High-impact: `Approval`, `CompanySetting`, `Device`, `FamilyDetail`, `PayrollAdjustment`, `PayrollItem`, `ShiftSchedule`
- Low-impact (V2): `Asset`, `AssetHandover`, `Loan`, `LoanInstallment`, `PerformanceReview`

### IDOR Gaps (from S-2 audit)
- **Employee API** — no team scoping in API endpoints (manager can list ALL employees, not just direct reports). Policy: "team scoping via Livewire query scope, not policy concern"
- **Overtime/Reimbursement store** — `OvertimePolicy::create()` / `ReimbursementPolicy::create()` exist but are never called in controllers (employee_id from auth, so not exploitable)
- **`viewAny` policies** — all permission-only; real ownership scoping lives in controller query builders (defense-in-depth gap)
- **`approveWfa`** — uses string permission `approve_wfa` instead of `$this->authorize()` (inconsistent, but hierarchy check still prevents IDOR)
- **Payroll generate** — accepts `employee_ids[]` with no relationship-to-user verification (gated by `process_payroll`)
- **AssetPolicy** — V2-deferred, no handover-based filtering

### Infrastructure
- Livewire: 1 component (Logout.php) — no test
- Blade: 38 view files — no assertions on rendered content
- CI: PostgreSQL job aktif di `.github/workflows/tests.yml` (pgvector/pgvector:pg16, extensions dibuat via psql). `composer test:pgsql` = 19 tests/43 assertions.

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
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `ai-sdk-development` — TRIGGER when working with ai-sdk which is Laravel official first-party AI SDK. Activate when building, editing AI agents, chatbots, text generation, image generation, audio/TTS, transcription/STT, embeddings, RAG, vector stores, reranking, structured output, streaming, conversation memory, tools, queueing, broadcasting, and provider failover across OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, ElevenLabs, Cohere, Jina, and VoyageAI. Invoke when the user references ai-sdk, the `Laravel\Ai\` namespace, or this project's AI features — not for other AI packages used directly.
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
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
