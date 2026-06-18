# Task Tracker — HRConnect Backend

> **Source of truth** untuk progress backend.
> Last updated: 2026-06-17 (Phase 2 Core Fixes — C-1, H-1/H-2, H-3/H-4, H-5, H-7, H-8, H-9, H-12, M-7/M-8 fixed; H-6/A-3 partial. Focused suites: KnowledgeBase 18, Attendance 16, Approval/Leave/Overtime 26, PayrollExport 13, Approval controller 7, API focused 83, Endpoint/Attendance 51, OvertimeService 3 passed.)

---

## 🔴 CRITICAL BUGS (P0) — 8 items ✅ ALL DONE

| # | Bug | File | Est. | Status |
|---|-----|------|------|--------|
| B-1 | **Payroll regeneration data loss** — `forceDelete()` hapus adjustments + PDF path. Fix: UPDATE existing DRAFT instead of DELETE+CREATE | `PayrollCalculatorService.php:465` | ~3h | ✅ |
| B-2 | **Job dispatch before transaction commit** — `GeneratePayslipPdfJob` & `ProcessKnowledgeBaseEmbedding` run on uncommitted data. Fix: Add `->afterCommit()` + move dispatch outside transaction | `PayrollObserver.php:14`<br>`KnowledgeBaseService.php:176` | ~1h | ✅ |
| B-3 | **Reimbursement period filter missing** — Mengambil SEMUA approved tanpa filter bulan → salary inflation. Fix: Add `whereYear/Month('expense_date')` | `PayrollCalculatorService.php:409-413` | ~30m | ✅ |
| B-4 | **Unpaid leave not deducted** — PRD §11.3.1 violated. Fix: Query approved unpaid leaves, subtract from countWorkingDays | `ManagesWorkDays::countWorkingDays()` | ~2h | ✅ |
| B-5 | **Missed clock detection missing** — PRD §6.2 status `missed_clock_in/out` not detected. Fix: Create cron command, schedule 00:01 daily | `DetectMissedClockCommand` | ~1.5h | ✅ |
| B-6 | **Half-day overlap broken** — Same-date morning+afternoon leaves rejected. Fix: Allow different `day_type` on same date in `hasOverlap()` | `Leave::hasOverlap()` | ~1h | ✅ |
| B-7 | **Holiday cache invalidation broken** — `isDirty('date')` in `saved` hook always false. Fix: Change to `wasChanged('date')`, invalidate both old+new year | `HolidayObserver.php:48` | ~30m | ✅ |
| B-8 | **Reimbursement link race condition** — No transaction + lock → same reimbursement linked to 2 payrolls. Fix: Wrap in `DB::transaction()` with `lockForUpdate()` | `ReimbursementService.php:82-101` | ~1h | ✅ |

---

## 🟠 HIGH PRIORITY (P1) — 8 items ✅ ALL DONE

| # | Bug | File | Est. | Status |
|---|-----|------|------|--------|
| B-9 | **Job failed handlers missing** — Payroll/PDF/Embedding jobs fail silently. Fix: Add `failed()` method to rollback/log/notify | `GenerateEmployeePayrollJob`<br>`GeneratePayslipPdfJob`<br>`ProcessKnowledgeBaseEmbedding` | ~2h | ✅ |
| B-10 | **Orphan overtime paid** — Business decision: approved manual overtime without `attendance_id` must be paid. Fix: payroll includes all approved overtime in period; regression test added. | `PayrollCalculatorService.php`<br>`SecurityRegressionTest.php` | ~15m | ✅ |
| B-11 | **Overnight overtime bug** — Shift/service/API paths now handle overnight by assigning `end = end->addDay()` and allowing next-day end time. Regression test added. | `Overtime::durationHours()`<br>`OvertimeService.php`<br>`StoreOvertimeRequest.php`<br>`RegressionModelTest.php` | ~30m | ✅ |
| B-12 | **Inactive manager approver** — Resigned manager receives approval. Fix: Check `status=ACTIVE` in `getDirectApprover()` | `Employee::getDirectApprover():276` | ~30m | ✅ |
| B-13 | **Missing transaction: initializeBalance** — Bulk insert without transaction. Fix: Wrap in `DB::transaction()` with lock | `LeaveService.php:124-166` | ~1h | ✅ |
| B-14 | **Missing transaction: carryForward** — Loop updateOrCreate without lock. Fix: Wrap in transaction, lock previous + target balances | `LeaveService.php:177-211` | ~1h | ✅ |
| B-15 | **Missing transaction: deleteKnowledgeBase** — Bulk delete can fail midway. Fix: Wrap in `DB::transaction()` | `KnowledgeBaseService.php:196-205` | ~30m | ✅ |
| B-16 | **Missing transaction: CreateNewUser** — User creation not atomic. Fix: Wrap in `DB::transaction()` | `CreateNewUser.php:20-32` | ~15m | ✅ |

---

## 🟡 MEDIUM PRIORITY (P2) — 12 items ✅ ALL DONE

| # | Bug | File | Est. | Status |
|---|-----|------|------|--------|
| B-17 | **Approval double-processing** — Approval row is now locked/reloaded inside transaction; stale status guard retained. Regression test covers level sequencing; concurrency still needs PostgreSQL integration coverage. | `ApprovalService.php` | ~30m | ✅ |
| B-18 | **Payroll terminal state violation** — PUBLISHED can become DRAFT. Fix: Add `updating` observer, block terminal state changes | `Payroll::booted()` | ~1h | ✅ |
| B-19 | **Reimbursement terminal state violation** — PAID/APPROVED/REJECTED can revert. Fix: Add `updating` observer | `Reimbursement::booted()` | ~1h | ✅ |
| B-20 | **Leave deletion no refund** — Refund now only applies to fully `APPROVED` leave and uses `LeaveBalance::refund()`. Regression test ensures `APPROVED_L1` does not refund. | `Leave::booted()`<br>`RegressionModelTest.php` | ~1h | ✅ |
| B-21 | **Division by zero: hourly rate** — If `monthly_working_hours=0` → crash. Fix: Guard before division | `PayrollCalculatorService.php:96` | ~15m | ✅ |
| B-22 | **Silent null fail: quota deduct** — `$balance?->deduct()` nullable. Fix: Throw if balance not found | `ApprovalService.php:86` | ~15m | ✅ |
| B-23 | **Error suppression @mkdir** — PDF/Export mkdir errors hidden. Fix: Remove `@`, check return value, throw exception | `PayslipPdfService.php:38`<br>`PayrollExportService.php:244` | ~30m | ✅ |
| B-24 | **Empty vector array** — `formatVector([])` creates invalid PostgreSQL vector. Fix: Guard empty array | `EmbeddingService.php:199` | ~15m | ✅ |
| B-25 | **No try-catch: payroll job** — Service exception crashes job. Fix: Add `failed()` handler | `GenerateEmployeePayrollJob.php` | ~30m | ✅ |
| B-26 | **No try-catch: PDF job** — PDF generation OOM/error crashes. Fix: Add `failed()` handler | `GeneratePayslipPdfJob.php` | ~30m | ✅ |
| B-27 | **File upload unchecked** — `store()` disk-full error not caught. Fix: Wrap in try-catch | `ReimbursementService.php:28-29` | ~30m | ✅ |
| B-28 | **Tax rate array unsafe** — `$taxRate['rate']` can be null pointer. Fix: Validate after `first()` | `PayrollCalculatorService.php:158-161` | ~30m | ✅ |

---

## 🟢 LOW PRIORITY (P3 — Backlog) — 12 items ⏳ PENDING / NEED REVIEW

| # | Issue | File | Est. | Status |
|---|-------|------|------|--------|
| B-29 | **Circular manager reference** — No prevention of `parent_id=id`. Fix: Validation rule + DB CHECK constraint | Employee validation + migration | ~1h | ⏳ |
| B-30 | **Future attendance** — No validation prevents future date. Fix: Add CHECK `date <= CURRENT_DATE` | Attendance migration | ~30m | ⏳ |
| B-31 | **LeaveBalance corruption** — CHECK constraint updated in original development migration to `used <= quota + carry_forward` (guarded for pgsql). | Migration | ~30m | ✅ |
| B-32 | **Null propagation guards** — `join_date?->diffInMonths()` can null. Fix: Explicit guard before diff | `PayrollCalculatorService.php:231,295` | ~30m | ⏳ |
| B-33 | **Overtime fractional rounding** — 0.3h pays 0.3×1.5x (legal gray area). Fix: Round to 0.5h | `PayrollCalculatorService.php:118` | ~1h | ⏳ |
| B-34 | **WFA late_minutes misleading** — Stored but not penalized. Fix: Set to 0 or NULL for WFA | `AttendanceService.php:88` | ~30m | ⏳ |
| B-35 | **Carry-forward all types** — Should only "Cuti Tahunan". Fix: Filter by eligibility flag | `LeaveService::carryForward()` | ~1h | ⏳ |
| B-36 | **TER K/3 label for 4+ kids** — Should show "K/3+" for clarity. Fix: UX improvement in label | `PayrollCalculatorService.php:31` | ~30m | ⏳ |
| B-37 | **PIN fallback rate limit** — No limit on consecutive PIN-only days. Fix: Add manager approval after N days | `AttendanceService::resolveVerification()` | ~2h | ⏳ |
| B-38 | **Employee no position** — Overtime calc returns 0 silently. Fix: Throw exception like payroll | `PayrollCalculatorService.php:92` | ~15m | ⏳ |
| B-39 | **Resign < join date** — No validation. Fix: Guard in pro-rated calc | `PayrollCalculatorService.php:52` | ~15m | ⏳ |
| B-40 | **WFA without branch** — Policy unclear. Fix: Document rule or enforce branch assignment | `AttendanceService::clockIn():50-53` | ~30m | ⏳ |

---

## 🔥 NEW AUDIT FINDINGS (2026-06-10) — 11 items ✅ ALL DONE

| # | Bug | File | Priority | Status |
|---|-----|------|----------|--------|
| B-41 | **API 2FA accepts any correctly-shaped code** — Fixed with Fortify `TwoFactorAuthenticationProvider`, encrypted secret decrypt, `hash_equals()` recovery code, and `replaceRecoveryCode()` after use. Invalid TOTP and one-time recovery code tests added. | `AuthController.php`<br>`SanctumApiTest.php` | P0 | ✅ |
| B-42 | **Employee update missing authorization** — Fixed route middleware to `manage_employees`, added policy authorization, and removed self-service update from employee management endpoint. Regression test added. | `routes/api.php`<br>`EmployeeController.php`<br>`EmployeePolicy.php`<br>`ControllerHttpTest.php` | P0 | ✅ |
| B-43 | **Employee detail leaks hidden encrypted PII** — General endpoint no longer returns sensitive PII. Dedicated `/employees/{employee}/pii` endpoint requires `manage_employees` and writes Spatie activity log. Regression tests added. | `EmployeeController.php`<br>`EmployeePiiResource.php`<br>`ControllerHttpTest.php` | P0 | ✅ |
| B-44 | **pgvector dependency/cast missing** — Added `pgvector/pgvector`, disabled vendor migration auto-discovery for SQLite compatibility, registered schema macro manually, and switched model cast/scope to package API. | `composer.json`<br>`composer.lock`<br>`AppServiceProvider.php`<br>`Employee.php`<br>`FaceRecognitionService.php` | P0 | ✅ |
| B-45 | **Approval level sequencing missing** — Fixed by rejecting higher-level approval until previous levels are approved. Regression tests added. | `ApprovalService.php`<br>`ApprovalServiceTest.php`<br>`ControllerHttpTest.php` | P1 | ✅ |
| B-46 | **Overtime create + approval workflow not atomic** — Fixed by moving create + approval workflow into `OvertimeService` transaction and routing controller store through service. | `OvertimeService.php`<br>`OvertimeController.php` | P1 | ✅ |
| B-47 | **Overtime description nullable but service requires key** — Fixed by requiring `description` in request validation. | `StoreOvertimeRequest.php` | P1 | ✅ |
| B-48 | **Shift overnight duration broken under CarbonImmutable** — Fixed to assign `$end = $end->addDay()`. Regression test added. | `Shift.php`<br>`RegressionModelTest.php` | P2 | ✅ |
| B-49 | **API password-expiry middleware missing** — **Cancelled by architecture decision.** PWA/API tokens intentionally are not blocked by password expiry to avoid disrupting mobile/PWA usage. Password expiry remains enforced on web routes only. Regression test documents that expired-password API tokens remain usable by design. | `routes/api.php`<br>`CheckPasswordExpired.php`<br>`SecurityRegressionTest.php` | P2 | 🚫 |
| B-50 | **Stateful Sanctum logout can error** — Fixed by guarding `TransientToken` before deleting current access token. | `AuthController.php` | P2 | ✅ |
| B-51 | **Contract-end termination parses unvalidated date** — Fixed with inline validation before `CarbonImmutable::parse()`, returning 422 for invalid dates instead of 500. | `EmployeeTerminationController.php` | P2 | ✅ |

---

## 🧨 FOLLOW-UP AUDIT BACKLOG (2026-06-10) — 2 items ✅ ALL DONE

| # | Bug | File | Priority | Status |
|---|-----|------|----------|--------|
| B-52 | **Leave overlap race condition** — Fixed by moving `Leave::hasOverlap()` inside the transaction after locking the employee row and, for quota leave, the relevant `LeaveBalance`. Regression test added. PostgreSQL parallel test remains covered by T-10. | `LeaveService.php`<br>`LeaveAndOvertimeTest.php` | P1 | ✅ |
| B-53 | **Payroll first-create race condition** — Fixed with `Cache::lock("payroll:generate:{employee_id}:{period}", 120)` around generation and controlled `BusinessRuleException` if lock is unavailable. Regression tests added. PostgreSQL parallel test remains covered by T-10. | `PayrollCalculatorService.php`<br>`SecurityRegressionTest.php`<br>`PayrollCalculatorCoreTest.php` | P1 | ✅ |

---

## 🔍 COMPREHENSIVE BACKEND AUDIT (2026-06-17) — 33 actionable findings

### 🔴 CRITICAL (4)

| # | Finding | File | Priority | Status |
|---|---------|------|----------|--------|
| C-1 | **KnowledgeBaseService hardcoded `knowledgeable_id=1`** — Fixed: upload now requires a valid owner and API upload passes authenticated user as owner. Missing owner throws `ValidationException`; no fallback morph ID is used. | `KnowledgeBaseService.php`<br>`KnowledgeBaseController.php`<br>`KnowledgeBaseServiceTest.php` | P0 | ✅ |
| C-2 | **`KnowledgeBase::$casts['embedding'] = 'vector'` has no SQLite fallback** — Fixed with `App\Casts\PgVector`, preserving pgvector behavior on PostgreSQL and string fallback on SQLite. | `app/Casts/PgVector.php`<br>`app/Models/KnowledgeBase.php` | P0 | ✅ |
| C-3 | **`Employee::$casts['face_embedding']` crashes on SQLite** — Fixed with `App\Casts\PgVector`; focused EmployeeTermination test passes. | `app/Casts/PgVector.php`<br>`app/Models/Employee.php` | P0 | ✅ |
| C-6 | **PostgreSQL integration suite tidak jalan di CI** — Fixed by adding a dedicated PostgreSQL job using `pgvector/pgvector:pg16` and required extensions. | `.github/workflows/tests.yml` | P0 | ✅ |

### 🟠 HIGH (13)

| # | Finding | File | Priority | Status |
|---|---------|------|----------|--------|
| H-1 | **`logBypass()` before transaction (clock-in)** — Fixed: PIN bypass logging now runs inside the clock-in transaction after attendance creation succeeds. | `AttendanceService.php` | P1 | ✅ |
| H-2 | **`logBypass()` before transaction (clock-out)** — Fixed: PIN bypass logging now runs inside the clock-out transaction after attendance update succeeds. | `AttendanceService.php` | P1 | ✅ |
| H-3 | **`createApprovalWorkflow()` nested in Leave transaction** — Fixed: `ApprovalService::createApprovalWorkflow()` now reuses an active transaction instead of opening a nested transaction. | `ApprovalService.php`<br>`LeaveService.php` | P1 | ✅ |
| H-4 | **`createApprovalWorkflow()` nested in Overtime transaction** — Fixed by the same transaction-level guard in `ApprovalService`. | `ApprovalService.php`<br>`OvertimeService.php` | P1 | ✅ |
| H-5 | **PayrollExportService Writer tanpa try-finally** — Fixed with shared `writeXlsx()` helper that opens the writer once and always closes it in `finally`. | `PayrollExportService.php` | P1 | ✅ |
| H-6 | **5 dead FormRequest classes (`authorize(): false`, never injected)** — Partial fix: all 5 `List*Request` classes now authorize authenticated users and define rules; active list endpoints now inject Attendance/Leave/Overtime/Reimbursement requests. Remaining: decide whether to add a KnowledgeBase list endpoint or remove `ListKnowledgeBaseRequest`. | 5 FormRequest files + 4 controllers | P1 | 🚧 **PARTIAL** |
| H-7 | **N+1 query in ApprovalController::index()** — Fixed: pending approvals now eager-load morph-specific `employee` relations via `morphWith()`. | `ApprovalController.php` | P1 | ✅ |
| H-8 | **AttendanceController::index() uses inline validation** — Fixed: `AttendanceController::index()` now injects `ListAttendanceRequest`. | `AttendanceController.php`<br>`ListAttendanceRequest.php` | P1 | ✅ |
| H-9 | **destroy() cancellation consistency** — Fixed for multi-write cancellation: Leave/Overtime now wrap `status update + delete()` in `DB::transaction()`. Reimbursement remains direct delete because enum has no `cancelled` state. | `LeaveController`<br>`OvertimeController` | P1 | ✅ |
| H-10 | **Default phpunit.xml missing `CIPHERSWEET_KEY`** — Fixed with deterministic test-only CipherSweet key in `phpunit.xml`. | `phpunit.xml` | P1 | ✅ |
| H-11 | **Testing-strategy.md references 36+ non-existent test files** — Dokumentasi tidak sinkron dengan codebase. Fix: audit and update doc. | `docs/testing/testing-strategy.md` | P1 | ⏳ |
| H-12 | **OvertimeService lacks direct service tests** — Fixed: direct service tests cover creation, overnight duration, approval workflow call, and cancellation soft delete. | `tests/Unit/Services/OvertimeServiceTest.php` | P1 | ✅ |
| H-13 | **Payroll PostgreSQL/concurrency coverage gap** — `generatePayroll()` has direct SQLite/unit coverage, but PostgreSQL locking/concurrency scenarios still need integration coverage. | `tests/Integration/Postgres/*` | P1 | ⏳ |

### 🟡 MEDIUM / DECISION ITEMS (16)

| # | Finding | File | Priority | Status |
|---|---------|------|----------|--------|
| M-1 | **`AttendanceService::clockIn()` double check `hasClockedInToday()`** — Checked before and inside transaction. Remove redundant check. | `AttendanceService.php` | P2 | ⏳ |
| M-2 | **`logBypass()` uses `request()->ip()` directly** — Works in normal HTTP flow, but should tolerate CLI/queue/test contexts with null-safe request/IP handling. | `AttendanceService.php` | P2 | ⏳ |
| M-3 | **`FaceRecognitionService::nearestNeighbors()` QueryException tidak ditangkap** — pgvector outage → uncaught crash. Wrap in try-catch. | `FaceRecognitionService.php:64` | P2 | ⏳ |
| M-4 | **GeminiClient `sleep()` blocking retry** — Harusnya `Http::retry()` non-blocking. | `GeminiClient.php:202` | P2 | ⏳ |
| M-5 | **Reimbursement status update ke PAID tanpa `lockForUpdate()`** — Race condition risk. | `PayrollCalculatorService.php:536` | P2 | ⏳ |
| M-6 | **EmployeeTerminationService activity log inside transaction** — Usually rolls back with the same DB connection, but side-effect timing should be reviewed and made explicit. | `EmployeeTerminationService.php:81` | P2 | ⏳ |
| M-7 | **ClockInRequest `embedding` tanpa validasi** — Fixed: `embedding` must be a 128-element numeric array with values between -1.5 and 1.5. | `ClockInRequest.php`<br>`EndpointsTest.php` | P2 | ✅ |
| M-8 | **ClockOutRequest — same missing validation** — Fixed with the same 128D numeric array validation and regression test. | `ClockOutRequest.php`<br>`EndpointsTest.php` | P2 | ✅ |
| M-9 | **LeaveController::store() tidak ada `$this->authorize('create')`** | `LeaveController.php` | P2 | ⏳ |
| M-11 | **EmployeeController::store() — `phone` tanpa format regex, `nik` tanpa `digits:16`** | `StoreEmployeeRequest.php` | P2 | ⏳ |
| M-12 | **ProfileController update should use explicit field mapping** — Current `UpdateProfileRequest` already whitelists safe fields; explicit mapping would make the self-service boundary clearer. | `ProfileController.php:62` | P2 | ⏳ |
| M-13 | **Sanctum token `expiration => null` decision** — Intentional for PWA/API token reuse unless product requires forced token expiry. Document final decision. | `config/sanctum.php` | Decision | ⏳ |
| M-14 | **Store endpoints (`/leave`, `/overtime`, `/reimbursement`) tidak ada rate limiting** | `routes/api.php` | P2 | ⏳ |
| M-15 | **Fortify `registration()` enabled decision** — Confirm whether self-registration is intentional. If HR-only employee creation is required, disable in production. | `config/fortify.php:147` | Decision | ⏳ |
| M-16 | **Employee `created_by`, `updated_by`, `face_embedding`, `pin` di `#[Fillable]`** — Seharusnya hanya via service. | `app/Models/Employee.php` | P2 | ⏳ |
| M-17 | **Employee `bank_account_number` blind-index decision** — Encrypted field has no blind index. Add one only if the product requires searchable bank-account lookups. | `app/Models/Employee.php` | Decision | ⏳ |

---

## 📦 ARCHITECTURAL IMPROVEMENTS — 7 items ⚠️ IN PROGRESS / NEEDS CLEANUP

| # | Task | Files | Est. | Status |
|---|------|------|------|--------|
| A-1 | **Create missing Services** — Extract controller logic to OvertimeService, ProfileService | `OvertimeService.php`<br>`ProfileService.php` | ~4h | ✅ |
| A-2 | **Create API Resources** — Standardize JSON response for all entities. Resource files exist, but several controllers still use manual `format*()` response methods. | 15 Resource classes + API controllers | ~6h | 🚧 **IN PROGRESS** |
| A-3 | **Create missing FormRequests** — Active index validation extracted to `ListAttendanceRequest`, `ListLeaveRequest`, `ListOvertimeRequest`, `ListReimbursementRequest`. Remaining: Employee index still inline; KnowledgeBase has a request but no list endpoint decision yet. | `ListAttendanceRequest`, `ListLeaveRequest`, `ListOvertimeRequest`, `ListReimbursementRequest`, `ListKnowledgeBaseRequest`, controllers | ~2h | 🚧 **IN PROGRESS** |
| A-4 | **Create missing Policies** — Already exist: OvertimePolicy, ReimbursementPolicy, Approval via Approvable trait | (8 policies already exist) | ~3h | ✅ |
| A-5 | **Standardize authorization** — Add Middleware vs Policy documentation to AGENTS.md | `AGENTS.md` | ~2h | ✅ |
| A-6 | **Add eager loading** — Prevent N+1 in PayrollController, ReimbursementController, OvertimeController index() | 3 controller `index()` methods | ~1h | ✅ |
| A-7 | **Extract hard-coded values** — Register service singletons in AppServiceProvider | `AppServiceProvider::register()` | ~1h | ✅ |

## 🧪 TESTING & FRONTEND — 15 items ⏳ 10 PENDING, 2 PARTIAL, 3 CANCELLED (T-13–T-15)

| # | Task | Scope | Est. | Status |
|---|------|-------|------|--------|
| T-1 | **Face Recognition frontend** — Livewire ClockIn component + face-api.js + camera UI | Frontend integration | TBD | ⏳ |
| T-2 | **Security hardening** — CORS `*`→`FRONTEND_URL`, session encryption, dead code, JSON errors | Multiple files | ~40m | ⏳ |
| T-3 | **Notification tests** — Test `toMail()` + `toArray()` for 8 notification classes | Test files | ~2h | ⏳ |
| T-4 | **Observer tests** — PayrollObserver, TaxConfigObserver, BpjsConfigObserver, HolidayObserver | Test files | ~1.5h | ⏳ |
| T-5 | **AttendanceController tests** — clockIn, clockOut, today, index, approveWfa | Test files | ~2h | ⏳ |
| T-6 | **LeaveController + OvertimeController tests** | Test files | ~1.5h | ⏳ |
| T-7 | **AuthController 2FA + FaceController + ProfileController tests** | Test files | ~1h | ⏳ |
| T-8 | **Service + Job tests** — PayslipPdfService, GeneratePayslipPdfJob, EmbeddingService | Test files | ~2h | ⏳ |
| T-9 | **Bug regression tests** — Write tests for critical bugs (B-1 to B-8) to prevent regression | Test files | ~3h | ⏳ |
| T-10 | **PostgreSQL integration test suite** — Dedicated pgsql suite is unblocked and passes locally (7 tests). CI PostgreSQL job added. Remaining: expand coverage for payroll/approval concurrency and additional `lockForUpdate()` scenarios. | `phpunit.pgsql.xml`<br>`.env.testing.pgsql.example`<br>`tests/Integration/Postgres/*`<br>`.github/workflows/tests.yml` | TBD | 🚧 **PARTIAL** |
| T-11 | **Scramble/OpenAPI contract tests** — Generate OpenAPI docs in CI/test flow and assert representative `/api/v1/*` paths, bearer security on protected routes, public health/login routes, and docs alignment for overtime/reimbursement request fields. | `config/scramble.php`<br>`tests/Feature/OpenApi/*` | TBD | ⏳ |
| T-12 | **CipherSweet + PII integration tests** — Partial PostgreSQL coverage already exists in `PostgresEnvironmentTest.php` for encrypted raw values + `whereBlind()` lookups. Remaining: dedicated test file and PII reveal endpoint audit logging coverage. | `tests/Integration/Postgres/PostgresEnvironmentTest.php`<br>`tests/Integration/Postgres/CipherSweetTest.php`<br>`tests/Feature/Api/*` | TBD | 🚧 **PARTIAL** |
| T-13 | **PWA Password Expiry Testing** — Cancelled by architecture decision (B-49). PWA/API tokens intentionally not blocked by password expiry. | Test files | ~2h | 🚫 |
| T-14 | **PWA Authentication Middleware Testing** — Cancelled by architecture decision (B-49). Same rationale. | Test files | ~2h | 🚫 |
| T-15 | **PWA Route Whitelist Testing** — Cancelled by architecture decision (B-49). Same rationale. | Test files | ~1h | 🚫 |

---

## 🛠️ DEVELOPMENT INFRASTRUCTURE — 2 items ⚠️ PARTIAL

| # | Task | Scope | Est. | Status |
|---|------|-------|------|--------|
| D-1 | **Testing environment split** — Dual strategy implemented and unblocked: SQLite fast suite + PostgreSQL integration suite. Files exist: `phpunit.pgsql.xml`, `.env.testing.pgsql.example`, `tests/Integration/Postgres/PostgresEnvironmentTest.php`, `composer.json` script `test:pgsql`, plus PostgreSQL CI job. | `phpunit.xml`<br>`phpunit.pgsql.xml`<br>`.env.testing.pgsql.example`<br>`.github/workflows/tests.yml`<br>`docs/testing/testing-strategy.md` | TBD | ✅ |
| D-2 | **Scramble docs regeneration workflow** — Add a reliable command/test path to regenerate/export `api.json`, clear stale docs safely, and avoid `optimize:clear` failures when DB cache points to an unavailable PostgreSQL instance. | `config/scramble.php`<br>`composer.json` scripts<br>CI workflow | TBD | ⏳ |

---

## 📊 PROGRESS SUMMARY

| Sprint | Tasks | Status | Effort |
|--------|-------|--------|--------|
| **Sprint 1** — Critical Stabilization | B-1 to B-8 (P0) | ✅ COMPLETED | ~11h |
| **Sprint 2** — Production Readiness | B-9 to B-16 (P1) | ✅ COMPLETED | ~6h |
| **Sprint 3** — Exception Handling | B-17 to B-28 (P2) | ✅ COMPLETED | ~7h |
| **Sprint 4** — Clean Architecture | A-1 to A-7 | ⚠️ PARTIAL CLEANUP REQUIRED (A-2, A-3) | ~19h |
| **Sprint 5** — Test Coverage | T-3 to T-15 | ⚠️ 3 CANCELLED (T-13 to T-15 by design), T-10/T-12 PARTIAL, 10 PENDING | ~13.5h+ |
| **Sprint 6** — Polish & Tech Debt | B-29 to B-40, T-1, T-2 | ⏳ PENDING | ~9h+ |
| **Sprint 7** — Audit Fixes | B-41 to B-53 + follow-ups B-10/B-11/B-17/B-20/B-31/A-2/A-3 | ⚠️ MOSTLY DONE (A-2, A-3 pending) | TBD |
| **Sprint 8** — Test Infrastructure | D-1, D-2, T-10, T-11, T-12 | ⚠️ PARTIAL (D-1 ✅ DONE, T-10/T-12 🚧 PARTIAL, T-11 ⏳ PENDING) | TBD |
| **Sprint 9 (NEW)** — Comprehensive Audit | C-1/C-2/C-3/C-6, H-1 to H-13, M-1 to M-17 (excluding removed false positives M-10/M-18) | ⚠️ PARTIAL (C-1/C-2/C-3/C-6/H-1/H-2/H-3/H-4/H-5/H-7/H-8/H-9/H-10/H-12/M-7/M-8 done, H-6 partial) | TBD |

**Total original bugs fixed: 29/40 confirmed done, 11 original P3 pending. Comprehensive audit (2026-06-17): 33 actionable findings — 4 Critical, 13 High, 16 Medium/decision items. Phase 1 completed: C-2, C-3, C-6, H-10 fixed; Phase 2 started: C-1, H-1, H-2, H-3, H-4, H-5, H-7, H-8, H-9, H-12, M-7, M-8 fixed; H-6/A-3 partial. PostgreSQL suite passes locally (7 tests). Latest focused suites: KnowledgeBase 18 passed, Attendance 16 passed, Approval/Leave/Overtime 26 passed, PayrollExport 13 passed, Approval controller 7 passed, API focused 83 passed, Endpoint/Attendance 51 passed, OvertimeService 3 passed.**

---

## ✅ COMPLETED TASKS (Historical Reference)

Phase A–E (35 tasks) + recent completions:
- ✅ #1: PDF engine swap (Dompdf)
- ✅ #2: Blade payslip view
- ✅ #3: Archival pattern (PayslipPdfService)
- ✅ #4: Queue integration (GeneratePayslipPdfJob + Observer)
- ✅ #5: Download endpoint with policy
- ✅ #6: Controller HTTP tests
- ✅ #7: PHPStan level 2→5 upgrade
- ✅ #9: API documentation regeneration
- ✅ #10: Endpoint flow descriptions
- ✅ #11: Study cases documentation
- ✅ #12: CipherSweet AGENTS.md documentation
- ✅ #13: Form request DB schema alignment
- ✅ #14: Security hardening (deferred to Sprint 6)

---

## 📝 USAGE NOTES

**Status Legend:**
- ⏳ **Pending** — Not started
- 🚧 **In Progress** — Currently working
- ✅ **Done** — Completed & tested
- ❌ **Blocked** — Waiting on dependency
- 🚫 **Cancelled** — Cancelled by architecture decision

**Bug Priority Rules:**
- 🔴 P0: Drop everything, fix immediately (financial/data loss)
- 🟠 P1: Fix before production deploy (concurrency/stability)
- 🟡 P2: Fix in current sprint (defensive programming)
- 🟢 P3: Backlog, fix when time permits (UX/edge cases)

**Cross-Reference:**
- `B-X` = Bug from Deep Reliability Audit (2026-06-10)
- `A-X` = Architectural improvement
- `T-X` = Testing task
- `D-X` = Development infrastructure / workflow task
- `C-X` = Critical finding from Comprehensive Backend Audit (2026-06-17)
- `H-X` = High-priority finding from Comprehensive Backend Audit (2026-06-17)
- `M-X` = Medium-priority finding from Comprehensive Backend Audit (2026-06-17)
