# HRConnect — Panduan Agent

Enterprise HRIS (skripsi). Laravel 13 + Livewire 4 + Tailwind CSS 4 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo dev.

## Prioritas Development

| Prioritas | Role | Kompleksitas | Alasan |
|-----------|------|:------------:|--------|
| **P1** | **HR-Manager** | 🔴 Tertinggi | Payroll config, master data, employee lifecycle, RAG docs, reports, company settings |
| **P2** | **Finance** | 🟡 Tinggi | Payroll execution, reimbursement payment, loan, asset, tax reports |
| **P3** | **Manager** | 🟢 Sedang | Approval L1, team monitoring |
| **P4** | **Employee** | 🔵 Rendah | Clock-in/out, apply cuti/lembur/reimbursement, view payslip, chat RAG |

**Pembuatan akun & semua data master** ada di HR-Manager + Finance — bukan employee.

**Sumber referensi UI (wajib):** Semua Blade/Livewire component harus merujuk ke repo yang sudah di-clone di `/home/merger/`. Jangan buat dari nol — ambil pola dari PasPapan (components, layout, Alpine patterns) lalu konversi ke Tailwind CSS 4 + Material Symbols + MD3 tokens HRConnect. Lihat `task.md` §UX Porting Plan untuk detail per komponen.

## Status Penyelesaian ~85%

- ✅ Database: 57 tabel, 50 migration — semua entitas inti HRIS
- ✅ API: 14 controller, 47+ endpoint `/api/v1`, Sanctum + 2FA + RBAC
- ✅ Business logic: payroll (PPh21 TER, BPJS, prorata, lembur PP 35/2021), presensi wajah + geofencing, approval multi-level, RAG knowledge base, termination lifecycle
- ✅ Keamanan: CipherSweet field encryption, device verification, password expiry, rate limiting
- ✅ Frontend: Livewire 4, Alpine.js, PWA, Tailwind CSS 4 + MD3 tokens
- ✅ Tests: 78 file, 1.052 fungsi test (SQLite), 1 integrasi PG
- ⏳ Lihat `task.md` §Pekerjaan Tersisa untuk item yang belum selesai

## Perintah Kunci

| Tujuan | Perintah |
|--------|---------|
| Dev server | `composer run dev` (serve + queue + pail + vite) |
| Full test suite | `composer test` (lint + phpunit, SQLite) |
| Test PostgreSQL | `composer test:pgsql` (→ `phpunit.pgsql.xml`) |
| Test spesifik | `php artisan test --compact --filter=Nama` |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| Lint check only | `composer lint:check` (CI-safe, no changes) |
| PHPStan | `vendor/bin/phpstan analyse` (level 5, only `app/`) |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Queue worker | `php artisan queue:work --queue=default,payroll_high,notifications` |
| Regenerasi API docs | `php artisan scramble:export` (→ `docs/api/api.json`) |

## Setup & Lingkungan

- Extension PG sebelum `migrate`: `CREATE EXTENSION IF NOT EXISTS vector; pg_trgm; pgcrypto`. Guard dengan `if (DB::getDriverName() === 'pgsql')` untuk SQLite compat.
- `CIPHERSWEET_KEY` wajib (64-char hex). `.env.example` punya placeholder; `phpunit.xml` punya test key.
- `DB_URL` harus **kosong** untuk SQLite test (set `""` di phpunit.xml).
- Default env: `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database` (encrypted), `HASH_DRIVER=argon2id`, `CACHE_STORE=database`.
- `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id` — response API Indonesian, Faker `id_ID`.
- Dual AI key: `GEMINI_API_KEY` preferred; `GOOGLE_AI_API_KEY` legacy fallback.
- `.npmrc` sets `ignore-scripts=true` — `npm install` tidak jalanin build scripts.
- `pgvector/pgvector` ada di `dont-discover` — register manual via `PgvectorSchema::register()` di `AppServiceProvider`.
- `post-update-cmd` runs `boost:update` — butuh `.env` ada.
- Livewire v4 SFC: `make_command.emoji` set `false` — no ⚡ prefix di filename.

## Arsitektur

- **14 API controller** di `/api/v1` (single `routes/api.php`). Sanctum bearer auth, token never expire. Public: health, login, 2fa-challenge, forgot-password.
- **~18 service** di `App\Services`. Logika bisnis di sini, bukan controller. Termasuk 4 file di `Services/Payroll/` (BpjsService, LemburService, PotonganService, Pph21Service).
- **8 observer** (register manual di `AppServiceProvider`), **8 policy** (auto-discovery), **7 notification**.
- **34 enum**: 16 Status/Indicator punya `color()` (MD3 semantic: success/warning/error/info); 18 Classification enum JANGAN.
- **5 Spatie roles**: super-admin, hr-manager, finance, manager, employee.
- **34 model**, **27 factory**, **14 route files** (termasuk `routes/console.php`).
- **Web routes** via `bootstrap/app.php` `then` block — membaca `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php`. `routes/settings.php` di-require dari `web.php`.
- **Model attributes**: Laravel 13 `#[Fillable]`/`#[Hidden]` syntax.

## Design System

**Source of truth:** `DESIGN.md`. CSS variables dari `@theme` di `resources/css/app.css`.

**Token MD3 yang dipakai:** `--color-surface-container`, `--color-on-background`, `--color-outline-variant`, `--color-primary`, `--color-error`, dll. Legacy tokens (`--color-body`, `--color-muted`, dll) masih ada sebagai backward compat.

**Aturan:**
- Jangan hardcode warna/radius/font — pakai utility classes: `bg-canvas`, `text-ink`, `rounded-xl`, dll
- Font: Rubik 500 (display), Inter (body)
- Brand colors (pink, teal, lavender, dll) → landing page ONLY. Halaman HR pakai warna netral/abu.
- Layout: `x-layouts::app.sidebar`

**App vs Landing split:** `DESIGN.md` mendeskripsikan dua tema (app: canvas putih #ffffff, landing: canvas cream #fffaf0). Implementasi baru sebagian: `.landing-theme` class di `welcome.blade.php` sudah ada, tapi `app.css` masih pakai cream (#fffaf0) — nilai belum divergen. Update `app.css` dulu kalau mau finalisasi split.

## Critical Gotchas

- **`CACHE_STORE=database`** → `Cache::tags()` throws `BadMethodCallException`. Pakai `Cache::forget('key')`.
- **CipherSweet**: Jangan query kolom terenkripsi langsung. Pakai `whereBlind('nik', 'nik_hash', $value)`. Unique validation: `Rule::encryptedUnique(Employee::class, 'nik_hash')`.
- **PHP 8.5**: `ReflectionProperty::setAccessible()` deprecated — langsung `getProperty()` → `setValue()`/`getValue()`.
- **Defensive migration**: Guard pgvector/pg_trgm/pgcrypto dengan `if (DB::getDriverName() === 'pgsql')` untuk SQLite compat.
- **CarbonImmutable**: `Date::use(CarbonImmutable::class)` di AppServiceProvider → mutations return new instances.
- **ApprovalLevel**: compare sebagai `$a->level === ApprovalLevel::L1_SUPERVISOR`, jangan `=== 1`.
- **Payroll isLocked()**: block PUBLISHED + PAID. Koreksi via PayrollAdjustment bulan depan, bukan unpublish.
- **PII split**: `GET /employees/{id}` masking NIK/phone/NPWP/bank. `GET /employees/{id}/pii` reveal (butuh `manage_employees` + audit log).
- **Per-page max**: pagination `per_page` capped di 100.
- **Scramble**: `config/scramble.php` `api_path => 'api'` strips prefix dari paths (tampil `/v1/...`), tapi server URL include `/api`. URLs resolve benar.
- **`RAG_MOCK_MODE=true`** di `.env` — **dead config.** Kode PHP tidak membaca ini. Sistem selalu panggil Gemini API sungguhan.

## Reference Repos (Cloned — Jangan Hapus)

Dua repositori sebagai pattern source-of-truth untuk business logic. **Hanya adopsi UX/component patterns, jangan copy CSS/colors.**

### PasPapan — Primary Reference (Face, GPS, Risk Scoring, Approval, Termination)
- Path: `/home/merger/PasPapan/`
- 80 models, 22 services, 58 Blade components, 1.642 lines app.js, 6.623 lines CSS (251 Blade files)

| Pattern | File | Port Ke |
|:--------|:-----|:--------|
| Attendance risk scoring (14 faktor, score 0-100) | `app/Support/AttendanceRiskScorer.php` | HRConnect GeofenceService + FaceService |
| Anti-replay QR (HMAC-SHA256 + nonce + TTL jitter) | `app/Support/DynamicBarcodeTokenService.php` | HRConnect clock-in QR |
| Device attendance lock (`lockForUpdate()` + radius) | `app/Services/Attendance/DeviceAttendanceService.php` | HRConnect AttendanceController |
| Approval lock (`lock()` + `ensureReviewable()`) | `app/Support/ReimbursementApprovalService.php` | HRConnect ReimbursementService |
| Lifecycle termination (bukan direct status change) | `app/Support/EmployeeLifecycleService.php` | HRConnect EmployeeTerminationService |
| Offboarding checklist (4-task + dependency chain) | `app/Support/HrChecklistService.php` | HRConnect termination flow |
| Face enrollment overlay (992 lines canvas guide) | `app/Livewire/User/FaceEnrollment.php` + view | HRConnect face-registration.blade.php |
| Face verification scan (1.100+ lines Alpine) | `resources/views/livewire/user/scan.blade.php` | HRConnect clock-in.blade.php |
| Face model (separate table for embeddings) | `app/Models/FaceDescriptor.php` | HRConnect FaceDescriptor migration |
| JS utilities (SweetAlert2, Flatpickr, validation) | `resources/js/app.js` (1.642 lines) | HRConnect `resources/js/face-utils.js` |

### Quanta HRIS — Indonesian Payroll (Post-Skripsi)
- Path: `/home/merger/quanta-hris-laravel/`
- 54 views, Filament 3, 10 services payroll Indonesia

| Service | File | Logic |
|:--------|:-----|:------|
| Pph21Service | `app/Services/Pph21Service.php` | TER PMK 168/2023, lookup by bruto bracket |
| BpjsService | `app/Services/BpjsService.php` | Kesehatan 1%, JHT 2%, JP 1% + batas atas |
| LemburService | `app/Services/LemburService.php` | PP 35/2021, formula (gaji+tunjangan)/173 |
| PotonganService | `app/Services/PotonganService.php` | Alfa (per hari), terlambat (3 metode) |
| TunjanganService | `app/Services/TunjanganService.php` | 3 jenis tunjangan + 75% rule compliance |
| HitungGajiService | `app/Services/HitungGajiService.php` | Orchestrator semua komponen |
| Migrations + Seeders | `database/migrations/` | 3 tabel pajak (kategori_ter, golongan_ptkp, tarif_ter) |

### ship-ai-with-laravel — RAG Chat Reference (Sesi C)
- Path: `/home/merger/RAG-repo/ship-ai-with-laravel/`
- Pattern: Livewire minimal (4 public props) + Alpine.js SSE streaming via `fetch()` + `ReadableStream.getReader()`
- Port: `app/Livewire/KnowledgeBaseChat.php` + `resources/views/livewire/knowledge-base-chat.blade.php`
- SSE: `POST /api/v1/knowledgebase/chat-stream` → `StreamedResponse` with `TextDelta` events

### Other Repos — Low Priority
- `/home/merger/laravel-smarthr/` — UI component reference (141 views, 5 Livewire, 0 approval workflow)
- `/home/merger/hrms-livewire/` — Queue progress bar pattern (109 views, 22 Livewire, 0 services)
- `/home/merger/hris/` — Org structure hierarchy (React, no HRIS features)
- `/home/merger/RAG-repo/laravel-ragkit/` — Reusable Laravel RAG package (multi-provider)
- `/home/merger/RAG-repo/laravelrag/` — Tutorial demo RAG Laravel

## CI

`develop`/`main`/`master`/`workos` push + PR trigger:
- **lint.yml**: PHP 8.4, `composer lint` (Pint). Commit otomatis di-comment out.
- **tests.yml (sqlite)**: PHP 8.5, `npm i` (ignore-scripts), `composer install --optimize-autoloader`, `./vendor/bin/pest`.
- **tests.yml (postgres)**: PHP 8.5, `pgvector/pgvector:pg16` service, `cp .env.testing.pgsql.example .env`, `./vendor/bin/pest --configuration=phpunit.pgsql.xml`.

## RAG Knowledge Base (Sesi C)

### Arsitektur
- **Backend**: `KnowledgeBaseService` orchestrates RAG — embed via Gemini `text-embedding-004` (768D), vector search top-5 chunks via pgvector cosine distance, generate answer via `HrKnowledgeBaseAgent` (Gemini 2.5 Flash, structured output `answer` + `confidence`). Fallback: pg_trgm keyword search (sync only).
- **Sync API**: `POST /api/v1/knowledgebase/chat` → JSON `{answer, sources, confidence}`.
- **Streaming API**: `POST /api/v1/knowledgebase/chat-stream` → SSE `text/event-stream` with `data: {"text":"..."}` events.
- **Upload**: `POST /api/v1/knowledgebase/upload` → PDF → chunking → async `ProcessKnowledgeBaseEmbedding` job.
- **Web UI**: Livewire + Alpine SSE streaming via `fetch()` + `ReadableStream.getReader()`.

### SSE Protocol
```
data: {"text":"Cuti"}
data: {"text":" tahunan"}
data: {"conversation_id":"abc123","sources":[...]}
data: [DONE]
```

### Gotchas
- **`RAG_MOCK_MODE=true`** dead config — sistem selalu panggil Gemini sungguhan.
- **pg_trgm fallback** hanya di sync `chat()`, bukan streaming. Kalau streaming gagal, frontend harus fallback ke sync.
- **Model `gemini-2.5-flash`** butuh tier bayar. Alternatif: `gemini-2.0-flash` (free tier).
- **Conversation memory**: `conversationId` dikirim balik tapi tidak disimpan ke DB. Setiap chat baru = konteks fresh.
