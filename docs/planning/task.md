# Task Tracker — Backend 100% Completion

> Source of truth untuk pekerjaan backend aktif sebelum pindah ke frontend.
> Last updated: 2026-06-23 (Multi-agent audit — 5 agents covering PHPStan, security, models/migrations, tests, services. 33 hardcoded color violations, 2 audit corrections, 3 new code quality issues found. 620→711 lines.)
> Note: item completed lama dipadatkan berdasarkan status tracker sebelumnya dan spot-check kode/test; full re-audit pembuktian dilakukan melalui task P0/P1 di bawah.

## Status Legend

| Status | Meaning |
|---|---|
| ✅ | Done and verified in this tracker. |
| 🚧 | In progress / partial; see remaining work in the row. |
| ⏳ | Not started. |
| 🚫 | Deferred/cancelled by decision. |

## Status Snapshot

| Area | Status | Notes |
|---|---:|---|
| Backend infrastructure (app/) | ~98% | 31 models, 34 enums, 15 services, 14 controllers, 28 requests, 17 resources, 8 policies, 8 observers, 3 jobs, 9 commands. 193 PHP files, ~12K LOC. ✅ |
| API layer (routes/api.php) | ~99% | 51 endpoints at /api/v1, 13 controllers. Semua CRUD + auth + 2FA + exports. ✅ |
| Production hardening | ~92% | CipherSweet (Employee), Sanctum, rate limits (12), 2FA, password expiry 90d, PII masking. Permission drift ✅. PG migration guards ✅ |
| Frontend ESS (Employee Self-Service) | ~30% | 8 static Blade views (hardcoded data) — attendance (2), leaves (2), overtimes (2), reimbursements (2). 5 route views MISSING: payroll, approvals, KB, assets, loans. **0 Livewire components**. |
| Design System compliance | ~40% | Flux UI 2 clashes with cream DESIGN.md. **33 hardcoded color violations** across 8 Blade files (stone-*, zinc-*, neutral-*, gray-*, green-*). muted #6a6a6a on canvas #fffaf0 = 3.9:1 contrast (fail WCAG AA). |
| Security & encryption | ~90% | Employee PII encrypted via CipherSweet. FamilyDetail & Company.npwp encryption pending (post-MVP). Activity log on all models. ✅ |
| PWA readiness | ~60% | service-worker.js ✅, manifest.json ✅, icons ✅, install banner ✅. **offline.html MISSING**. |
| Test suite | 1,121 tests / 3,702 assertions (SQLite) + 28 tests / 61 assertions (PG) | 75 test files: 52 Feature, 21 Unit, 1 Integration/PG, 1 Arch. CI: lint + SQLite + PG. |

## Completed Summary

- Historical backend bugfixes `B-1` sampai `B-53` selesai atau dibatalkan by-design.
- Comprehensive audit critical/high findings mayoritas selesai: `C-1`, `C-2`, `C-3`, `C-6`, `H-1` sampai `H-10`, `H-12`, `H-13`, dan mayoritas `M-*` sudah fixed/reviewed.
- Test infrastructure split sudah ada: fast SQLite suite (`phpunit.xml`) dan PostgreSQL integration suite (`phpunit.pgsql.xml`).
- CI sudah memiliki PostgreSQL job dengan `pgvector/pgvector:pg16` dan extension `vector`, `pg_trgm`, `pgcrypto`.
- Core guards sudah diterapkan: pgvector SQLite fallback cast, CipherSweet test key, payroll/leave/overtime/reimbursement race-condition fixes, PII endpoint split, and API throttling for core write endpoints.

## Comprehensive Gap Audit (P0-6) Findings

Completed 2026-06-18. Scanned all 60+ PHP source files in `app/`, 50+ test files, config, routes, migrations, Livewire, Blade views, JS, and docs.

### Zero Coverage

| Area | Item | Detail |
|------|------|--------|
| Service | `ProfileService` | Only service class with zero test coverage (3 methods: getProfile, updateProfile, changePassword) |
| Search | Pinecone search | Stub returns `[]`, no tests exist |
| Jobs | Edge cases | 9 tests cover basic dispatch only; no failed/retry/log edge cases ✅ T-26 completed (28 tests) |
| Events/Mail | Architecture | No `app/Events/`, `app/Listeners/`, `app/Mail/` directories exist ✅ T-30: adequate with Notification system. PayrollPublished wired. Dead notifications removed. |
| Integration | Blade-to-API | Zero tests for end-to-end frontend-backend flow |
| Web routes | Fortify + dashboard | 25+ GET routes (auth pages, dashboard, settings) — zero smoke tests |
| Middleware | `DeviceDetection` | UA-parsing middleware — zero test coverage ✅ FIXED (8 tests in DeviceDetectionTest) |
| Middleware | `GeofenceValidation` | Middleware-layer test coverage zero ✅ FIXED (8 tests in GeofenceValidationTest) |
| Commands | `detect-missed-clock` | Daily scheduled — zero test coverage |
| Commands | `auto-approve-wfa` | Daily scheduled — zero test coverage |
| Commands | `send-reminders` | Weekday scheduled — zero test coverage |
| Commands | `knowledgebase:index` | Manual — zero test coverage |

### Thin Coverage (<10 assertions — gap sudah ditutup)

Progress thin-coverage service, sekarang semua ≥7 assertions:

| File | Tests (sebelum→sesudah) | Notes |
|------|:---:|---|
| `PayslipPdfServiceTest.php` | 4 → 8 | `buildTemplateData()` salary breakdown, employee name, empty extra_income, generated_at format. DomPDF facade tetap untestable. |
| `PayrollExportServiceTest.php` | 6 → 8 | Added branch filter size, full-vs-empty period comparison. XLSX via inline `new Writer` (OpenSpout) — hard to mock. |
| `EmployeeTerminationServiceTest.php` | 6 → 8 | Added DISMISSED with reason, CONTRACT_END financial_summary via terminate(). Static `activity()` calls. |
| `OvertimeServiceTest.php` (unit) | 3 → 5 | Added equal-time 24h (overnight) + very short 0.5h duration edge cases. |
| `GeminiClientTest.php` | 6 → 8 | Added empty context string, multi-embed (different input texts). |
| `EmbeddingServiceTest.php` | 6 | Chunk + format only; `new PdfParser` untestable without real PDF. |
| `FaceRecognitionServiceTest.php` | 6 unit + 5 PG integration | Unit: dimension + validation. PG: nearestNeighbors() invoked with threshold override, null embedding, similarity_percentage, rejection message. |
| `GeofenceServiceTest.php` | 11 | OK but edge-case light. |
| `KnowledgeBaseServiceTest.php` | 11 | OK but RAG flow untested. |
| `PayrollCalculatorService` | Scattered → 41 dedicated (T-20) | `generatePayroll()` (~150 baris) tanpa dedicated test file; coverage tersebar di 3 file. |

### No Dead/TODO/FIXME Code Found
- `app/` source files: 0 TODO markers, 0 FIXME markers, 0 commented-out code blocks, 0 `dd()`/`dump()`/`ray()`/`logger()->debug()` calls
- Config files: clean, no stale keys
- Routes: no dead routes, all 51 API routes mapped to real controllers
- Migrations: all 5 PG-specific files have `if (DB::getDriverName() === 'pgsql')` guard
- JS: `resources/js/app.js` minimal, no `console.log()` left

### IDOR Audit Results

**Verdict: 0 ❌ VULNERABLE, 14 ⚠️ PARTIAL (defense-in-depth gaps), 21 ✅ SAFE.**

#### EmployeeController — No Team Scoping in API
| Endpoint | Auth | Gap |
|----------|------|-----|
| `GET /employees` (index) | `view_employees` permission | Manager can list ALL employees (not just direct reports). Policy comment says "team scoping di-handle via Livewire component, bukan policy concern." |
| `GET /employees/{id}` (show) | `view_employees` + `EmployeePolicy::view` | Same — no team boundary |
| `PUT /employees/{id}` | `manage_employees` permission | Permission-only check on resource; no additional scope. Low-risk since `manage_employees` is HR-only. |
| `DELETE /employees/{id}` | `manage_employees` permission | Same pattern |
| `GET /employees/{id}/pii` | `manage_employees` + audit log | Same pattern (PII reveal is intentional + audited) |

#### Overtime & Reimbursement Store — Policy Not Invoked (✅ FIXED)
| Endpoint | Issue |
|----------|-------|
| `POST /overtime` | ✅ `$this->authorize('create', Overtime::class)` added. |
| `POST /reimbursement` | ✅ `$this->authorize('create', Reimbursement::class)` added. |

#### Defense-in-Depth Gaps
- **All `viewAny` policies** (Attendance, Leave, Overtime, Reimbursement) are permission-only. Real ownership scoping happens in controller query builders. If a future `index` omits the query scope, the policy alone won't prevent IDOR.
- **`Payroll generate`** accepts `employee_ids[]` with no relationship verification. Gated by `process_payroll` (Finance-only).
- **`approveWfa`** uses `$user->can('approve_wfa')` string permission instead of `$this->authorize()` — inconsistent with rest of codebase (hierarchy check still prevents IDOR).

### Infrastructure Gaps

| Item | Detail |
|------|--------|
| Livewire | 1 component (`app/Livewire/Actions/Logout.php`) — no test |
| Blade view | 38 view files — no assertions on rendered content |
| CI | `.github/workflows/tests.yml` — SQLite CI works; PostgreSQL CI commented out |
| CI config | `phpunit.pgsql.xml` naming confirmed correct |
| Docs | `docs/testing/testing-strategy.md` rewritten (was aspirational plan, now documents actual suite of 73 files, 1,018 tests). `docs/INDEX.md` updated (API count 1→2, total 27→28). | ✅ |

### FormRequest Gaps (6 of 28 have zero validation tests)

| Request | Endpoint | Gap |
|---------|----------|-----|
| `UpdateProfileRequest` | `PUT /profile` | Phone regex, bank_account regex never validated |
| `ListAttendanceRequest` | `GET /attendance` | period, status, per_page rules never tested |
| `ListLeaveRequest` | `GET /leave` | year, status, employee_id, per_page rules never tested |
| `ListOvertimeRequest` | `GET /overtime` | status, period, per_page rules never tested |
| `ListPayrollRequest` | `GET /payroll` | year, employee_id, per_page rules never tested |
| `ListReimbursementRequest` | `GET /reimbursement` | status, period, per_page rules never tested |

### Policy Gaps (2 of 8 without direct policy tests)

| Policy | Test Coverage |
|--------|---------------|
| `AttendancePolicy` | Exercised via endpoint tests, but no direct policy-boundary test |
| `OvertimePolicy` | Exercised via endpoint tests, but no direct policy-boundary test |

### Permission Drift
- `MANAGE_REIMBURSEMENTS` — defined in `Permission` enum but **not assigned to any role** in `RoleAndPermissionSeeder`

### Factory Gaps (12 of 30 models use `HasFactory` but lack factory class)

| Model | Impact |
|-------|--------|
| `Approval` | Cannot use `Approval::factory()` — tests build approvals manually |
| `Asset`, `AssetHandover` | V2 module, low priority |
| `Device` | Face recognition device tracking |
| `CompanySetting` | Heavily used in services via `CompanySetting::get()` static call |
| `FamilyDetail` | CipherSweet PII model — no factory |
| `Loan`, `LoanInstallment` | V2 module, low priority |
| `PayrollAdjustment`, `PayrollItem` | Payroll detail models |
| `PerformanceReview` | V2 module |
| `ShiftSchedule` | Attendance scheduling |
| `KnowledgeBase` | Does NOT use `HasFactory` at all — intentional?

## Completed In Current Backend-100% Pass

| ID | Done | Verification |
|---|---|---|
| P1-10a | Removed all API controller `private format*()` methods and moved Leave, Overtime, Reimbursement, Employee, and Profile serialization to API Resources. | `php artisan test --compact tests/Feature/Api/LeaveAndOvertimeTest.php tests/Feature/Api/ControllerHttpTest.php tests/Feature/Api/EndpointsTest.php tests/Feature/Api/SecurityRegressionTest.php --filter='LeaveController|OvertimeController|ReimbursementController|EmployeeController CRUD|profile|Profile|change-password'` -> 39 passed, 179 assertions. |
| P0-3a | Added grouped inventory for all 51 `/api/v1` routes. | `php artisan route:list --path=api --except-vendor` |
| P0-3b | Added 401 smoke tests for all 51 routes (GET, POST, PUT, DELETE) — every protected endpoint returns 401 without token. | `php artisan test --compact --filter='EndpointsTest'` -> 57 passed, 116 assertions. |
| P1-9a | KnowledgeBase API audit: upload, chat, delete with auth/permission/validation coverage. | `php artisan test --compact --filter='KnowledgeBaseProof'` -> 14 passed, 40 assertions. |
| P1-8a | Payroll API audit: list, show, generate, payslip, exports, permission gating. | `php artisan test --compact --filter='PayrollProof'` -> 20 passed, 81 assertions. |
| P1-3a | Attendance API audit: clock-in/out, GPS/WFA edge cases, today, index, permission gating. | `php artisan test --compact --filter='AttendanceProof'` -> 20 passed, 60 assertions. |
| P1-1a | Auth API audit: 2FA login + TOTP challenge, rate limit, forgot-password, validation. | `php artisan test --compact --filter='AuthProof'` -> 14 passed, 55 assertions. |
| P1-4a | Leave API audit: auth, permission, delete, owner access gaps. | `php artisan test --compact --filter='LeaveProof'` -> 9 passed, 13 assertions. |
| P1-5a | Overtime API audit: auth, permission, owner access gaps. | `php artisan test --compact --filter='OvertimeProof'` -> 6 passed, 6 assertions. |
| P1-6a | Reimbursement API audit: auth, permission, state-conflict gaps. | `php artisan test --compact --filter='ReimbursementProof'` -> 10 passed, 19 assertions. |
| P1-7a | Approval API audit: auth, validation, resource gaps. | `php artisan test --compact --filter='ApprovalProof'` -> 8 passed, 12 assertions. |
| P1-2a | Employee API audit: auth, permission gating gaps. | `php artisan test --compact --filter='EmployeeProof'` -> 8 passed, 16 assertions. |
| P1-3b | Face API audit: register, verify, validation, auth. | `php artisan test --compact --filter='FaceProof'` -> 8 passed, 26 assertions. |
| P1-11a | ProfileService test coverage (was zero) + refactor ProfileController to use service. | `php artisan test --compact --filter='ProfileService'` -> 5 passed, 19 assertions. |
| P1-15a | Scheduled command tests: 12 tests for detect-missed-clock, auto-approve-wfa, send-reminders, knowledgebase:index. Fixed 3 production bugs found. | `php artisan test --compact --filter='ConsoleCommands'` -> 25 passed, 41 assertions. |
| T-23a | Added `$this->authorize('create')` to OvertimeController and ReimbursementController store methods. | `php artisan test --compact --filter='OvertimeProof|ReimbursementProof|EndpointsTest'` -> 44 passed, 88 assertions. |
| T-17a | Policy boundary tests: 15 new tests for AttendancePolicy (6) and OvertimePolicy (9) — all 8 policies now have direct tests. | `php artisan test --compact --filter='PoliciesTest'` -> 36 passed, 57 assertions. |

| P1-12a | Web route smoke tests: 18 tests covering 6 public Fortify auth pages, 6 unauthenticated redirects, 3 authenticated pages (dashboard, email/verify, confirm-password), 3 Livewire settings pages. | `php artisan test --compact --filter='WebRouteSmoke'` -> 18 passed, 26 assertions. | ✅ |
| T-3a | PII/CipherSweet tests: 14 tests covering Employee/Company/FamilyDetail encryption round-trip, blind index lookups, empty encrypted field, ProfileResource masking, encryptedUnique duplicate NIK, PII audit log, and forbidden PII access. | `php artisan test --compact --filter='PiiCipherSweet'` -> 14 passed, 36 assertions. | ✅ |
| P1-1a | Password expiry + logout-all: fixed 2 bugs (ResetUserPassword + CreateNewUser set password_changed_at). 10 tests: change-password resets clock, forgot-password sets password_changed_at, CreateNewUser sets password_changed_at, logout-all token invalidation, edge cases (boundary 90d, CompanySetting=0, unauthenticated, single-token). | `php artisan test --compact --filter='PasswordExpiry|CheckPasswordExpired|logout-all'` -> 19 passed, 38 assertions. | ✅ |

## Carried Forward From Previous Tracker

Item lama yang belum `✅` atau `🚫` tidak dihapus; semuanya dipetakan ke task aktif berikut.

| Old ID | Previous Item | New Task |
|---|---|---|
| H-11 | Testing strategy doc references nonexistent tests | `T-12` |
| M-13 | Sanctum token expiration decision | `P0-5`, `API-6` |
| M-15 | Fortify registration production decision | `P0-5`, `API-6` |
| M-17 | Bank account blind-index decision | `P0-5`, `T-3` |
| A-2 | API Resource / response standardization incomplete | `P1-10`, `API-1` |
| T-1 | Face Recognition frontend | Deferred until backend freeze; not part of backend 100% tracker |
| T-2 | Security hardening | `S-1` sampai `S-8`, `O-7` |
| T-3 | Notification tests | `T-8` |
| T-4 | Observer tests | `T-9` |
| T-5 | AttendanceController tests | `P1-3`, new `T-1`, `T-4` |
| T-6 | LeaveController + OvertimeController tests | `P1-4`, `P1-5`, new `T-1`, `T-5` |
| T-7 | AuthController, FaceController, ProfileController tests | `P1-1`, `P1-3`, new `T-1` |
| T-8 | Service + Job tests | `T-7`, `T-8` |
| T-9 | Critical bug regression tests | `T-4`, `T-5`, `T-6`, `T-10` |
| T-10 | PostgreSQL integration expansion | `T-10` |
| T-11 | Scramble/OpenAPI contract tests | `T-11`, `API-7` |
| T-12 | CipherSweet + PII integration tests | `T-3` |
| D-2 | Scramble docs regeneration workflow | `T-11`, `API-7` |

## Backend 100% Definition Of Done

- Backend V1 scope freeze selesai; semua fitur non-V1 eksplisit ditunda.
- Semua endpoint V1 audited: route, middleware, permission, policy, FormRequest, response, error code, Resource/PII, and tests.
- Semua service V1 bisa dijalankan via API tanpa edit DB manual.
- `composer test` hijau.
- `composer test:pgsql` hijau.
- Tidak ada PII leak, secret hardcoded, atau endpoint sensitif tanpa authorization.
- Queue worker, scheduler, cache invalidation, storage, backup/restore, and deployment rehearsal siap production.
- API contract final dan stabil untuk frontend.

## P0 — Scope Freeze And Audit

| ID | Task | Output | Status |
|---|---|---|---|
| P0-1 | Freeze backend V1 scope | ✅ `docs/api/scope-v1.md` created: 13 modules (51 routes), all live. V2 modules explicitly listed as deferred: Loan, Asset, Performance Review, WhatsApp. Design decisions documented (token lifetime, Fortify registration, RAG, face, GPS, notifications). | ✅ |
| P0-2 | Mark V2 modules as out-of-scope for backend 100% | ✅ Loan/Kasbon, Asset Management, Performance Review, WhatsApp Notifications 100% deferred. No tables/routes exist. `docs/api/scope-v1.md` documents the boundary explicitly. | ✅ |
| P0-3 | Build endpoint audit matrix for all `/api/v1` routes | ✅ Inventory: 51 API routes grouped in 13 modules. 401 smoke tests for all protected routes. Proof tests for all 11 endpoint groups. Matrix documented in `docs/api/api-contracts.md` and `docs/api/scope-v1.md`. | ✅ |
| P0-4 | Build service audit matrix | ✅ `docs/api/service-matrix.md` created: 15 services, 54 methods, all with test files. Coverage ratings: 6 Good, 3 Adequate, 6 Thin. Architecture patterns documented: 15 transactions (all lockForUpdate), 2 job dispatch sites, 3 external API calls (Gemini), 4 cache calls, 3 activity log calls, 0 events. Gaps documented per service. | ✅ |
| P0-5 | Decide remaining product decisions | ✅ All 3 decisions documented in `docs/api/scope-v1.md`: (1) **Sanctum token**: never expire (PWA design), optional `SANCTUM_TOKEN_EXPIRATION` override. (2) **Fortify registration**: DISABLED in production via `app()->environment('production')` guard in `config/fortify.php` — HR creates employees via API. (3) **Bank account blind index**: DEFERRED (encrypted but no search use case, add in V1.1 if needed). | ✅ |
| P0-6 | Comprehensive gap audit of entire codebase | ✅ Three-round audit complete: (1) app code scan, (2) middleware/form-request/command/policy audit, (3) database layer audit. 0 dead code found. Gaps documented across 12 categories with ~30 specific items. See full findings above. | ✅ |

## API Endpoint Audit Matrix

Inventory source: `php artisan route:list --path=api --except-vendor` on 2026-06-18.

| Group | Routes | Controllers | Request Coverage | Auth/Permission | Test Status | Gaps |
|---|---:|---|---|---|---|---|
| Health | 1 | `HealthController` | N/A | Public | Needs smoke proof | None known |
| Auth public | 3 | `AuthController@login`, `twoFactorChallenge`, `forgotPassword` | `LoginRequest`, `TwoFactorChallengeRequest`, `ForgotPasswordRequest` | Public + throttle | ✅ | AuthProofTest covers login→2FA, TOTP valid/invalid, rate limit, forgot-password validation. Confirm OpenAPI/security contract. |
| Auth protected/user | 3 | `AuthController@logout`, `logoutAll`, `me` | Basic `Request` | `auth:sanctum` | Partial | Token lifetime decision `P0-5` |
| Profile | 3 | `ProfileController` | `UpdateProfileRequest`, `ChangePasswordRequest`; show uses `Request` | `auth:sanctum` | Partial | Final profile response contract |
| Face | 2 | `FaceController` | `RegisterFaceRequest` | `auth:sanctum` + throttle | Partial | Browser/client face-api flow deferred to FE |
| Attendance | 5 | `AttendanceController` | `ClockInRequest`, `ClockOutRequest`, `ListAttendanceRequest`; `today/approveWfa` use `Request` | `auth:sanctum`, throttles on writes | ✅ | AttendanceProofTest covers clock-in/out PIN+GPS+WFA, today, index, WFA approval (+20 tests). Gaps: face recognition 128D flow. |
| Leave | 5 | `LeaveController` | `StoreLeaveRequest`, `ListLeaveRequest`; quota/show/delete use `Request` | `auth:sanctum`, policy, throttle on store | Partial | Quota response contract and role matrix proof |
| Overtime | 4 | `OvertimeController` | `StoreOvertimeRequest`, `ListOvertimeRequest`; show/delete use `Request` | `auth:sanctum`, policy, throttle on store | Partial | Role matrix and payroll-impact proof |
| Reimbursement | 4 | `ReimbursementController` | `StoreReimbursementRequest`, `ListReimbursementRequest`; show/delete use `Request` | `auth:sanctum`, policy, throttle on store | Partial | Upload failure and payment-state proof |
| Approval | 3 | `ApprovalController` | `PendingApprovalsRequest`, `ApproveRequest`, `RejectRequest` | `auth:sanctum`, policy/service checks | Partial | L1/L2 and double-processing coverage exists but needs matrix link |
| Payroll | 7 | `PayrollController` | `ListPayrollRequest`, `GeneratePayrollRequest`, `ExportMonthlyRequest`, `ExportPeriodRequest`; show/payslip use `Request` | `auth:sanctum`, policy, `process_payroll` permissions | ✅ | PayrollProofTest covers list, show, generate, payslip gating, exports, permission gating (+20 tests). Gaps: lock behavior, concurrent generate. |
| Employees | 8 | `EmployeeController`, `EmployeeTerminationController` | `ListEmployeeRequest`, `StoreEmployeeRequest`, `UpdateEmployeeRequest`, `TerminateEmployeeRequest`; contract-end uses inline validation | `auth:sanctum`, `view_employees`/`manage_employees`, policies | Partial | Contract-end FormRequest decision; PII audit proof |
| KnowledgeBase | 3 | `KnowledgeBaseController` | `ChatRequest`, `UploadDocumentRequest`; destroy uses `Request` | `auth:sanctum`, throttle chat, `manage_knowledgebase` for mutations | ✅ | KnowledgeBaseProofTest covers chat, upload, delete, auth/permission (+14 tests). RAG refactor done (SESI-48, RAG-1–9 except RAG-8). Streaming endpoint plan pending. |

## P1 — API And Service Completion

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|---|
| P1-1 | Auth/Profile API audit | ✅ AuthProof (+8 tests). ✅ Password expiry + logout-all (+5 edge-case/token tests: boundary 90 hari, CreateNewUser sets password_changed_at, CompanySetting=0 disable, unauthenticated 401, single-token revoke). | ✅ |
| P1-2 | Employee API audit | ✅ EmployeeProof (+8 tests). Combined with ControllerHttpTest (11 existing). ✅ PII audit log verification: audit log written with correct description/log_name/causer, no audit for 403 or regular show(), multiple accesses create multiple entries. PiiCipherSweetTest now 17 tests (+3). | ✅ |
| P1-3 | Attendance API audit | ✅ AttendanceProof (+20 tests). ✅ Face recognition 128D flow: RegisterFaceRequest validation (boundaries, range, non-numeric), clock-out face PIN fallback, clock-out face fail without fallback. FaceRecognitionService unit (7) + FaceProof API (8) + clock-in/out face tests (5) + RegisterFaceRequest validation (5) = 25 face-related tests. | ✅ |
| P1-4 | Leave API audit | ✅ LeaveProof (+9 tests). ✅ Approval quota deduction edge cases: balance deleted before final approval throws 422 (fixed in Leave.php 'deleting' event: getOriginal('status') vs status). LeaveAndOvertimeTest now 38 tests (+1 bugfix). | ✅ |
| P1-5 | Overtime API audit | ✅ OvertimeProof (+6 tests). ✅ Payroll-impact proof: multiple approved overtimes sum correctly in payroll (end-to-end). Combined unit tests (6 calculateOvertimePay) + SecurityRegressionTest (1 end-to-end) = 7 payroll-impact tests. | ✅ |
| P1-6 | Reimbursement API audit | ✅ ReimbursementProof (+10 tests). ✅ Payment workflow: end-to-end submit→approve→payroll→PAID flow, model terminal-state guard (4 tests for APPROVED/PAID/REJECTED/PENDING). Bug fix: Reimbursement.php booted() used getOriginal() instead of getRawOriginal() causing guard to never fire. Guard now allows APPROVED→PAID forward transition. ReimbursementServiceTest now 14 tests (+4, all pass). | ✅ |
| P1-7 | Approval API audit | ✅ ApprovalProof (+9 tests: +1 successful approve, +1 successful reject via endpoint). ApprovalProofTest now 9 tests. | ✅ |
| P1-8 | Payroll API audit | ✅ PayrollProof (+20 tests). ✅ Lock behavior: isLocked() direct tests (3), Payroll model updating guard (2 tests for PUBLISHED/PAID), lock release after exception (1 test). PayrollCalculatorCoreTest now 57 tests (+6). | ✅ |
| P1-9 | KnowledgeBase API audit | ✅ KnowledgeBaseProof (+14 tests). | ✅ |
| P1-10 | Standardize API resources/responses | ✅ Code serialization cleanup done: no `format*()` methods remain. ✅ Response envelope standardized: 4 patterns (data, list+meta, action+message+data, action+message). Health endpoint intentional exception. ✅ API contract tests pass (39 tests, API-1 ✅, T-11 ✅). | ✅ |
| P1-11 | ProfileService test coverage | ✅ 5 tests written (getProfile with/without employee, updateProfile, changePassword success, changePassword wrong current). Refactored ProfileController to use ProfileService (eliminated dead code). | ✅ |
| P1-12 | Web route smoke tests | 25+ web GET routes (Fortify auth pages, dashboard, settings) have zero test coverage. Add smoke tests. | ✅ |
| P1-13 | Middleware test coverage | ✅ All 3 middleware tested: CheckPasswordExpired (7 web-layer tests), DeviceDetection (9 unit tests — UA parsing for mobile/tablet/desktop + all browsers/OS), GeofenceValidation (7 feature/integration tests — auth, branch, GPS radius, edge cases). | ✅ |
| P1-14 | FormRequest validation tests | ✅ All 6 FormRequests already tested (22 total): UpdateProfileRequest (4), ListAttendanceRequest (4), ListLeaveRequest (4), ListOvertimeRequest (3), ListPayrollRequest (3), ListReimbursementRequest (4). Combined with T-28 (29 tests) = 51 total FormRequest tests. | ✅ |
| P1-15 | Scheduled command tests | ✅ 12 tests added: detect-missed-clock (3), auto-approve-wfa (3), send-reminders (2), knowledgebase:index (3). **2 bugs found + fixed** in AutoApproveWfaCommand (enum comparison with ->value vs enum, missing $timeoutDays in closure scope). DetectMissedClockCommand fixed (referenced nonexistent columns). All 9 commands now have test coverage. | ✅ |

## P1 — RAG Production Refactor With Laravel AI SDK (✅ SESI-48)

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| RAG-1 | Decide Laravel AI SDK adoption boundary | AI SDK handles provider, embeddings, agent, structured output, streaming/testing; pgvector/pg_trgm/domain storage stay custom | ✅ |
| RAG-2 | Add and configure `laravel/ai` | Package installed, config published, env keys mapped, migrations understood, no conflict with current KB schema | ✅ |
| RAG-3 | Replace custom Gemini generation behind adapter | Existing `KnowledgeBaseService` behavior preserved while generation uses AI SDK path | ✅ |
| RAG-4 | Replace/adapter embeddings through AI SDK | `text-embedding-004` remains 768D; PostgreSQL vector storage remains `knowledge_bases.embedding` | ✅ |
| RAG-5 | Create `HrKnowledgeBaseAgent` | Prompt/instructions centralized; answer refuses hallucination when no source exists | ✅ |
| RAG-6 | Add structured RAG output | Response includes `answer`, `sources`, `confidence`, `fallback`, `model` | ✅ |
| RAG-7 | Preserve `pg_trgm` fallback | Gemini/embedding failure falls back to keyword search without crashing chat endpoint | ✅ |
| RAG-8 | Add optional streaming endpoint plan | Streaming API contract decided for frontend chat; implementation if backend scope includes it | 🚧 | Plan created at `docs/api/rag-8-streaming-plan.md`. Decision: defer implementation (sync chat sufficient for MVP). |
| RAG-9 | Add AI SDK fake tests | Agent, embedding, and fallback tests do not require real external API calls | ✅ |

## P1 — Test Coverage Completion

| ID | Task | Minimum Coverage | Status |
|---|---|---|---|
| T-1 | Full API endpoint tests | Happy path, validation error, unauthorized, forbidden, state conflict for all V1 endpoint groups | 🚧 | Proof tests done for all 11 groups + policy tests + command tests = 1,074 total across 60+ files. Web route smoke tests (T-13) ✅, FormRequest validation tests (P1-14) ✅, Middleware tests ✅, P1-1 ✅. Remaining: deeper edge cases per group. |
| T-2 | Role/permission matrix tests | super-admin, hr-manager, finance, manager, employee access boundaries | ✅ | Full 5-role × 44-permission matrix validated via data-driven test (217 cases: 118 can + 99 cannot). Matrix added to RoleAndPermissionSeederTest. |
| T-3 | PII/CipherSweet tests | `whereBlind()`, `Rule::encryptedUnique()`, raw encrypted values, PII reveal audit logging | ✅ |
| T-4 | Attendance regression tests | Face success, face fail -> PIN, PIN streak, fake GPS, outside geofence, WFA, duplicate clock-in/out | ✅ | 10 new tests added to AttendanceProofTest. All 30 tests pass. Covers: face clock-in, PIN fallback, geofence 403, low accuracy, WFA clock-out, face clock-out, non-WFA approval rejection, manage_attendances scope, status filter. |
| T-5 | Leave/approval regression tests | Final approval quota deduction, reject no deduction, no negative balance, overlap, wrong approver | ✅ | 15 new tests in LeaveAndOvertimeTest. Covers: sick leave without proof, probation employee, cancel non-pending, L1→approved_l1, full L1+L2→approved+quota deducted, direct L2, reject→REJECTED+rejection_reason, pending list, wrong approver 403, double approve/reject 409, L2 before L1 422, rejection no deduction. |
| T-6 | Payroll regression tests | Generate, regenerate draft, reject published/paid changes, payslip gating, exports, concurrent lock behavior | ✅ | PayrollProof covers generate, payslip gating, exports (+10 tests). ✅ Added: model guard rejects direct PUBLISHED/PAID updates (3 tests), regenerate draft preserves record (1 test), lock release via finally block (existing). ✅ Concurrent: reimbursements claimed and not double-counted on regenerate (gross_salary reflects inclusion/exclusion). |
| T-7 | KnowledgeBase/RAG tests | Upload, chunk, embedding job, vector search, fallback keyword search, structured response | ✅ | KnowledgeBaseProof covers chat, upload, delete (+14 tests). RAG-1–9 complete (except RAG-8 streaming deferred). 38 RAG tests pass via AI SDK fakes. Fallback keyword search: both branches tested (results found + empty results). Confidence field asserted. |
| T-8 | Queue/job tests | Payroll, payslip PDF, embedding, notifications failed handlers and retry/log behavior | ✅ |
| T-9 | Observer/cache tests | TaxConfig, BpjsConfig, Holiday, Employee, Attendance, Leave, Payroll invalidation behavior | ✅ | Added CompanySettingObserver tests (2 — was the only gap). Created CacheIntegrationTest (8 tests: TaxConfig cachedAll, CompanySetting get/set/fallback, Holiday cachedYear active/inactive, BpjsConfig cachedAll). |
| T-10 | PostgreSQL integration expansion | pgvector, CipherSweet, constraints, payroll/approval locking, migration extension guards | ✅ | 15 total tests (up from 10). Added: 768D KB embedding cosine search, nearest neighbor ranking (3 non-collinear vectors), CipherSweet whereBlind null return, HNSW index existence verification on knowledge_bases.embedding. |
| T-11 | OpenAPI/Scramble contract tests | Representative `/api/v1/*` paths, bearer security, public routes, request schema alignment | ✅ | 25 contract tests: spec structure (version, tags, schemas, paths, ops), route completeness (all 51 routes documented, no extras), security contract (4 public routes security: [], 47 protected 401 via ref), validation contract (all POST/PUT with requestBody have 422), pagination contract (7 list endpoints with meta.current_page/last_page/per_page/total), operationId uniqueness, smoke tests (health 200, 401/403/422 shapes, 404). |
| T-12 | Remove/replace stale docs test references | `docs/testing/testing-strategy.md` reflects actual test suite, not nonexistent files | ✅ | Rewritten from scratch (was aspirational plan referencing nonexistent files). Now documents 73 actual test files, 1,018 tests, PostgreSQL suite, AI/vector testing approach. INDEX.md updated: API count 1→2 (+api.json), total 27→28 files. |
| T-13 | Web route smoke tests | ✅ 18 smoke tests: 6 public auth pages (200), 6 unauthenticated redirects (302), 3 authenticated pages (200), 2 special pages (email_verify→dashboard, security→confirm-password), 1 2FA-challenge redirect. | ✅ |
| T-14 | Livewire component test | Add basic render test for `app/Livewire/Actions/Logout.php` | ✅ | 3 tests: logout clears auth, invalidates session, regenerates CSRF token. (Note: Logout is an invokable action class, not a Component — tested as unit action.) |
| T-15 | ProfileService test | ✅ 5 tests (getProfile with/without employee, updateProfile, changePassword success/wrong current). Plus refactored ProfileController to use the service (eliminated dead code). | ✅ |
| T-16 | Pinecone stub test | ✅ Test that pgvector SQLite fallback returns ready records (searchSimilar + searchByKeyword + processKnowledgeBase). Added 5 tests to EmbeddingServiceTest. | ✅ |
| T-17 | Policy direct tests | ✅ 15 new boundary tests for `AttendancePolicy` (6) and `OvertimePolicy` (9) covering view self/other/team, create, update/delete status gates, approveLevel1/2 scoping. All 8 policies now have direct tests. | ✅ |
| T-18 | Factory gap closure | ✅ Created factory classes: `Approval`, `CompanySetting`, `Device`, `FamilyDetail`, `PayrollAdjustment`, `PayrollItem`, `ShiftSchedule`, plus `KnowledgeBase` (had HasFactory trait missing). All 8 verified in tinker. | ✅ |
| T-19 | Permission drift audit | `MANAGE_REIMBURSEMENTS` already assigned to finance role. Added to `ReimbursementPolicy::update()` and `delete()` — finance users can manage any pending reimbursement. | ✅ |
| T-20 | PayrollCalculatorService dedicated tests | Extract `generatePayroll()` coverage from integration tests into dedicated service test file | ✅ | 41 new tests added to PayrollCalculatorCoreTest: calculateOvertimePay (weekday tiers, weekend tiers, zero hours, missing position), calculatePesangon (tenure tiers, variant multipliers), calculateUangPenghargaanMasaKerja (all 7 tenure brackets), calculateUangKompensasi (CONTRACT/PKWT, tenure), calculateLeaveCashOut (no balance, zero remaining, daily rate), regenerate draft B-1 fix. All 5 previously uncovered methods now tested. ✅ JobEdgeCaseTest: 2 tests for GenerateEmployeePayrollJob failed() reimbursement rollback. ✅ Payroll model booted guard: 3 tests (PUBLISHED/PAID rejection, DRAFT allowed). 33 new tests total. |
| T-21 | FaceRecognitionService pgvector test | Add test that actually invokes `nearestNeighbors()` against DB | ✅ | 5 PG tests total (1 existing + 4 new): CompanySetting threshold override, FaceNotRegisteredException for null embedding, similarity_percentage on match, FaceNotRecognizedException message with similarity. |
| T-22 | Livewire component test | Add basic render test for `app/Livewire/Actions/Logout.php` (duplicate of T-14) | ✅ | Covered by T-14. |
| T-23 | Overtime store policy call | ✅ Added `$this->authorize('create', Overtime::class)` to `OvertimeController::store()`. Fixed 2 test expectations (404→403). | ✅ |
| T-24 | Reimbursement store policy call | ✅ Added `$this->authorize('create', Reimbursement::class)` to `ReimbursementController::store()`. | ✅ |
| T-25 | Employee API team scope decision | ✅ Deferred: team scoping via Livewire query scope, not API policy concern (per AGENTS.md). No code change needed. | ✅ |

## Remaining Gaps (Target 100%)

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| B-100-1 | PayslipPdfService public methods | ✅ generate(), generateAndStore(), getPayslipPath() tests added (4 new). PayslipPdfServiceTest now 12 tests. | ✅ |
| B-100-2 | FormRequest validation (6 files) | ✅ All 6 already tested in FormRequestValidationTest.php (22 tests total). Completed as P1-14. | ✅ |
| B-100-3 | 2FA challenge smoke test | ✅ Added `POST /api/v1/auth/2fa/challenge` to public routes dataset in EndpointsTest. | ✅ |
| B-100-4 | Permission drift | ✅ MANAGE_REIMBURSEMENTS assigned to finance role + added to ReimbursementPolicy (T-19). | ✅ |
| B-100-5 | Web routes (Fortify + Livewire) | ✅ P1-12/T-13: 18 smoke tests (auth pages, redirects, dashboard, settings). | ✅ |
| B-100-6 | Blade-to-API integration | 🚫 Deferred. Domain Blade views (attendance, leaves, overtimes, reimbursements) not yet created. FE routes exist but views missing. Create views first, then integration tests. | 🚫 |
| B-100-7 | Migration PG guard tests | ✅ 4 new PG tests: vector columns on employees + knowledge_bases, jsonb metadata column, jsonb store/query. PostgresEnvironmentTest now 28 tests. | ✅ |
| B-100-8 | Factory gaps (high-impact) | ✅ T-18: 8 factories created (Approval, CompanySetting, Device, FamilyDetail, PayrollAdjustment, PayrollItem, ShiftSchedule, KnowledgeBase). | ✅ |
| B-100-9 | IDOR hardening tests | ✅ 6 tests documented known defense-in-depth gaps: employee team scoping (by design), payroll generate arbitrary employee_ids, approveWfa hierarchy check. See IdorGapDocumentationTest. | ✅ |
| FE-1 | ESS Clock In/Out (Livewire) | GPS + face + PIN fallback. face-detection.js, gps-locator.js, face-enrollment.js | 📋 |
| FE-2 | ESS Attendance History | Livewire component with filters, summary cards | 📋 |
| FE-3 | ESS Leave (Apply + History + Quota) | Livewire SFC, quota management, approval flow | 📋 |
| FE-4 | ESS Overtime (Apply + History) | Livewire SFC, payroll integration | 📋 |
| FE-5 | ESS Reimbursement (Request + History) | Livewire SFC, receipt upload, approval flow | 📋 |
| FE-6 | ESS Payroll Slip | Livewire SFC, PDF download | 📋 |
| FE-7 | ESS Profile & Devices | Personal info, family details, device management | 📋 |
| FE-8 | Missing route views | payroll, approvals, loans, knowledge-base, assets landing pages | 📋 |
| DS-1 | Fix CSS variables (readability) | canvas #fffaf0→#ffffff, surface-card #f5f0e0→#efefef, muted #6a6a6a→#535353 | 📋 |
| DS-2 | Landing page override | welcome.blade.php override CSS vars back to cream vintage | 📋 |
| DS-3 | Fix hardcoded colors (30 HIGH) | two-factor-setup-modal (13: stone-*), dashboard (8: neutral-*, gray-*), navlist/group (3: zinc-*), recovery-codes (2: zinc-*), forgot-password (1: zinc-400), register (1: zinc-600), verify-email (1: green-600), auth-session-status (1: green-600) | 📋 |
| DS-4 | Fix text-muted → text-body | 20+ replacements across 8 ESS Blade files | 📋 |
| DS-5 | Vendor pagination customization | Republish + customize to design tokens | 📋 |
| DS-6 | Update DESIGN.md | Note: App theme vs Landing page split | 📋 |

**Estimasi total**: ~2 minggu (DS-1 sampai DS-6 ≈ 2 hari, FE-1 sampai FE-8 ≈ 1.5 minggu).

### Completed (P1)

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| T-26 | Jobs failed/retry/log edge cases | — | ✅ |
| T-27 | Pinecone search test | — | ✅ |
| T-28 | FormRequest validation sisa (22/28) | — | ✅ |
| T-29 | CI PostgreSQL enable | — | ✅ |
| T-30 | Events/Mail arsitektur | — | ✅ |
| T-31 | Docs sync | — | ✅ |

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| S-1 | Authorization audit | ✅ All 51 routes audited: 4 public (no auth), 47 protected with `auth:sanctum`. ~28 routes use `$this->authorize()`. 15 use permission middleware. 73 endpoint tests pass. 10 low-severity gaps documented (approvals no policy, leave/quota no policy, processContractEnd no policy, approveWfa inconsistent). | ✅ |
| S-2 | IDOR audit | ✅ Audit complete: 0 vulnerable methods. 14 partial/defense-in-depth gaps found: Employee team scoping in API (by design per policy comment), Overtime/Reimbursement store not invoking policy, all `viewAny` policies permission-only. See P0-6 IDOR section. | ✅ |
| S-3 | PII response audit | ✅ All 17 Resources audited: EmployeeResource properly hides nik/phone/npwp/bank_account/pin/face_embedding via #[Hidden] + manual exclusion. ProfileResource masks phone/bank_account. EmployeePiiResource gated by manage_employees + audit log. All controllers use manual field selection (no direct ->toArray()). No HIGH/CRITICAL leaks. Only finding: EmployeeResource exposes address_detail raw (LOW). | ✅ |
| S-4 | Log/audit privacy audit | ✅ All 40+ log statements audited. No NIK/phone/NPWP/bank/PIN/face/password/token/API key logged anywhere. 3 MEDIUM findings fixed: (1) KB question text removed from Gemini fallback log, (2) Gemini 4xx body removed from log, (3) Gemini 5xx full error body replaced with status-only. Low-risk: employee name in payroll job logs, IP in attendance bypass activity log. No HIGH/CRITICAL findings. | ✅ |
| S-5 | Rate-limit audit | ✅ All 13 inline throttles verified in code. 11 throttles exist: auth endpoints (5/min), face (10/min), attendance (5/5min), leave/overtime/reimbursement (10/min), KB chat (20/min). 2 named Fortify limiters (login, two-factor: 5/min). Findings: KB chat 20/min too generous (→ 10/min recommended), face verify 10/min too generous (→ 5/min recommended), password-change missing throttle. 2 phantom limiters documented but never registered (`api` 60/min, `attendance` 10/min). Added throttle tests for login + forgot-password (2 new tests). 3/13 throttles now tested. | ✅ |
| S-6 | Secret audit | ✅ No secrets ever committed. .env gitignored, never in git history. Full git history pickaxe scan (api_key/password/secret/APP_KEY/DB_PASSWORD/CIPHERSWEET/FLUX/SANCTUM/gemini) = no real credentials found. Config files all use env() — no hardcoded secrets. No .pem/.key/.cert/credentials.json in repo. | ✅ |
| S-7 | File upload audit | ✅ All 3 upload endpoints audited (KB + reimbursement + leave). MIME/size validated via FormRequests. Filenames sanitized (UUID/uniqid). Auth via policies + Sanctum. Fixed: (1) KB storeAs wrapped in try/catch + orphan cleanup on transaction/extraction failure, (2) Leave proof_file wrapped in try/catch + orphan cleanup on applyLeave failure, (3) Reimbursement receipt wrapped in try/catch + orphan cleanup on create failure. Added 6 tests: leave proof_file upload, invalid type, oversized; reimbursement receipt upload, invalid type, oversized. Findings: employee photo field is dead code (fillable but no endpoint), no file cleanup on soft-delete. | ✅ |
| S-8 | Production env checklist | ✅ Full audit completed. Fixed: (1) env var mismatch `.env` used `GEMINI_API_KEY` but config reads `GOOGLE_AI_API_KEY` — renamed. (2) `.env.production` rewritten with proper production defaults: APP_NAME=HRConnect, LOG_STACK=daily+30days, SESSION_ENCRYPT=true, DB_SSLMODE=require, MAIL_MAILER=smtp, secured/blanked all credentials. `.env.production` already in `.gitignore`. Key findings: `trustProxies(at: '*')` should be narrowed in deployment, Sanctum tokens never expire, no HSTS header, Gemini will fail without valid API key. Overall production readiness: 4.4/10 — P0/P1 items documented for deployment. | ✅ |

## P2 — Operations Readiness

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| O-1 | Queue readiness | Worker command final, queue names documented, failed jobs observable/retryable, job timeouts/backoff reviewed | ✅ |
| O-2 | Scheduler readiness | `php artisan schedule:list` verified; production cron documented; overlapping prevented | ✅ |
| O-3 | Cache readiness | No `Cache::tags()` with database cache; all cached models have invalidation path | ✅ |
| O-4 | Storage readiness | Payslip/export/KB/reimbursement file disks, permissions, cleanup policy, and failure behavior verified | ✅ |
| O-5 | Health and observability | Health endpoint, logs, queue failures, scheduler logs, and alertable failure modes documented | ✅ |
| O-6 | Backup and restore rehearsal | PostgreSQL backup and restore tested at least once with extension compatibility | ✅ |
| O-7 | Deployment rehearsal | Fresh production-like deploy, migrate, seed required data, queue, scheduler, and smoke API flow succeed | ✅ |

## P2 — API Contract Freeze For Frontend

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| API-1 | Standard response envelope | ✅ Response envelope standardized: 4 patterns (data, list+meta, action+message+data, action+message). Fixed EmployeePiiController::showPii() — was returning raw resource without `status` wrapper. Health endpoint uses `ok`/`degraded` (intentional — different domain). | ✅ |
| API-2 | Error code contract | ✅ Error code table finalized and documented: 10 error status codes, 15+ domain-specific error keys with HTTP mapping. All custom exceptions use uniform `{ status: 'error', message }` shape. | ✅ |
| API-3 | Pagination/filter contract | ✅ All list endpoints use consistent `{ data, meta: { current_page, last_page, per_page, total } }` shape. `per_page` capped at 100. Documented in contract. | ✅ |
| API-4 | Enum contract | ✅ Complete enum reference documented in contracts: 13 enum types (EmployeeStatus, AttendanceStatus, RequestStatus, WfaStatus, PayrollStatus, KBStatus, Gender, MaritalStatus, BloodType, EducationLevel, DayType, ApprovalLevel, SalaryType) with values, labels, Flux colors. | ✅ |
| API-5 | File upload/download contract | ✅ All 3 upload endpoints documented (leave proof_file, reimbursement receipt, KB PDF). All 4 download endpoints documented (payslip + 3 payroll exports). MIME/size limits, storage paths, error handling patterns documented. | ✅ |
| API-6 | Auth/token contract | ✅ Auth flow documented: login → (2FA challenge) → token. Logout revokes current, logout-all revokes all. Token never expires (design decision). Password expiry via `password_changed_at`. All public endpoints listed with throttle limits. | ✅ |
| API-7 | Generate/export docs | ✅ Scramble OpenAPI 3.1 spec generated (275KB, 51+ routes). Exported to `docs/api/api.json`. `api-contracts.md` rewritten (v3.0) — was 1360 lines outdated (~2% implemented), now accurate reflecting current state (51 routes, 13 controllers, 695 tests). | ✅ |

## FE — Frontend ESS Development

| ID | Task | Modul | Status |
|---|---|---|---|
| FE-1 | **Clock In/Out** | Attendance | 📋 |
| | 1a. Livewire component: ClockInOut — face recognition + GPS + PIN fallback | | 📋 |
| | 1b. JS modules: face-detection.js, gps-locator.js, face-enrollment.js | | 📋 |
| | 1c. API integration layer: AttendanceService → clockIn(), clockOut() | | 📋 |
| | 1d. Real-time status polling / Livewire event broadcasting | | 📋 |
| | 1e. Tests: component render, clock-in flow, clock-out flow, error states | | 📋 |
| FE-2 | **Attendance History** | Attendance | 📋 |
| | 2a. Livewire component: AttendanceHistory — table + filters (date range, status) | | 📋 |
| | 2b. Summary cards: on-time, late, absent, overtime hours | | 📋 |
| | 2c. Pagination + export button | | 📋 |
| | 2d. Tests | | 📋 |
| FE-3 | **Leave** (Apply + History + Quota) | Leave | 📋 |
| | 3a. Livewire component: LeaveApply — form (type, dates, reason, proof upload) | | 📋 |
| | 3b. Livewire component: LeaveHistory — table with status badges | | 📋 |
| | 3c. Quota display per leave type | | 📋 |
| | 3d. Approval flow: supervisor modal | | 📋 |
| | 3e. Tests | | 📋 |
| FE-4 | **Overtime** (Apply + History) | Overtime | 📋 |
| | 4a. Livewire component: OvertimeApply — form (date, hours, reason) | | 📋 |
| | 4b. Livewire component: OvertimeHistory — table | | 📋 |
| | 4c. Payroll integration indicator | | 📋 |
| | 4d. Tests | | 📋 |
| FE-5 | **Reimbursement** (Request + History) | Reimbursement | 📋 |
| | 5a. Livewire component: ReimbursementRequest — form (type, amount, receipt upload) | | 📋 |
| | 5b. Livewire component: ReimbursementHistory — table | | 📋 |
| | 5c. Approval flow | | 📋 |
| | 5d. Tests | | 📋 |
| FE-6 | **Payroll Slip** | Payroll | 📋 |
| | 6a. Livewire component: PayrollSlip — payslip list + PDF download | | 📋 |
| | 6b. Summary: take-home pay, deductions, earnings | | 📋 |
| | 6c. Tests | | 📋 |
| FE-7 | **Profile & Devices** | Profile | 📋 |
| | 7a. Livewire component: MyProfile — personal info, family, emergency contact | | 📋 |
| | 7b. Livewire component: MyDevices — device list + add/remove | | 📋 |
| | 7c. Tests | | 📋 |
| FE-8 | **Missing Route Views** | Various | 📋 |
| | 8a. Payroll landing page (routes/payroll.php) | | 📋 |
| | 8b. Approvals landing page (routes/approval.php) | | 📋 |
| | 8c. Loans landing page (routes/loan.php) | | 📋 |
| | 8d. Knowledge Base landing page (routes/knowledge-base.php) | | 📋 |
| | 8e. Assets landing page (routes/asset.php) | | 📋 |

## DS — Design System & Readability

| ID | Task | Modul | Status |
|---|---|---|---|
| DS-1 | **Fix CSS variables (readability)** | CSS | 📋 |
| | 1a. canvas: #fffaf0 → #ffffff | | 📋 |
| | 1b. surface-card: #f5f0e0 → #efefef | | 📋 |
| | 1c. surface-soft: #faf5eb → #f7f7f7 | | 📋 |
| | 1d. surface-strong: #e0daca → #d0d0d0 | | 📋 |
| | 1e. muted: #6a6a6a → #535353 | | 📋 |
| | 1f. muted-soft: #9a9a9a → #888888 | | 📋 |
| | 1g. hairline: #e2dcca → #cfcfcf | | 📋 |
| DS-2 | **Landing page override** | welcome.blade.php | 📋 |
| | 2a. Wrap landing content with CSS variable overrides (cream vintage) | | 📋 |
| DS-3 | **Fix hardcoded colors (33 HIGH)** | Multiple Blade files | 📋 |
| | 3a. two-factor-setup-modal.blade.php (14 violations: stone-*, green-500) | | 📋 |
| | 3b. dashboard.blade.php (8 violations: border-neutral-200, stroke-gray-900/20, dark: variants) | | 📋 |
| | 3c. navlist/group.blade.php (3 violations: zinc-*) | | 📋 |
| | 3d. recovery-codes.blade.php (2 violations: zinc-*) | | 📋 |
| | 3e. auth pages (6 violations): forgot-password (zinc-400), register (zinc-600 + dark:zinc-400), verify-email (green-600 + dark:green-400), auth-session-status (green-600) | | 📋 |
| DS-4 | **Fix text-muted → text-body** | 8 ESS Blade files | 📋 |
| | 4a. attendance/, leaves/, overtimes/, reimbursements/ (20+ replacements) | | 📋 |
| DS-5 | **Vendor pagination customization** | Pagination | 📋 |
| | 5a. Republish pagination views | | 📋 |
| | 5b. Customize to design tokens | | 📋 |
| DS-6 | **Update DESIGN.md** | Documentation | 📋 |
| | 6a. Note: App theme vs Landing page split | | 📋 |
| | 6b. Document new CSS variable values | | 📋 |

| | 6b. Document new CSS variable values | | 📋 |

## Project Audit — 2026-06-23

### File & Layer Counts

| Layer | Count | Details |
|---|---|---|
| Models | 31 | Employee, User, Attendance, Leave, LeaveType, LeaveBalance, Overtime, Reimbursement, ReimbursementCategory, Payroll, PayrollItem, PayrollAdjustment, Approval, Company, Branch, Department, Position, Shift, ShiftSchedule, Holiday, CompanySetting, TaxConfig, BpjsConfig, Asset, AssetHandover, Loan, LoanInstallment, Device, FamilyDetail, KnowledgeBase, PerformanceReview |
| Enums | 34 | 16 Status (+ `color()`) + 17 Classification (tanpa `color`) + 1 Permission (extra) |
| Services | 15 | Attendance, Leave, Overtime, Reimbursement, Approval, PayrollCalculator, PayrollExport, PayslipPdf, Profile, FaceRecognition, Geofence, EmployeeTermination, KnowledgeBase, Embedding, GeminiClient |
| API Controllers | 13 | Auth, Attendance, Leave, Overtime, Reimbursement, Approval, Payroll, Employee, EmployeeTermination, Face, Profile, KnowledgeBase, Health |
| Form Requests | 28 | Login, ClockIn, ClockOut, StoreLeave, StoreOvertime, StoreReimbursement, StoreEmployee, UpdateEmployee, List* (6), Approve, Reject, PendingApprovals, RegisterFace, GeneratePayroll, Export* (2), ChangePassword, UpdateProfile, UploadDocument, Chat, ForgotPassword, TwoFactorChallenge, TerminateEmployee |
| API Resources | 17 | Employee, EmployeePii, Attendance, Leave, LeaveType, Overtime, Reimbursement, ReimbursementCategory, Approval, Payroll, PayrollItem, Payslip, Profile, Branch, Department, Position, KnowledgeBase |
| Policies | 8 | Employee, Attendance, Leave, Overtime, Reimbursement, Payroll, Asset, KnowledgeBase |
| Observers | 8 | Employee, Attendance, Leave, Payroll, Holiday, TaxConfig, BpjsConfig, CompanySetting |
| Middleware | 3 | CheckPasswordExpired, DeviceDetection, GeofenceValidation |
| Jobs | 3 | GenerateEmployeePayroll, ProcessKnowledgeBaseEmbedding, GeneratePayslipPdf |
| Commands | 9 | DetectAlphaAttendance, DetectChronicLate, ResetLeaveQuota, GeneratePayroll, AttendanceSendReminders, AutoApproveWfa, DetectMissedClock, KnowledgeBaseIndex, WarmCache |
| Notifications | 4 | PayrollPublished, NewDeviceLogin, ChronicLateWarning, AttendanceReminder |

### API Routes (51 endpoints at `/api/v1`)

| Prefix | Endpoints | Auth | Throttle |
|---|---|---|---|
| `GET /health` | 1 | Public | — |
| `POST /auth/*` | 4 (login, 2fa, forgot-password, logout) | Public (3) + Sanctum (1) | 5/min |
| `GET /user` | 1 | Sanctum | — |
| `/profile` | 3 (show, update, change-password) | Sanctum | — |
| `/face` | 2 (register, verify) | Sanctum | 10/min |
| `/attendance` | 5 (clock-in, clock-out, today, index, approve-wfa) | Sanctum | 5/5min |
| `/leave` | 5 (store, index, quota, show, destroy) | Sanctum | 10/min |
| `/overtime` | 4 (store, index, show, destroy) | Sanctum | 10/min |
| `/reimbursement` | 4 (store, index, show, destroy) | Sanctum | 10/min |
| `/approvals` | 3 (pending, approve, reject) | Sanctum | — |
| `/payroll` | 7 (index, generate, export-monthly/1721/bpjs, show, payslip) | Sanctum | — |
| `/employees` | 9 (index, show, pii, store, update, destroy, terminate, contract-end) | Sanctum + permission | — |
| `/knowledgebase` | 3 (chat, upload, destroy) | Sanctum | 20/min |

### Missing Views (route exists, file MISSING)

| Route Name | Path | Priority |
|---|---|---|
| `payroll.index` | `resources/views/payroll/index.blade.php` | HIGH |
| `approvals.index` | `resources/views/approvals/index.blade.php` | HIGH |
| `knowledge-base.index` | `resources/views/knowledge-base/index.blade.php` | MEDIUM |
| `assets.index` | `resources/views/assets/index.blade.php` | LOW (V2) |
| `loans.index` | `resources/views/loans/index.blade.php` | LOW (V2) |

### Design System Violations

#### Hardcoded Colors (33 HIGH)

| File | Lines | Violations |
|---|---|---|
| `two-factor-setup-modal.blade.php` | 14 | Lines 160,161,162,168,222,224,230,253,254,274,276,284,289,295: `stone-100/200/600/700/800/900`, `green-500`, `white`, `bg-white`, dark variants |
| `dashboard.blade.php` | 8 | Lines 4,5,7,8,10,11,14,15: `border-neutral-200`, `dark:border-neutral-700`, `stroke-gray-900/20`, `dark:stroke-neutral-100/20` |
| `navlist/group.blade.php` | 3 | Lines 16,27,37: `zinc-500`, `zinc-800/5`, `zinc-200`, `zinc-400`, `dark:text-white/80`, `dark:bg-white/30` |
| `recovery-codes.blade.php` | 2 | Lines 49,115: `zinc-200`, `zinc-100`, `dark:border-white/10`, `dark:bg-white/5` |
| `forgot-password.blade.php` | 1 | Line 26: `text-zinc-400` |
| `register.blade.php` | 2 | Line 62: `text-zinc-600`, `dark:text-zinc-400` |
| `verify-email.blade.php` | 2 | Line 8: `!text-green-600`, `!dark:text-green-400` |
| `auth-session-status.blade.php` | 1 | Line 6: `text-green-600` |

#### CSS Contrast Issues (DS-1)

| Token | Current | On #fffaf0 | WCAG AA | Fix Target |
|---|---|---|---|---|
| `--color-muted` (#6a6a6a) | 3.9:1 | ❌ | → #535353 |
| `--color-muted-soft` (#9a9a9a) | 2.5:1 | ❌ | → #888888 |
| Flux `text-zinc-400` (#a1a1aa) | 2.1:1 | ❌ | → neutral palette |
| Flux `text-zinc-500` (#71717a) | 3.5:1 | ❌ | → neutral palette |

### Security & Encryption

| Item | Status | Notes |
|---|---|---|
| CipherSweet (Employee PII) | ✅ | nik, phone, npwp, bank_account encrypted |
| Blind indexes (nik_hash, phone_hash, npwp_hash) | ✅ | `whereBlind()` queries |
| CipherSweet (FamilyDetail) | ⏳ | Post-MVP — PRD §17.1 CAT-003 |
| CipherSweet (Company.npwp) | ⏳ | Post-MVP |
| Sanctum token expiry | Never | By design (PWA reuse) |
| 2FA TOTP | ✅ | Fortify built-in |
| Password expiry 90d | ✅ | CheckPasswordExpired middleware |
| Force change password | ✅ | `password_changed` column |
| Rate limiting | ✅ | 11 inline + 2 Fortify named limiters |
| PII masking (API) | ✅ | EmployeeResource hides nik/phone/npwp/bank |
| Activity logging | ✅ | Spatie ActivityLog on all models |
| Soft deletes | ✅ | employees, leaves, overtimes, payrolls, attendances, depts, positions |

### Test Coverage

| Area | Files | Coverage |
|---|---|---|
| API Endpoints | 17 | Auth, Attendance, Employee, Face, KB, Leave, Overtime, Payroll, Reimbursement, Approvals, IDOR, Security, Contract |
| Auth (Feature) | 5 | Login, Register, 2FA, Password Reset, Email Verification, Password Confirm |
| Services (Unit) | 9 | Attendance, Leave, Overtime, Reimbursement, Approval, Face, Geofence, Profile, Payroll |
| Services (Feature) | 5 | Attendance, KB, PayslipPdf, PayrollExport, EmployeeTermination |
| Jobs | 2 | Edge cases, Basic |
| Middleware | 2 | Geofence, Device Detection |
| Observers | 1 | All 8 observers |
| Policies | 1 | All 8 policies |
| Cache | 1 | Cache integration |
| Console | 1 | All 9 commands |
| Postgres (Integration) | 1 | 28 PG-specific tests (pgvector, jsonb, migration guards) |
| Architecture | 1 | Laravel conventions |
| **Total** | **75 files** | **~1,121 tests / ~3,702 assertions** |

### PWA Status

| Asset | Exists | Notes |
|---|---|---|
| `public/manifest.json` | ✅ | |
| `public/service-worker.js` | ✅ | |
| `public/pwa-install.js` | ✅ | |
| `public/icon-192.svg` | ✅ | |
| `public/icon-512.svg` | ✅ | |
| `public/apple-touch-icon.png` | ✅ | |
| **`public/offline.html`** | ❌ | Missing — service worker fetch fails offline |
| Install banner (sidebar) | ✅ | `div#pwa-install-banner` in sidebar layout |

### Project Size

| Metric | Value |
|---|---|
| Total PHP files (excl vendor) | 2,370 |
| App PHP files (app/) | 193 |
| App LOC | ~12,070 |
| Blade views | 50 |
| Route files | 13 |
| Config files | 23 |
| Migrations | 45 |
| Seeders | 12 |
| Factories | 26 |
| Test files | 75 |
| Frontend assets (CSS/JS) | Vite + Tailwind v4 + Flux UI 2 |
| NPM packages | @tailwindcss/vite, vite, alpinejs, concurrently, laravel-vite-plugin |

## Verification Commands

| Purpose | Command |
|---|---|
| Format changed PHP | `vendor/bin/pint --dirty --format agent` |
| Fast focused tests | `php artisan test --compact --filter=Name` |
| Fast SQLite suite | `php artisan test --compact` |
| CI-style local suite | `composer test` |
| PostgreSQL integration | `composer test:pgsql` |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Schedule audit | `php artisan schedule:list` |

## Backend Readiness Milestones

| Milestone | Target Readiness | Gate |
|:---|---:|---|
| M1 — Scope + audit complete | 75-80% | P0 complete with endpoint/service matrices. |
| M2 — Feature gaps closed | 80-85% | P1 API/service tasks complete. Thin-coverage, Jobs, FormRequest, Embedding, Events/Mail — semua selesai. Notifikasi: PayrollPublished di-wire, 4 dead removed. |
| M3 — RAG production-ready | 85-90% | Laravel AI SDK adoption completed or explicitly deferred with stable custom implementation. |
| M4 — Test coverage complete | 95-97% | `composer test` passing (1,121 tests, 3,702 assertions). `composer test:pgsql` passing (28 tests, 61 assertions). 75 test files. CI PG job aktif. Remaining: FE-1 to FE-8 (Livewire components), DS-1 to DS-6 (design fixes). |
| M5 — Security hardened | 97-98% | Authorization, IDOR, PII, rate limit, secret audits complete. Permission drift ✅. IDOR hardening ✅ (6 gap-documentation tests). |
| M6 — Operations ready | 97-98% | Queue, scheduler, cache, storage, backup, deployment rehearsal complete. PG migration guards ✅ (4 dedicated tests). |
| M7 — API frozen | 99-100% | API contract stable. Web routes ✅ (18 smoke tests, P1-12). Semua endpoint API terdaftar, diaudit, dan dites. Blade-to-API integration menunggu frontend views. |

## Multi-Agent Audit — 2026-06-23

Lima agen paralel mengaudit seluruh codebase (193 app PHP files, 50 Blade views, 75 test files). Berikut temuan terkini setelah koreksi:

### 🔴 HIGH (3)

| # | Issue | File | Detail |
|---|-------|------|--------|
| 1 | **PHPStan BLOCKER** | `phpstan-baseline.neon:741-781` | Baseline referensi 3 file notifikasi yang sudah dihapus: `app/Notifications/LeaveApproved.php`, `LeaveRejected.php`, `LeaveRequestSubmitted.php`. 1219 baris baseline, ~160 ignore entries, 203 ignored errors. Harus regenerate baseline. |
| 2 | **buildContextSection duplikat** | `app/Services/GeminiClient.php:106` + `app/Services/KnowledgeBaseService.php:143` | Method 16 baris identik (DRY violation). Format context chunks untuk prompt LLM — harus diekstrak ke trait atau shared helper. Satu-satunya beda: PHPDoc `@param` ada di GeminiClient, tidak di KnowledgeBaseService. |
| 3 | **5 missing route views** | `routes/payroll.php`, `approval.php`, `knowledge-base.php`, `asset.php`, `loan.php` | Akan throw `ViewNotFound` jika diakses. payroll cuma punya `payslip.blade.php` (PDF template). approvals, knowledge-base, assets, loans — direktori views tidak ada sama sekali. |

### 🟠 MEDIUM (6)

| # | Issue | Detail |
|---|-------|--------|
| 1 | `bank_account_number` tanpa blind index | Employee model punya `bank_account_number` dienkripsi via CipherSweet tapi tidak ada blind index → tidak bisa `whereBlind()`. Deferred sampai V1.1 (belum ada use case search). |
| 2 | Password expiry middleware belum di-wire | `CheckPasswordExpired` sudah ada (7 web-layer tests) tapi belum didaftarkan di `bootstrap/app.php` → tidak aktif. |
| 3 | `Device.device_type` tanpa enum cast | Device model punya kolom `device_type` (string) tapi tidak di-cast ke enum → bisa diisi string arbitrary. |
| 4 | Observer tanpa `withoutEvents()` | 8 observer fire di setiap model event. `Model::withoutEvents()` tidak dipakai untuk batch operations → performance overhead. |
| 5 | Exception non-custom | Beberapa service method throw `\Exception` raw, bukan custom exception class (e.g., `BusinessRuleException`, `NotFoundException`). |
| 6 | `(int)` cast di prorata quota | `LeaveService::initializeBalance()` line 154: `(int) round()` — kalau quota leave type jadi fraksional (e.g., 12.5), truncate diam-diam. DB column `decimal(4,1)` — perlu konsisten. |

### 🟡 LOW (7)

| # | Issue | Detail |
|---|-------|--------|
| 1 | 5 model `HasFactory` tanpa factory | Approval, Asset, AssetHandover, Loan, LoanInstallment, PerformanceReview — semua V2 module, sengaja. |
| 2 | 6 redundant indexes | Contoh: `users_email_unique` + `users_email_index` — unique sudah include index. |
| 3 | `EmployeeSeeder` tidak idempotent | Jalan ulang `php artisan db:seed --class=EmployeeSeeder` bikin duplikat employee. |
| 4 | Default password di `.env.example` | Super-admin password default harus diganti dengan placeholder. |
| 5 | `TrustProxies` allow all | `trustProxies(at: '*')` — ok untuk dev, perlu di-tighten per deployment (Cloudflare/Load Balancer IP). |
| 6 | 16 unused private methods | Tersebar di services/controllers — code coverage tapi tidak dipanggil. |
| 7 | 13 controller tanpa `declare(strict_types=1)` | Semua API controllers — perlu ditambahkan untuk type safety. |

### Koreksi Temuan Sebelumnya (3)

| Temuan Lama | Status Sebenarnya | Penjelasan |
|-------------|-------------------|------------|
| `{!! !!}` XSS di 5 Blade files | ✅ **False positive** | 18 dari 19 `{!! !!}` ada di vendor Livewire pagination templates (standard). Hanya 1 di kode sendiri: QR code SVG di `two-factor-setup-modal.blade.php:233` — legitimate karena SVG tidak bisa di-escape. |
| `processOfflineSync` dead code | ✅ **Tidak pernah ada** | Method tidak ditemukan di `AttendanceService` atau manapun. Tidak ada offline sync functionality di seluruh `app/`. |
| `DemoDataSeeder` kolom salah | ✅ **Tidak benar** | Seeder menulis ke kolom standar (`full_name`, `employee_number`, `nik`). Tidak ada kolom `demo_*` di migrations. Seeder sudah benar. |

### Data ESS Blade (8 static views, 0 Livewire)

| Bladename | LOC | Isi |
|-----------|:---:|-----|
| `attendance/clock-in.blade.php` | 42 | Card jam realtime (hardcoded 08:02 AM), tombol Clock In/Out, Today's Activity (hardcoded), link ke history |
| `attendance/index.blade.php` | 67 | Filter bulan, tabel attendance (3 baris hardcoded), summary cards (18 days worked, 2 late, 4.5h OT) |
| `leaves/apply.blade.php` | 44 | Form leave type (select), start/end date, reason textarea, cancel/submit |
| `leaves/index.blade.php` | 63 | Tombol Apply Leave, quota cards (4 kolom: Annual 8/12, Sick 3/6, Personal 2/3, Pending 1), tabel 2 baris hardcoded |
| `overtimes/apply.blade.php` | 40 | Form date, start/end time, reason, cancel/submit |
| `overtimes/index.blade.php` | 62 | Tombol Request Overtime, tabel 3 baris hardcoded, summary cards (4.5h total, 1.5h pending, 3.0h approved) |
| `reimbursements/apply.blade.php` | 51 | Form category (select 5), amount, expense date, description, receipt upload (opsional) |
| `reimbursements/index.blade.php` | 62 | Tombol New Claim, tabel 3 baris hardcoded (Travel/Medical/Supplies), summary cards (Rp1.325jt total) |

Semua Static Blade — **0 Livewire binding**, 0 wire:model, 0 wire:click, 0 API integration. Data hardcoded. Layout: `<x-layouts::app.sidebar>`.

### PHPStan Baseline Detail

| Metrik | Nilai |
|--------|-------|
| Total baris `phpstan-baseline.neon` | 1,219 |
| Total ignore entries | ~160 |
| Total ignored errors | ~203 |
| Files referenced | 38 files in `app/` |
| Deleted files referenced | 3 (`LeaveApproved.php`, `LeaveRejected.php`, `LeaveRequestSubmitted.php`) |
| Level | 5 |
| Scan path | `app/` only |

**Exact baseline lines untuk deleted files:**
- `app/Notifications/LeaveApproved.php`: lines 745, 751 (6 ignored errors: $name property not found ×2, format() called on string ×4)
- `app/Notifications/LeaveRejected.php`: lines 757, 763 (6 ignored errors: $name property not found ×2, format() called on string ×4)
- `app/Notifications/LeaveRequestSubmitted.php`: lines 769, 775, 781 (10 ignored errors: $full_name ×4, $name ×2, format() ×4)

**Fix:** Hapus 3 entry tersebut dari baseline + regenerate via `vendor/bin/phpstan analyse --generate-baseline`.
