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
- CI: PostgreSQL CI commented out in `.github/workflows/tests.yml`

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
