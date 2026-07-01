# HRConnect — Panduan Agent

Enterprise HRIS (skripsi). Laravel 13 + Livewire 4 + Tailwind CSS 4 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

## Instruction Files (wajib dibaca)

| File | Isi |
|------|-----|
| `CLAUDE.md` / `GEMINI.md` | Laravel Boost guidelines — skills activation, Boost MCP tools, search-docs, coding conventions, lint auto-fix |
| `DESIGN.md` | MD3 design tokens, app vs landing theme split, utility classes |
| `task.md` | Pekerjaan tersisa, audit findings, UX porting plan |

## Skills Tersedia (`.agents/skills/`)

Aktifkan via `skill` tool sesuai domain: `ai-sdk-development`, `fortify-development`, `laravel-best-practices`, `livewire-development`, `pest-testing`, `tailwindcss-development`.

## Prioritas Development

| Prioritas | Role | Kompleksitas |
|-----------|------|:------------:|
| **P1** | **HR-Manager** | Payroll config, master data, employee lifecycle, RAG docs, reports, company settings |
| **P2** | **Finance** | Payroll execution, reimbursement payment, loan, asset, tax reports |
| **P3** | **Manager** | Approval L1, team monitoring |
| **P4** | **Employee** | Clock-in/out, apply cuti/lembur/reimbursement, view payslip, chat RAG |

**Pembuatan akun & semua data master** ada di HR-Manager + Finance — bukan employee.

**Sumber referensi UI (wajib):** Semua Blade/Livewire component harus merujuk ke repo clone di `/home/merger/`. Jangan buat dari nol — ambil pola dari PasPapan (components, layout, Alpine patterns) lalu konversi ke Tailwind CSS 4 + Material Symbols + MD3 tokens HRConnect. Lihat `task.md` §UX Porting Plan untuk detail per komponen.

## Status ~95%

- ✅ Database: 50 migration, seluruh entitas inti
- ✅ API: 21 controller `/api/v1`, ~80 endpoint, Sanctum + 2FA + RBAC
- ✅ Business logic: PPh21 TER, BPJS, prorata, lembur PP 35/2021, face recognition + geofencing, multi-level approval, RAG, termination lifecycle
- ✅ Keamanan: CipherSweet, device verification, password expiry, rate limiting
- ✅ Frontend: Livewire 4 SFC, Alpine.js, PWA, Tailwind CSS 4 + MD3 tokens
- ✅ Tests: **78 file — 1.173 passed, 2 skipped, 4.128 assertions** (SQLite)
- ⏳ Lihat `task.md` untuk item tersisa

## Perintah Kunci

| Tujuan | Perintah |
|--------|---------|
| Dev server | `composer run dev` (serve + queue + pail + vite) |
| Full test | `composer test` (config:clear + lint + phpunit) |
| Test spesifik | `php artisan test --compact --filter=Nama` |
| Test PostgreSQL | `composer test:pgsql` (→ `phpunit.pgsql.xml`) |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| Lint check only | `composer lint:check` (CI-safe, dry-run) |
| PHPStan | `vendor/bin/phpstan analyse` (level 5, only `app/`) |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Regenerasi API docs | `php artisan scramble:export` (→ `docs/api/api.json`) |
| Queue worker | `php artisan queue:work --queue=default,payroll_high,notifications` |

**Catatan:** `composer test` jalanin `config:clear` + `lint:check` dulu — untuk fast feedback pakai `php artisan test --compact --filter=X`.

## Setup & Lingkungan

- Extension PG sebelum migrate: `vector`, `pg_trgm`, `pgcrypto`. Guard dengan `if (DB::getDriverName() === 'pgsql')` untuk SQLite compat.
- `CIPHERSWEET_KEY` wajib (64-char hex). `.env.example` punya placeholder; `phpunit.xml` punya test key.
- `DB_URL` harus **kosong** untuk SQLite test.
- `.npmrc` sets `ignore-scripts=true` — `npm install` skip build scripts. Run `npm run build` manually.
- `pgvector/pgvector` di `dont-discover` — register manual via `PgvectorSchema::register()` di AppServiceProvider.
- `post-update-cmd` runs `boost:update` — butuh `.env` ada.
- Livewire v4 SFC: `make_command.emoji` set `false`.
- Boost MCP tools tersedia: `database-query`, `database-schema`, `search-docs`, `browser-logs`.

## Arsitektur

- **21 API controller** di `/api/v1` (single `routes/api.php`). Sanctum bearer auth, never expire. Public: health, login, 2fa-challenge, forgot-password.
- **19 services** di `App\Services` — logika bisnis di sini, bukan controller. Payroll: BpjsService, LemburService, PotonganService, Pph21Service.
- **34 models, 31 factories, 50 migrations, 14 route files**
- **34 enums** — 16 Status/Indicator punya `color()` (MD3 semantic); 18 Classification jangan.
- **8 observers** (register manual di AppServiceProvider), **13 policies** (auto-discovery), **8 notifications**
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee.
- **Web routes** via `bootstrap/app.php` `then` block — membaca `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php`.
- **Model attributes**: Laravel 13 `#[Fillable]`/`#[Hidden]` syntax.

## Frontend & JS

- Vite entry: `resources/css/app.css` + `resources/js/app.js`. Tailwind CSS 4 via `@tailwindcss/vite` plugin.
- **14 Alpine.data sudah diekstrak** dari inline Blade ke `resources/js/{nama}.js` — didaftarkan di `app.js` `alpine:init` (baris 119-136). Penting untuk `wire:navigate` compat.
- **Toast event**: `$this->dispatch('toast', variant: 'success', text: '...')` — JS di `app.js:196` listen `toast`, **bukan** `notify`.
- JS utilities: `window.HRConnectAlert` (toast/confirm/modal via SweetAlert2), `window.L` (Leaflet), `flatpickr`, `TomSelect`.
- `npm run build` untuk production assets.

## Design System

**Source of truth:** `DESIGN.md`. CSS variables dari `@theme` di `resources/css/app.css`.

**Aturan:**
- Jangan hardcode warna/radius/font — pakai utility: `bg-canvas`, `text-ink`, `rounded-xl`, dll
- Font: Rubik 500 (display), Inter (body)
- Brand colors (pink, teal, lavender) → landing page ONLY. HR pages pakai netral.
- Layout: `x-layouts::app.sidebar`
- Theme split **sudah final**: app (canvas `#ffffff`) vs landing (`.landing-theme` → `#fffaf0`).

## Critical Gotchas

- **`CACHE_STORE=database`** → `Cache::tags()` throws `BadMethodCallException`. Pakai `Cache::forget('key')`.
- **CipherSweet**: Jangan query kolom terenkripsi langsung. Pakai `whereBlind('nik', 'nik_hash', $value)`. Unique: `Rule::encryptedUnique(Employee::class, 'nik_hash')`.
- **PHP 8.5**: `ReflectionProperty::setAccessible()` deprecated — langsung `getProperty()` → `setValue()`/`getValue()`.
- **CarbonImmutable**: `Date::use(CarbonImmutable::class)` di AppServiceProvider → mutations return new instances.
- **ApprovalLevel**: compare sebagai `$a->level === ApprovalLevel::L1_SUPERVISOR`, jangan `=== 1`.
- **Payroll isLocked()**: block PUBLISHED + PAID. Koreksi via PayrollAdjustment bulan depan.
- **PII split**: `GET /employees/{id}` masking NIK/phone/NPWP/bank. `GET /employees/{id}/pii` reveal (butuh `manage_employees` + audit log).
- **Per-page max**: pagination `per_page` capped 100.
- **Scramble**: `api_path => 'api'` strips prefix (tampil `/v1/...`) tapi server URL include `/api`. URLs resolve benar.
- **`RAG_MOCK_MODE=true`** — **dead config.** PHP tidak membaca ini. Selalu panggil Gemini sungguhan.
- **pg_trgm fallback** hanya di sync `chat()`, bukan streaming.

## Reference Repos (Cloned — Jangan Hapus)

Pattern source-of-truth. **Adopsi UX/component patterns saja, jangan copy CSS/colors.**

| Repo | Path | Untuk |
|------|------|-------|
| **PasPapan** | `/home/merger/PasPapan/` | Face scan, GPS, risk scoring, approval, termination, UI components |
| **Quanta HRIS** | `/home/merger/quanta-hris-laravel/` | Payroll Indonesia (PPH21, BPJS, lembur, potongan) |
| **ship-ai-with-laravel** | `/home/merger/RAG-repo/ship-ai-with-laravel/` | RAG chat SSE streaming pattern |
| **Lainnya** | `/home/merger/laravel-smarthr/`, `hrms-livewire/`, dll | Prioritas rendah |

## CI

`develop`/`main`/`master`/`workos` push + PR trigger:
- **lint.yml**: PHP 8.4, `composer lint` (Pint). Auto-commit di-comment out.
- **tests.yml (sqlite)**: PHP 8.5, `composer install --optimize-autoloader`, `./vendor/bin/pest`.
- **tests.yml (postgres)**: PHP 8.5, `pgvector/pgvector:pg16` service, `./vendor/bin/pest --configuration=phpunit.pgsql.xml`.

## RAG Knowledge Base

- **Backend**: `KnowledgeBaseService` — embed via Gemini `text-embedding-004` (768D) → pgvector cosine top-5 → `HrKnowledgeBaseAgent` (Gemini 2.5 Flash, structured output).
- **Sync API**: `POST /api/v1/knowledgebase/chat` → JSON `{answer, sources, confidence}`.
- **Streaming API**: `POST /api/v1/knowledgebase/chat-stream` → SSE `text/event-stream`.
- **Conversation memory**: `conversationId` dikirim balik tapi **tidak disimpan** ke DB. Setiap chat = konteks fresh.
