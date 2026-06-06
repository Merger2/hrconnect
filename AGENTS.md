# HRConnect — Agent Instructions

Enterprise HRIS (thesis project). Laravel 13 + Livewire 4 + Flux UI 2 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo developer.

## Project Documentation

All plans live in `docs/`. Read `docs/INDEX.md` for the full index. Source-of-truth files:
- `docs/PRD.md` — PRD v3.1 (35 koreksi konsolidasi: K1-K5 + M1-M8 + S1-S18 + N1-N12 + Glossary)
- `docs/PRD-errata.md` — HISTORICAL ONLY
- `docs/planning/task.md` — Executable spec v4.2 (per-item status: ✅/⚠️/❌)
- `docs/architecture/erd.dbml` — DB schema source of truth
- `docs/security/error-handling-strategy.md` — Fallback tiers, exception HTTP codes
- `docs/security/caching-strategy.md` — Cache keys + TTL plan (NOT fully implemented yet)
- `docs/security/security-config.md` — CipherSweet, 2FA, RBAC, password expiry
- `docs/testing/testing-strategy.md` — §9 Database Testing Strategy

When PRD/docs conflict with code, **trust ERD + migrations + task.md status table first**.

## Setup & Dev

- **PostgreSQL extensions** required before `migrate`:
  ```sql
  CREATE EXTENSION IF NOT EXISTS vector;
  CREATE EXTENSION IF NOT EXISTS pg_trgm;
  CREATE EXTENSION IF NOT EXISTS pgcrypto;
  ```
- **Flux UI auth** required before `composer install`:
  ```bash
  composer config http-basic.composer.fluxui.dev "${FLUX_USERNAME}" "${FLUX_LICENSE_KEY}"
  ```
- Dev server: `composer run dev` (serve + queue + pail + vite concurrently)
- Lint: `vendor/bin/pint --dirty --format agent`
- Queue worker: `php artisan queue:work --queue=default,payroll_high,notifications`
- `.npmrc` sets `ignore-scripts=true` — npm runs no postinstall scripts
- `.env.example` and `config/database.php` default to `pgsql` — do **not** revert to `sqlite`

## Folder Status (as of 2026-05-31)

Update progress per folder. Sebelumnya banyak yang kosong — sebagian sudah populated:

- `app/Observers/` — ✅ **5 observers** (Hari 2): TaxConfig, BpjsConfig, Holiday, Employee, Attendance. Registered di `AppServiceProvider::boot()`.
- `app/Policies/` — ✅ **8 policies** (Sesi 3): Employee, Attendance, Leave, Overtime, Reimbursement, Payroll, KnowledgeBase, Asset. Auto-discovery Laravel 11+. IDOR fix.
- `app/Http/Middleware/` — ✅ **CheckPasswordExpired** (Sesi 5, alias `password.expired`). `GoogleOAuthRefresh` belum dibuat (V2).
- `app/Console/Commands/` — ✅ **5 commands** (Sesi 6): `attendance:detect-alpha`, `attendance:detect-chronic-late`, `leave:reset-quota`, `payroll:generate`, `cache:warm`. Schedule registered di `routes/console.php`.
- `app/Notifications/` — ❌ **DOES NOT EXIST**. Folder must be created before adding notification classes.
- `routes/api.php` — ✅ Bootstrap dengan `/api/v1/user` endpoint (Sesi 2). Sanctum installed, `HasApiTokens` di User. Module routes (attendance, leave, dll) belum (Sprint 30).
- `routes/web.php` — exists. Dashboard pakai middleware `password.expired`. Module routes (HRD/Finance/Admin) belum.
- `app/Enums/Permission.php` — ✅ **44 cases** (Sesi 1). Pair dengan `RoleAndPermissionSeeder` (5 roles) + `SuperAdminSeeder` (env-driven).

## Architecture

- Service layer in `App\Services\*` — business logic here, not in controllers.
- **33 enums** in `App\Enums\` (PHP 8.1 backed enums).
  - **16 enums HAVE `color()`** (Status/Indicator): ApprovalStatus, AssetStatus, AttendanceStatus, EmployeeStatus, EmploymentType, KnowledgeBaseStatus, LoanInstallmentStatus, LoanStatus, MaritalStatus, PayrollItemType, PayrollStatus, ReimbursementStatus, RequestStatus, TerCategory, TerminationType, WfaStatus.
  - **17 enums DO NOT have `color()`** (Classification/Data): ApprovalLevel, BloodType, BpjsType, CompanySettingType, DayType, DeviceType, EducationLevel, FamilyRelationship, Gender, HandoverCategory, KnowledgeBaseCategory, LeaveQuotaReset, NotificationType, ResignationReason, SalaryType, ShiftScheduleType, VerificationMethod.
  - Adding `color()` to a Classification enum is **wrong** (visual noise / "pasar malam" effect). See task.md §2.19.
  - Color palette is **5 Flux UI semantic names only**: `success`, `warning`, `danger`, `info`, `zinc`. **No** `violet`, no raw `green`/`red`/`amber`/`blue`/`primary`/`slate`/`neutral`.
  - `DayType` is Classification (no color); use `weight()` method, not `getQuotaDeduction()`.
- Models use Laravel 13 attribute syntax: `#[Fillable([...])]` + `#[Hidden([...])]`, **not** `$fillable`/`$hidden` properties.
- Traits: `App\Traits\Approvable` (multi-level approval), `App\Traits\ManagesWorkDays`.
- Concerns: `App\Concerns\PasswordValidationRules`, `App\Concerns\ProfileValidationRules`.
- RBAC via Spatie Permission, 5 roles: `super-admin`, `hr-manager`, `finance`, `manager`, `employee`.
- SoftDeletes on: User, Employee, Attendance, Department, Position, Leave, Overtime, Loan, Reimbursement, Asset, PerformanceReview, Payroll.
- Custom exceptions in `App\Exceptions\` (HTTP codes per error-handling-strategy.md §11.0):
  - `BusinessRuleException` → 422
  - `FaceNotRegisteredException` → 422
  - `NotClockedInException` → 409 (state conflict)
  - `AlreadyClockedInException` / `AlreadyClockedOutException` → 409
  - `AntiFakeGPSException`, `FaceNotRecognizedException`, `GeofenceViolationException`, `InvalidPinException`

## 3NF Companies / Branches / Employees

- **Companies**: legal identity only (name, phone, email, website, npwp, code, logo, is_active). NO address FK.
- **Branches**: `address` text + `latitude`/`longitude`/`radius` for geofence. NO Indonesia FK columns.
- **Employees**: keep province/city/district/village FK for personal address.

## PII Protection (SEC-1)

- Employee `#[Hidden(['face_embedding','pin','nik','phone','npwp','bank_account_number'])]`
- FamilyDetail `#[Hidden(['nik','phone','address'])]`
- Company `#[Hidden(['npwp'])]`
- Encrypted columns: `nik`, `phone`, `npwp`, `bank_account_number` (Employee); `nik`, `phone`, `address` (FamilyDetail); `npwp` (Company).

## CipherSweet (PII Encryption)

- **Package**: `spatie/laravel-ciphersweet` v1.7.4 — AEAD field-level encryption with searchable blind indexes
- **Env vars**:
  - `CIPHERSWEET_KEY` — 64-char hex key (required, already in `.env` and `.env.production`)
  - `CIPHERSWEET_BACKEND=nacl` (default)
  - `CIPHERSWEET_PROVIDER=string` (default)
- **Commands**:
  - `php artisan ciphersweet:generate-key` — generate a new encryption key
  - `php artisan ciphersweet:encrypt App\\Models\\Employee <key>` — encrypt existing rows (run after seed!)
  - `php artisan ciphersweet:encrypt App\\Models\\FamilyDetail <key>`
  - `php artisan ciphersweet:encrypt App\\Models\\Company <key>`
- **Models**: 3 models implement `CipherSweetEncrypted` + trait `UsesCipherSweet`:
  - `Employee` — encrypts `nik`, `phone`, `npwp`, `bank_account_number`; blind indexes `nik_hash`, `phone_hash`, `npwp_hash`
  - `FamilyDetail` — encrypts `nik`, `phone`, `address`; blind indexes `nik_hash`, `phone_hash`
  - `Company` — encrypts `npwp`; blind index `npwp_hash`
- **Blind index storage**: Blind indexes are stored in the **`blind_indexes` table** (polymorphic `morphs` + `name` + `value`), NOT as columns in the main table. Migration `2026_04_21_014315_create_blind_indexes_table.php` is published.
- **Query rule**: Use `whereBlind()` scope (auto-computes hash). **NEVER query encrypted columns directly.**
  ```php
  // ✅ CORRECT — uses the blind_indexes table
  Employee::whereBlind('nik', 'nik_hash', $nikValue)->first();
  Employee::whereBlind('phone', 'phone_hash', $phoneValue)->first();

  // ❌ WRONG — 'nik_hash' column does NOT exist in employees table
  Employee::where('nik_hash', $nik)->first();
  ```
- **Unique validation**: Use `Rule::encryptedUnique()` for encrypted fields:
  ```php
  use Illuminate\Validation\Rule;
  Rule::encryptedUnique(Employee::class, 'nik_hash');
  ```
- **Key rotation**: Generate new key → run `ciphersweet:encrypt` for each model → update `CIPHERSWEET_KEY` in `.env`.
- **False positives**: Blind indexes may produce collisions. Always verify decrypted value matches after lookup.
- **Config**: Published at `config/ciphersweet.php`.

## 3 Core Thesis Features (MUST NOT defer)

1. **Face Recognition** — face-api.js (FaceNet 128D) → pgvector. `FaceRecognitionService::verifyFace()` uses `<=>` cosine distance, threshold `MAX_DISTANCE = 0.15` (≈85% similarity). `employees.face_embedding` is `vector(128)` cast as `'vector'`. Fallback: Face → PIN → Manual (face failure NOT swallowed — CAT-017). Clock-in and clock-out have **separate** `verification_method` / `face_similarity_score` columns (ERR-004).
2. **GPS Geofencing + Anti-Fake GPS** — Haversine in `GeofenceService::validateLocation()`. Block if `is_mocked` or `accuracy > 100m` → `AntiFakeGPSException`. WFA mode skips GPS but requires note ≥20 chars + approval.
3. **KnowledgeBase RAG** — polymorphic morph `knowledgeable`, `embedding vector(768)` (NOT 1536). Embedding via Gemini `text-embedding-004`, Q&A via Gemini 2.5 Flash. Fallback to `pg_trgm` when AI down. Status/category enums exist.

## Known Critical Bugs (must check before touching these files)

> **STATUS (2026-05-31):** B1, B2, B3, B4, B5, B6, B7, B12 — semua **SELESAI** di Sesi 4-8.
> Tidak ada bug critical yang masih open. Daftar di bawah disimpan sebagai catatan historis untuk regression check.

| Bug | File:line | Status |
|-----|-----------|--------|
| **B1** Payroll regenerate uses `delete()` | `PayrollCalculatorService.php` | ✅ FIXED — pakai `forceDelete()` |
| **B2** Payroll race condition | `PayrollCalculatorService.php` | ✅ FIXED — `lockForUpdate()` di awal transaction |
| **B3** Leave quota deducted at submit, not approval | `LeaveService::applyLeave()` + `ApprovalService::approve()` | ✅ FIXED — deduct dipindah ke `isAllApproved()` callback |
| **B4** `LeaveBalance::deduct()` allows negative | `LeaveBalance.php` | ✅ FIXED — guard `if ($this->available() < $days) throw BusinessRuleException` |
| **B5** `family_details_count` counts ALL relations | `Employee.php` + `PayrollCalculatorService::getTERCategory()` | ✅ FIXED — scoped relation `Employee::children()` (Sesi 4) |
| **B6** Hardcoded `/22` working days | `PayrollCalculatorService.php` | ✅ FIXED — `countWorkingDays($startOfMonth, $endOfMonth)` (Sesi 4) |
| **B7** Overtime weekday flat 1.5x | `PayrollCalculatorService.php` | ✅ FIXED — tiered: hour-1=1.5x, sisa=2x (UU Cipta Kerja) (Sesi 4) |
| **B12** `FaceNotRegisteredException` not caught | `AttendanceService.php` | ✅ FIXED — `resolveVerification()` tiered Face → PIN (Sesi 4) |

## Caching Gotchas

- **`CACHE_STORE=database`** — does **NOT** support `Cache::tags()`. Use `Cache::forget("key")` per-key, never `Cache::tags(['x'])->flush()`. caching-strategy.md §3 has examples that **will throw `BadMethodCallException`** with this driver.
- **Holiday cache is wasteful** (`PayrollCalculatorService.php:88`): key `holiday_{YYYY-MM-DD}` creates 30+ entries per payroll run. caching-strategy.md §2.6 prescribes `holidays:{year}` (1 entry, in-memory check). Refactor pending.
- **Stale cache risk** — `tax_configs`, `bpjs_configs`, `holiday_*`, `settings:{key}` use 1-day–1-month TTL but **no observers** invalidate them. Admin updates via Eloquent normal flow → cached value stale until TTL expires. Need `app/Observers/` populated (see Empty Folders).
- **Cache invalidation pattern** when modifying any cached model: call `Cache::forget("key")` in the same transaction or use observer. `CompanySetting::set()` does this manually but bypasses if you call `update()` directly.
- **Cache key naming** — mixed conventions exist (`holiday_{date}` vs `settings:{key}` vs `tax_configs`). Strategy doc prescribes colon separator: `module:identifier:key`.

## PHP 8.5 Deprecations

- **`ReflectionProperty::setAccessible()`** is **DEPRECATED in PHP 8.5**. Reflection is accessible by default — never call `$prop->setAccessible(true)`. Just `getProperty()` then `setValue()` / `getValue()` directly.
- **`ReflectionMethod::setAccessible()`** same — deprecated, omit the call.
- **`ReflectionClass::newInstanceWithoutConstructor()`** still works but consider direct instantiation when possible.
- When fixing legacy code, search `setAccessible` and remove the call — keep the rest of the reflection chain intact.

## Database Gotchas

- **Defensive Migration**: any `vector`, `pg_trgm`, `pgcrypto`, HNSW index code MUST be guarded with `if (DB::getDriverName() === 'pgsql')` for SQLite test compat (CAT-018). 3 migrations already guarded: users, employees, knowledge_bases.
- **Never hardcode SQL state `23505`**. Use `Illuminate\Database\UniqueConstraintViolationException` (CAT-019).
- **Payroll** is permanently locked once `PUBLISHED` — `isLocked()` blocks PUBLISHED + PAID. Corrections via `PayrollAdjustment` for next month, not unpublish.
- **ApprovalLevel comparison**: `$approval->level === ApprovalLevel::L1_SUPERVISOR` — never `=== 1` (enum vs int, always false — CAT-001).
- **`Carbon` immutable**: `AppServiceProvider` calls `Date::use(CarbonImmutable::class)`. Mutating Carbon ops (`->addDay()` etc.) return new instances; chain or assign.
- **`DB::prohibitDestructiveCommands()`** active in production — `migrate:fresh`, `db:wipe` blocked.
- **Development workflow**: edit original migration files directly, then `migrate:fresh --seed`. Avoid alter-table migrations until deployed.

## Auth (Fortify)

- Custom view bindings in `App\Providers\FortifyServiceProvider`.
- Features: registration, password reset, email verification, 2FA TOTP + confirm password.
- User uses `HasRoles` (Spatie), `SoftDeletes`, `TwoFactorAuthenticatable`, `HasApiTokens` (Sanctum).
- `password_changed_at` column exists; `CheckPasswordExpired` middleware **DONE** (CAT-005 closed) — alias `password.expired`, default 90 hari, configurable via CompanySetting `password_expiry_days`.
- Custom actions: `App\Actions\Fortify\CreateNewUser`, `App\Actions\Fortify\ResetUserPassword`.

## Testing

- Pest 4. PHPUnit config uses **SQLite in-memory** — pgvector/pg_trgm/CipherSweet **will not** work without mocking. Defensive Migration guards prevent crashes.
- For pgvector-specific tests: see `docs/testing/testing-strategy.md` §9.
- CI: `./vendor/bin/pest` (`.github/workflows/tests.yml`).

## Project Status

- Phase 1 (Foundation): ~90% complete (Sprint 1-11 in `develop`).
- Phase 2-6: 0% (UI/API/policies/observers).
- Branches: `develop` = integration, `feat/sprintXX-*` = work, `main` = protected.

## Deferred to V2 — Do NOT implement

Loan/Kasbon (tables exist but service incomplete), Asset Management, Performance Review, WhatsApp Notifications.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
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
