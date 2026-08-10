# AGENTS.md — HRConnect

**Stack:** Laravel 13 / PHP 8.5+ / PostgreSQL 15+ (pgvector, pg_trgm, pgcrypto) / Livewire 4 / Tailwind v4 / Alpine.js / PWA
**Dev:** Fikih (solo) — HRIS enterprise: face recognition, GPS geofencing, payroll PPh21, RAG KB
**Perusahaan utama:** PT Daya Cipta Mandiri Solusi — Jl. Pegambiran No.292 B, RT.15/RW.8, Rawamangun, Kec. Pulo Gadung, Kota Jakarta Timur, DKI Jakarta 13220 (branding: `app.company_name`/`app.company_address` di tabel `settings`; jangan ganti ke nama lain)

---

## 📌 Product Scope & PRD (Release 1)

**`PRD.md` (root) adalah scope contract.** Baca sebelum implement fitur/change apa pun:

- **Hanya 7 modul Release 1:** master data karyawan, absensi & jadwal, cuti & approval, payroll & payslip, dokumen & HR checklist, reports & import/export, AI Knowledge Base.
- **Change-control gate:** selama Release 1 belum lolos production gate, **jangan tambah fitur di luar 7 modul** (recruitment, training, performance review, marketplace, dll = out of scope). Bug/security boleh diperbaiki; fitur baru tidak.
- **Production gate = 0 open P0/P1** (P0: data leak/rusak, payroll salah, gagal login; P1: fitur core mati, queue/mail mati, backup gagal, AI silent failure).
- **No silent degradation / no placeholder:** tiap kegagalan (AI, queue, embedding, import) harus terlihat di log + UI. Jangan jawab AI dengan respons palsu/fake embedding.
- **AI KB hard gate:** embedding **768D nyata** (jangan fake/random), **citation pada setiap jawaban**, timeout/retry/cost limit, eval dataset ≥20 Q&A, kualitas ≥90% relevan.
- **Policy tetap:** absensi face-only (tanpa PIN fallback), geofence radius 50 m + toleransi 15 menit, approval default Manager → HR, payroll gross bulanan + PPh21 TER, kalender kerja 5 hari, RPO 24 jam / RTO 4 jam.
- **Non-goal:** SaaS multi-tenant, layer edition/license, feature-lock baru di luar yang sudah ada.

---

## Status & Progress

- **92 Livewire components** · **101 models** · **72 controllers** (~104 features)
- Progress/status: `docs/PROGRESS.md` + `docs/FEATURE-INVENTORY.md` (di-update per session)
- Audit terbaru: `AUDIT.md` (root, git-tracked, SATU-SATUNYA file audit) — jangan re-fix issue yang sudah tercatat
- ⚠️ **Git hygiene:** `docs/**`, `.agents/`, `.claude/`, `.opencode/`, `phpstan.neon.dist`, `phpstan-baseline.neon`, `codemap.md` (semua level), `/.slim/` — gitignored, **local-only** (branch main produksi hanya berisi kode + folder penting). `tests/` + `PRD.md` **TRACKED sejak 2026-08-04** (commit `5e2f953`) — ikut CI/checkout fresh. `.github/workflows/tests.yml` **TRACKED** (commit `e5b929f`, 2026-08-05) + `lint.yml` **TRACKED** (2026-08-06). ⚠️ Push workflow butuh `workflow` scope di token GitHub (pernah ditolak: "refusing to allow an OAuth App to create or update workflow"). `AUDIT.md` root TETAP di-commit (SATU-SATUNYA file audit — jangan buat file audit lain); `.env.testing` **local-only** (berisi kredensial DB asli — di-untrack 2026-08-04, jangan commit ulang; ⚠️ password asli pernah ada di history commit `6a24cc4` — sudah ter-push, repo saat ini PRIVATE + DB testing lokal, rotasi password baru wajib sebelum repo dipublikasikan); `phpunit.pgsql.xml` sudah ter-commit.

---

## Perintah Penting

| Perintah | Fungsi |
|----------|--------|
| `composer run setup` | Bootstrap penuh: install deps, copy `.env`, `key:generate`, migrate, npm build |
| `composer run dev` | Server + queue + logs + Vite concurrently |
| `composer run test` | `config:clear` → `lint:check` → `php artisan test` (butuh pgsql lokal: DB `hris_testing`, user `postgres`/`password`) |
| `composer run ci:check` | `disableProcessTimeout` + `token:check` + `check-token-sync` + `check-ui-rules` + `@test` (audit statis dulu, test terakhir; tanpa `config:clear`/lint) |
| `composer run lint` | `pint --parallel` (auto-fix) |
| `composer run lint:check` | `pint --parallel --test` (dry-run) |
| `vendor/bin/pint --dirty --format agent` | **Wajib** setelah tiap perubahan PHP |
| `vendor/bin/pest` | Direct test runner (bypasses config:clear — lebih cepat buat iterasi) |
| `php artisan test --compact --filter=X` | Test spesifik (filter sesuai modul yang diubah) |
| `composer run test:pgsql` | `@php artisan test --configuration=phpunit.pgsql.xml` (dipakai CI job postgres) |
| `vendor/bin/phpstan analyse` | PHPStan level 5 (scans `app/`, baseline di `phpstan-baseline.neon`) — ⚠️ kedua config gitignored, hilang di checkout fresh |
| `npx playwright test --project=chromium-employee` | E2E role employee |
| `npx playwright test` | Full E2E suite (semua project) |
| `npm run test:e2e:headed` / `:debug` / `:report` | Playwright headed / debug / HTML report |
| `npm run build` | Build assets |
| `php artisan db:seed --class=KnowledgeBaseSeeder` | **Wajib** — isi KB entries + 768D embeddings. RAG gak jalan tanpa ini |

---

## Arsitektur

- `routes/web.php` require **6 file**: `web/system.php`, `web/files.php`, `web/user.php`, `web/payroll.php`, `web/admin.php` + `routes/knowledge-base.php` (prefix route `knowledge-base.`). Semua route didefinisikan di file-file ini, bukan inline di `web.php`.
- `routes/api.php` — REST API (middleware `auth:sanctum` + `throttle:api`).
- `app/Livewire/` → komponen. `app/Services/` → business logic. `app/Support/` → helper. `app/Domain/` → domain logic.
- `scripts/` — helper dev (erd-generator, check-blade-js-syntax, check-enterprise-boundary, screenshot dll).
- `app/helpers.php` — auto-loaded via `composer.json` `files`.
- `design.md` — locked design system. **Baca sebelum ngerjain UI.**
- `CLAUDE.md` / `GEMINI.md` — Laravel Boost guidelines (aktif via MCP `laravel-boost` di `opencode.json`). Untuk hal Laravel generik, ikuti ini.

---

## ⚠️ Critical: Schema FK Pattern

**Jangan tebak FK column. Ini pola tetap:**

| Tabel | Kolom | Cara query |
|:------|:-----:|:-----------|
| `attendances`, `payrolls`, `face_descriptors`, `overtimes`, `reimbursements`, `attendance_corrections`, `employee_document_requests` | `employee_id` | `whereHas('employee', fn ($q) => $q->where('user_id', $user->id))` |
| `schedules`, `cash_advances`, `shift_swap_requests`, `work_from_home_requests` | **`user_id`** | `where('user_id', $user->id)` langsung |

`attendance_corrections` punya KEDUA kolom (`employee_id` + `user_id`).

---

## ⚠️ Critical: Role/Permission BUKAN Spatie Default

**`HasRolePermissions` trait** (`app/Models/Concerns/HasRolePermissions.php`) baca dari `$role->permission_keys` — kolom JSON di tabel `roles`, BUKAN dari Spatie `role_has_permissions` pivot.

Kalo nambah permission ke role, update `permission_keys` JSON column langsung:
```php
$role->permission_keys = ['view_dashboard', 'view_knowledgebase', ...];
$role->save();
```

Permission enum: `App\Enums\Permission` (**94 case**). Auto-register sebagai Gates di `AuthServiceProvider`.

---

## ⚠️ Critical: CSS — JANGAN pake `@apply`

Tailwind v4 JIT tree-shake utility class yang cuma dipanggil di `@apply` dalam CSS (gak kedeteksi di template). **Pake CSS properti langsung:**

```css
/* ✅ BENAR */
.user-bottom-navigation {
  position: fixed;
  inset-inline: 0;
  bottom: 0;
  z-index: 40;
}

/* ❌ SALAH — kena tree-shake */
.user-bottom-navigation {
  @apply fixed inset-x-0 bottom-0 z-40;
}
```

`resources/css/app.css` hybrid HRConnect + PasPapan (~6200 baris). Kalo nambah style, taro di `@layer components` yang udah ada (mulai baris ~128).

---

## ⚠️ Livewire Quirks

- `$wire` di Alpine `init()` → pake **`this.$wire`**, bukan `$wire`
- `config/livewire.php`: `max_components` = 200, `max_size` = 5MB
- `@stack('scripts')` WAJIB ada di `layouts/app.blade.php` (baris ~120) — kalo gak, semua `@push('scripts')` gak ke-render (face-enrollment JS dll)
- `ClockInAction` → `app/Livewire/User/ClockInAction.php`, route `/scan` (`web/user.php`)

### ⚠️ Alpine $watch dengan $wire

```javascript
// ❌ SALAH — $wire magic property bukan Alpine data path, bakal crash
this.$watch('$wire.messages', callback);

// ✅ BENAR — pake Livewire $watch method
this.$wire.$watch('messages', callback);
```

### ⚠️ canSend() di Alpine — pake $wire, bukan DOM ref

```javascript
// ❌ SALAH — DOM value gak reactive di Alpine
canSend() { return this.$refs.input.value.length >= 5; }

// ✅ BENAR — $wire.question trigger Alpine re-evaluate
canSend() { return this.$wire?.question?.trim()?.length >= 5; }
```

---

## ⚠️ Fixed Bugs (jangan di-fix ulang)

1. **`CommunityService::registerFace()`** (`app/Services/Attendance/CommunityService.php`) — pake `employee_id` + `embedding`, bukan `user_id` + `descriptor`. Strips 129→128 dimensi.
2. **Face descriptor 129 elements** — quality score di index[0] harus di-`array_slice()` sebelum disimpan. Kolom `face_descriptors.embedding` adalah `vector(128)`.
3. **Fortify config** — butuh `Features::updateProfileInformation()` dan `Features::updatePasswords()` di `config/fortify.php` `features` array.
4. **KB Chat sendMessage** — Alpine crash `$watch('$wire.messages', ...)` → `$wire.$watch('messages', ...)`. `canSend()` DOM ref → `$wire.question`.
5. **Flatpickr date pickers** — JS di-import di `resources/js/app.js` tapi gak pernah di-init. `[data-ui-picker]` elements jadi readonly text. Tambah `initFlatpickr()` + Livewire `morph.updated` hook.
6. **Appraisal `period_year`/`period_month`** — kolom gak ada, ganti pake `period` (single "YYYY-MM").
7. **ShiftSwapRequest `job_title_id`** — kolom gak ada di `users` table, hapus dari SELECT.
8. **UserAssetService `user_id`** — `company_asset_histories` pake `from_employee_id`/`to_employee_id`.
9. **KB Chat route name** — route name adalah `knowledge-base.chat`, BUKAN `kb.chat`.

---

## E2E Playwright

- Auth state pake `storageState` (5 role states: employee, hr, manager, finance, admin) di `tests/e2e/.auth/`.
- Employee creds: `employee@hrconnect.test` / `password` (dibuat `E2eTestSeeder.php`).
- ✅ **`tests/e2e/auth.setup.ts` TRACKED** sejak commit `4190822` (fix(audit) 2026-08-05) — project `setup` di `playwright.config.js` (testMatch `/auth\.setup\.ts/`) me-re-generate storage state 5 role via **endpoint dev `GET /__e2e-login`** (token `services.e2e.login_token`, default `local-apk-e2e`, hanya aktif di env local/testing), bukan fill form login. Semua project role punya `dependencies: ['setup']`.
- ⚠️ Storage states `.auth/*.json` hasil generate tetap **gitignored** — di checkout fresh jalankan `npx playwright test --project=setup` dulu supaya `.auth/*.json` terbentuk; jangan hapus `.auth/` (semua project E2E butuh state-nya).
- Bottom nav: Beranda, Jadwal, Absen (`/scan`), Tasks, Profil.
- 🧹 **2026-08-10: spec legacy Paspapan + email dev DIHAPUS** (approval-workflow, main-smoke, payroll, login-critical, profile, post-login, test-profile, login_and_dashboard_check) — kredensialnya (`apk.demo.*@paspapan.test`, `fikhahldiansyah28@gmail.com`) tidak ada di DB seeder, tidak pernah hijau. Project config yang jadi kosong ikut dihapus. **Project aktif:** `setup`, `chromium-employee` (24 halaman user), `chromium-pwa` (manifest + SW). Semua butuh `permissions: ['camera','geolocation']` (sudah di config).

---

## Env Quirks

- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`
- `CIPHERSWEET_KEY` **wajib** (hex; generate via `php artisan ciphersweet:generate-key`). Nilai test/dev di semua config: `0123456789abcdef0123456789abcdef` (phpunit.xml, phpunit.pgsql.xml, .env.testing).
- `GEMINI_API_KEY` prefer dari `GOOGLE_AI_API_KEY`; model RAG di `.env.example`: `GEMINI_MODEL=gemini-2.5-flash`, embedding `gemini-embedding-001` 768D (via `output_dimensionality`; `text-embedding-004` dihapus Google 2026 → 404).
- ⚠️ `package.json` `overrides` pin **`@tensorflow/tfjs-core` ke 2.4.0** — jangan upgrade (face-api.js butuh API v2).
- `docs/**`, `/.agents/`, `/.claude/`, `/.opencode/`, `phpstan.*` — gitignored.

---

## Testing Quirks

- **`phpunit.xml` hardcode PostgreSQL** (`DB_DATABASE=hris_testing`, `postgres`/`password`) — `composer run test` butuh pgsql lokal jalan dengan DB+user itu.
- `phpunit.pgsql.xml` = copy identik `phpunit.xml` (sama-sama `hris_testing`/`postgres`/`password`), dipakai job postgres di CI (`tests.yml`).
- **`tests/bootstrap.php` JANGAN dihapus** — force env testing ke `putenv`+`$_ENV`+`$_SERVER`. PHPUnit `<env force="true">` cuma nulis `$_ENV`, host env (mis. `DB_DATABASE` di shell) menang dan bisa bikin suite nyasar ke dev DB.
- `.env.testing` **local-only** (berisi kredensial DB asli — di-untrack 2026-08-04, jangan commit ulang). DB `hris_testing`, user `hrconnect_app`.
- CI `.github/workflows/tests.yml` **TRACKED** (commit `e5b929f`, 2026-08-05): satu job `postgres` (pgvector/pg16) jalanin `./vendor/bin/pest --configuration=phpunit.pgsql.xml` dengan `POSTGRES_DB=hris_testing` — mismatch sqlite/pgsql + hrconnect_testing (Q4) sudah di-fix; `lint.yml` (Pint `composer lint`) tracked 2026-08-06. ⚠️ Push ke `.github/` butuh `workflow` scope di token GitHub (pernah ditolak).
- `vendor/bin/pest` langsung jalan tanpa `config:clear` prefix (lebih cepet buat iterasi).
- **Feature lock middleware** — pake `middleware('feature.lock:{module},{user|gate:ability},{fallback_route}')` (contoh: `cash_advance`, `assets`, `appraisal`).

---

## Stack Tambahan

| Library | Fungsi |
|---------|--------|
| **Jetstream 5.x** | Profile pages, API tokens, 2FA forms |
| **Laravel Reverb + Echo** | Real-time broadcasting (pusher-js + laravel-echo) |
| **Capacitor 8** | Native mobile app (`capacitor.config.ts`) |
| **spatie/laravel-activitylog** | Activity trail |
| **spatie/laravel-backup** | Automated backups |
| **sentry/sentry-laravel** | Error tracking |
| **laravel/ai** | AI SDK (RAG KB, agents — lihat skill `ai-sdk-development`) |
| **maatwebsite/excel** (4.x-dev) + openspout | Import/export module (modul 6) |

---

## Sebelum Nulis Kode

1. Baca **CONVENTIONS.md** — ikuti pola yang ada.
2. Cek **AUDIT.md + docs/PROGRESS.md** — jangan re-fix known issues.
3. Cari **1-2 contoh existing** dengan pola yang sama. Referensi eksplisit.
4. Kalau **tidak ada** pola existing → **tanya Fikih** — jangan improvisasi.
5. **Prioritas:** CONVENTIONS.md > CLAUDE.md (Boost guidelines) > guidance Laravel generik.

### Definition of Done

1. Semua caller method/field yang diubah udah diupdate (grep dulu).
2. `vendor/bin/pint --dirty --format agent` pass.
3. `php artisan test --compact --filter=X` pass (filter sesuai modul yang diubah).
4. `npx playwright test --project=chromium-employee` pass (kalau perubahan nyentuh UI user).

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

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
- laravel/reverb (REVERB) - v1
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
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `ai-sdk-development` — TRIGGER when working with ai-sdk which is Laravel official first-party AI SDK. Activate when building, editing AI agents, chatbots, text generation, image generation, audio/TTS, transcription/STT, embeddings, RAG, vector stores, reranking, structured output, streaming, conversation memory, tools, queueing, broadcasting, and provider failover across OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, ElevenLabs, Cohere, Jina, and VoyageAI. Invoke when the user references ai-sdk, the `Laravel\Ai\` namespace, or this project's AI features — not for other AI packages used directly.
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `echo-development` — Develops real-time broadcasting with Laravel Echo. Activates when setting up broadcasting (Reverb, Pusher, Ably); creating ShouldBroadcast events; defining broadcast channels (public, private, presence, encrypted); authorizing channels; configuring Echo; listening for events; implementing client events (whisper); setting up model broadcasting; broadcasting notifications; or when the user mentions broadcasting, Echo, WebSockets, real-time events, Reverb, or presence channels.
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
- Follow existing application Enum naming conventions.
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
