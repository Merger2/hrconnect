# Task Tracker — HRConnect Backend

> **Source of truth** untuk progress backend.
> Last updated: 2026-06-10 (Audit backend/API — security P0 + selected P1/P2 fixes completed with focused regression tests; API/PWA password expiry intentionally not enforced per architecture decision; remaining backlog A-2, A-3, T-10 s.d. T-12, D-1, D-2, and original P3 items)

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

## 📦 ARCHITECTURAL IMPROVEMENTS — 7 items ⚠️ MOSTLY DONE / NEED CLEANUP

| # | Task | Files | Est. | Status |
|---|------|-------|------|--------|
| A-1 | **Create missing Services** — Extract controller logic to OvertimeService, ProfileService | `OvertimeService.php`<br>`ProfileService.php` | ~4h | ✅ |
| A-2 | **Create API Resources** — Standardize JSON response for all entities. Resource files exist, but several controllers still use manual `format*()` response methods. | 15 Resource classes + API controllers | ~6h | ⚠️ |
| A-3 | **Create missing FormRequests** — Extract manual validation from index() to List*Request. FormRequests exist, but some controllers still use raw `Request` / inline validation. | `ListAttendanceRequest`, `ListLeaveRequest`, `ListOvertimeRequest`, `ListReimbursementRequest`, `ListKnowledgeBaseRequest`, controllers | ~2h | ⚠️ |
| A-4 | **Create missing Policies** — Already exist: OvertimePolicy, ReimbursementPolicy, Approval via Approvable trait | (8 policies already exist) | ~3h | ✅ |
| A-5 | **Standardize authorization** — Add Middleware vs Policy documentation to AGENTS.md | `AGENTS.md` | ~2h | ✅ |
| A-6 | **Add eager loading** — Prevent N+1 in PayrollController, ReimbursementController, OvertimeController index() | 3 controller `index()` methods | ~1h | ✅ |
| A-7 | **Extract hard-coded values** — Register service singletons in AppServiceProvider | `AppServiceProvider::register()` | ~1h | ✅ |

---

## 🧪 TESTING & FRONTEND — 12 items ❌ NOT STARTED

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
| T-10 | **PostgreSQL integration test suite** — Add dedicated pgsql integration coverage for `pgvector`, `pg_trgm`, `pgcrypto`, CHECK constraints, `lockForUpdate()`, and payroll/approval concurrency. Keep SQLite fast suite unless a full pgsql suite is explicitly chosen. | `phpunit.pgsql.xml` or `.env.testing.pgsql`<br>`tests/Integration/Postgres/*` | TBD | ⏳ |
| T-11 | **Scramble/OpenAPI contract tests** — Generate OpenAPI docs in CI/test flow and assert representative `/api/v1/*` paths, bearer security on protected routes, public health/login routes, and docs alignment for overtime/reimbursement request fields. | `config/scramble.php`<br>`tests/Feature/OpenApi/*` | TBD | ⏳ |
| T-12 | **CipherSweet + PII integration tests** — Verify encrypted round-trip, `whereBlind()` lookups, raw database values not plaintext, and PII reveal endpoint audit logging under PostgreSQL-compatible setup. | `tests/Integration/Postgres/CipherSweetTest.php`<br>`tests/Feature/Api/*` | TBD | ⏳ |

---

## 🛠️ DEVELOPMENT INFRASTRUCTURE — 2 items ❌ NOT STARTED

| # | Task | Scope | Est. | Status |
|---|------|-------|------|--------|
| D-1 | **Testing environment split** — Define whether HRConnect uses dual test strategy (SQLite fast suite + PostgreSQL integration suite) or full PostgreSQL tests. If dual, add `phpunit.pgsql.xml` / `.env.testing.pgsql` and document commands. If full pgsql, update `phpunit.xml` after ensuring CI/local PostgreSQL is always available. | `phpunit.xml`<br>`phpunit.pgsql.xml`<br>`.env.testing.example`<br>`docs/testing/testing-strategy.md` | TBD | ⏳ |
| D-2 | **Scramble docs regeneration workflow** — Add a reliable command/test path to regenerate/export `api.json`, clear stale docs safely, and avoid `optimize:clear` failures when DB cache points to an unavailable PostgreSQL instance. | `config/scramble.php`<br>`composer.json` scripts<br>CI workflow | TBD | ⏳ |

---

## 📊 PROGRESS SUMMARY

| Sprint | Tasks | Status | Effort |
|--------|-------|--------|--------|
| **Sprint 1** — Critical Stabilization | B-1 to B-8 (P0) | ✅ COMPLETED | ~11h |
| **Sprint 2** — Production Readiness | B-9 to B-16 (P1) | ✅ COMPLETED | ~6h |
| **Sprint 3** — Exception Handling | B-17 to B-28 (P2) | ✅ COMPLETED | ~7h |
| **Sprint 4** — Clean Architecture | A-1 to A-7 | ⚠️ PARTIAL CLEANUP REQUIRED (A-2, A-3) | ~19h |
| **Sprint 5** — Test Coverage | T-3 to T-12 | ⏳ PENDING | ~13.5h+ |
| **Sprint 6** — Polish & Tech Debt | B-29 to B-40, T-1, T-2 | ⏳ PENDING | ~9h+ |
| **Sprint 7** — Audit Fixes | B-41 to B-53 + follow-ups B-10/B-11/B-17/B-20/B-31/A-2/A-3 | ⚠️ MOSTLY DONE (A-2, A-3 pending) | TBD |
| **Sprint 8** — Test Infrastructure | D-1, D-2, T-10, T-11, T-12 | ❌ NOT STARTED | TBD |

**Total original bugs fixed: 29/40 confirmed done, 11 original P3 pending. New audit findings: 12/13 fixed, 1 cancelled by design (B-49). Latest focused regression suite: 38 tests passed.**

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
