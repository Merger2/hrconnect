# HRConnect — Agent Instructions

Enterprise HRIS (thesis project). Laravel 13 + Livewire 4 + Flux UI 2 + PostgreSQL (pgvector + pg_trgm + pgcrypto). Solo developer.

## Project Documentation

All plans live in `docs/`. Read `docs/INDEX.md` for the full index. Source-of-truth files:
- `docs/PRD.md` — PRD v3.0 (errata merged inline)
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

## Empty / Missing Folders (status as of v4.2)

These exist as folders but contain **no implementations** — agents often assume they're populated:
- `app/Observers/` — **EMPTY**. No HolidayObserver, TaxConfigObserver, BpjsConfigObserver, EmployeeObserver, AttendanceObserver yet (cache invalidation + default shift assignment broken because of this).
- `app/Policies/` — **EMPTY**. Zero authorization → IDOR vulnerability across all modules. `$user->can(...)` always false.
- `app/Http/Middleware/` — **EMPTY**. No `CheckPasswordExpired`, no `GoogleOAuthRefresh`.
- `app/Console/Commands/` — **EMPTY**. No `attendance:detect-chronic-late`, no `cache:warm`.
- `app/Notifications/` — **DOES NOT EXIST**. Folder must be created before adding notification classes.
- `routes/api.php` — **DOES NOT EXIST**. Sanctum not installed.
- `routes/web.php` — exists but has no module routes (only Fortify auth + settings + console).

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
- **NEVER query CipherSweet-encrypted columns directly.** Use blind index `*_hash`, e.g. `Employee::where('nik_hash', $nik)`. Encrypted columns: `nik`, `phone`, `npwp`, `bank_account_number` (Employee); `nik`, `phone`, `address` (FamilyDetail); `npwp` (Company).

## 3 Core Thesis Features (MUST NOT defer)

1. **Face Recognition** — face-api.js (FaceNet 128D) → pgvector. `FaceRecognitionService::verifyFace()` uses `<=>` cosine distance, threshold `MAX_DISTANCE = 0.15` (≈85% similarity). `employees.face_embedding` is `vector(128)` cast as `'vector'`. Fallback: Face → PIN → Manual (face failure NOT swallowed — CAT-017). Clock-in and clock-out have **separate** `verification_method` / `face_similarity_score` columns (ERR-004).
2. **GPS Geofencing + Anti-Fake GPS** — Haversine in `GeofenceService::validateLocation()`. Block if `is_mocked` or `accuracy > 100m` → `AntiFakeGPSException`. WFA mode skips GPS but requires note ≥20 chars + approval.
3. **KnowledgeBase RAG** — polymorphic morph `knowledgeable`, `embedding vector(768)` (NOT 1536). Embedding via Gemini `text-embedding-004`, Q&A via Gemini 2.5 Flash. Fallback to `pg_trgm` when AI down. Status/category enums exist.

## Known Critical Bugs (must check before touching these files)

| Bug | File:line | Fix |
|-----|-----------|-----|
| **B1** Payroll regenerate uses `delete()` | `PayrollCalculatorService.php:274` | Use `forceDelete()` — soft delete violates unique `(employee_id, period)` (CAT-002). |
| **B2** Payroll race condition | `PayrollCalculatorService.php:199` | Add `->lockForUpdate()` when checking existing payroll. |
| **B3** Leave quota deducted at submit, not approval | `LeaveService::applyLeave():80` | Move `$balance->deduct()` to `ApprovalService::approve()` after `isAllApproved()`. Add `LeaveBalance::refund()`. PRD §9.1. |
| **B4** `LeaveBalance::deduct()` allows negative | `LeaveBalance.php:50` | Add `if ($this->available() < $days) throw BusinessRuleException`. |
| **B5** `family_details_count` counts ALL relations | `Employee.php` + `PayrollCalculatorService::getTERCategory()` | Filter `relationship = CHILD` in `withCount`. Currently safe via fallback at line 109-111 but fragile. |
| **B6** Hardcoded `/22` working days | `PayrollCalculatorService.php:247` | Use `countWorkingDays($startOfMonth, $endOfMonth)`. |
| **B7** Overtime weekday flat 1.5x | `PayrollCalculatorService.php:99` | PRD §8.3: hour-1 = 1.5x, rest = 2x. |
| **B12** `FaceNotRegisteredException` not caught | `AttendanceService.php:55-66` | Catch + fallback to PIN per error-handling-strategy.md §1 Skenario 4. |

## Caching Gotchas

- **`CACHE_STORE=database`** — does **NOT** support `Cache::tags()`. Use `Cache::forget("key")` per-key, never `Cache::tags(['x'])->flush()`. caching-strategy.md §3 has examples that **will throw `BadMethodCallException`** with this driver.
- **Holiday cache is wasteful** (`PayrollCalculatorService.php:88`): key `holiday_{YYYY-MM-DD}` creates 30+ entries per payroll run. caching-strategy.md §2.6 prescribes `holidays:{year}` (1 entry, in-memory check). Refactor pending.
- **Stale cache risk** — `tax_configs`, `bpjs_configs`, `holiday_*`, `settings:{key}` use 1-day–1-month TTL but **no observers** invalidate them. Admin updates via Eloquent normal flow → cached value stale until TTL expires. Need `app/Observers/` populated (see Empty Folders).
- **Cache invalidation pattern** when modifying any cached model: call `Cache::forget("key")` in the same transaction or use observer. `CompanySetting::set()` does this manually but bypasses if you call `update()` directly.
- **Cache key naming** — mixed conventions exist (`holiday_{date}` vs `settings:{key}` vs `tax_configs`). Strategy doc prescribes colon separator: `module:identifier:key`.

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
- User uses `HasRoles` (Spatie), `SoftDeletes`, `TwoFactorAuthenticatable`.
- `password_changed_at` column exists; `CheckPasswordExpired` middleware **does not** (CAT-005 partial).
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
