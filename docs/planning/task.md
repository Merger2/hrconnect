# Task Tracker — Backend 100% Completion

> Source of truth untuk pekerjaan backend aktif sebelum pindah ke frontend.
> Last updated: 2026-06-18 (S-3 PII response audit completed. Test suite: 687 / 4,020 assertions).
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
| Backend core services | ~75% | 15 services exist. RAG refactor (RAG-1–9) belum dimulai. Pinecone search masih stub (return []). All services now have at least some test coverage (ProfileService ✅ +5 tests). |
| Backend API layer | ~90% | 51 routes at `/api/v1`, 13 controllers, all module routes active. All 11 endpoint groups have dedicated proof test files. 654 tests total. 13 web GET routes (module index/apply pages) have missing views but routes exist. 1 Livewire component (Logout). |
| Production hardening | ~65-70% | IDOR audit done (0 vulnerable). Security/PII audit partial — enkripsi verified, tapi S-4/S-5/S-7/S-8 belum. Queue/scheduler, deployment rehearsal belumlah. |
| Frontend integration | ~10-20% | Ditunda sampai backend dinyatakan freeze; UI modul bisnis belum menjadi fokus file ini. |
| Test suite | 687 tests / 4,020 assertions | Fast SQLite (default) dan PostgreSQL integration suite (`phpunit.pgsql.xml`). Gaps: observer/cache, notification, factories. Comprehensive gap audit completed — see P0-6. |

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
| Jobs | Edge cases | 9 tests cover basic dispatch only; no failed/retry/log edge cases |
| Events/Mail | Architecture | No `app/Events/`, `app/Listeners/`, `app/Mail/` directories exist |
| Integration | Blade-to-API | Zero tests for end-to-end frontend-backend flow |
| Web routes | Fortify + dashboard | 25+ GET routes (auth pages, dashboard, settings) — zero smoke tests |
| Middleware | `DeviceDetection` | UA-parsing middleware — zero test coverage |
| Middleware | `GeofenceValidation` | Middleware-layer test coverage zero (service-layer tested via GeofenceServiceTest) |
| Commands | `detect-missed-clock` | Daily scheduled — zero test coverage |
| Commands | `auto-approve-wfa` | Daily scheduled — zero test coverage |
| Commands | `send-reminders` | Weekday scheduled — zero test coverage |
| Commands | `knowledgebase:index` | Manual — zero test coverage |

### Thin Coverage (<10 assertions)

| File | Tests | Notes |
|------|------:|-------|
| `PayslipPdfServiceTest.php` | 4 | `buildTemplateData()` via reflection only; DomPDF facade untestable |
| `PayrollExportServiceTest.php` | 6 | XLSX creation via inline `new Writer` (OpenSpout) — hard to mock |
| `EmployeeTerminationServiceTest.php` | 6 | Happy path only; static `activity()` calls |
| `OvertimeServiceTest.php` (unit) | 3 | Creation + overnight logic + cancel |
| `EmbeddingServiceTest.php` | 6 | Chunk + format only; `new PdfParser` untestable without real PDF |
| `FaceRecognitionServiceTest.php` | 6 | Dimension + validation; `nearestNeighbors()` pgvector query never invoked |
| `GeminiClientTest.php` | 7 | Mock mode only; `sleep()` retry logic slows tests |
| `GeofenceServiceTest.php` | 11 | OK but edge-case light |
| `KnowledgeBaseServiceTest.php` | 11 | OK but RAG flow untested |
| `PayrollCalculatorService` | Scattered | `generatePayroll()` (~150 baris) tanpa dedicated test file; coverage tersebar di 3 file |

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
| Docs | `docs/INDEX.md` outdated; `docs/testing/testing-strategy.md` references nonexistent files |

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
| P1-1a | Password expiry + logout-all: fixed 2 bugs (ResetUserPassword + CreateNewUser set password_changed_at). 7 tests: change-password resets clock, forgot-password sets password_changed_at, logout-all token invalidation, edge cases. | `php artisan test --compact --filter='PasswordExpiry|logout-all'` -> 7 passed, 17 assertions. | ✅ |

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
|---|---|---|---|---|
| P0-1 | Freeze backend V1 scope | Daftar final fitur V1 dan V2 | 🚧 |
| P0-2 | Mark V2 modules as out-of-scope for backend 100% | Loan/Kasbon, Asset Management, Performance Review, WhatsApp Notifications tetap V2 kecuali user ubah scope | 🚧 |
| P0-3 | Build endpoint audit matrix for all `/api/v1` routes | ✅ Inventory complete: 51 API routes grouped below. ✅ 401 smoke tests for all routes. ✅ Proof tests for all 11 endpoint groups. 🚧 Remaining: deeper regression scenarios per group (duplicate processing, race conditions, edge cases). | 🚧 |
| P0-4 | Build service audit matrix | Matrix service -> workflow -> transaction -> cache -> tests -> status | 🚧 |
| P0-5 | Decide remaining product decisions | Sanctum token expiration, Fortify registration in production, searchable bank-account blind index | 🚧 |
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
| KnowledgeBase | 3 | `KnowledgeBaseController` | `ChatRequest`, `UploadDocumentRequest`; destroy uses `Request` | `auth:sanctum`, throttle chat, `manage_knowledgebase` for mutations | ✅ | KnowledgeBaseProofTest covers chat, upload, delete, auth/permission (+14 tests). Gaps: Laravel AI SDK RAG refactor and fallback tests (RAG-1–9). |

## P1 — API And Service Completion

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|---|
| P1-1 | Auth/Profile API audit | ✅ AuthProof (+8 tests: login→2FA, TOTP, rate limit, forgot-password). 🚧 Remaining: password expiry behavior, logout-all token revoke test. | 🚧 |
| P1-2 | Employee API audit | ✅ EmployeeProof (+8 tests: auth/permission gating). Combined with ControllerHttpTest coverage (11 existing tests) = comprehensive. 🚧 Remaining: PII audit log verification. | 🚧 |
| P1-3 | Attendance API audit | ✅ AttendanceProof (+20 tests: clock-in/out PIN+GPS+WFA, today, index, WFA approval). 🚧 Remaining: face recognition 128D flow. | 🚧 |
| P1-4 | Leave API audit | ✅ LeaveProof (+9 tests: auth/permission/delete/owner gaps). Combined with LeaveAndOvertimeTest (9 existing) = comprehensive. 🚧 Remaining: approval quota deduction edge cases. | 🚧 |
| P1-5 | Overtime API audit | ✅ OvertimeProof (+6 tests: auth/permission/owner gaps). Combined with LeaveAndOvertimeTest (8 existing) = covered. 🚧 Remaining: payroll-impact proof. | 🚧 |
| P1-6 | Reimbursement API audit | ✅ ReimbursementProof (+10 tests: auth/permission/state gaps). Combined with ControllerHttpTest (5 existing) = covered. 🚧 Remaining: payment workflow edge cases. | 🚧 |
| P1-7 | Approval API audit | ✅ ApprovalProof (+8 tests: auth/validation/resource gaps). Combined with ControllerHttpTest (7 existing) = covered. | 🚧 |
| P1-8 | Payroll API audit | ✅ PayrollProof (+20 tests: list, show, generate, payslip gating, exports, permission). 🚧 Remaining: lock behavior (published/paid), concurrent generate race. | 🚧 |
| P1-9 | KnowledgeBase API audit | ✅ KnowledgeBaseProof (+14 tests: chat mock mode, upload partialMock+Queue::fake, delete, auth/permission). | 🚧 |
| P1-10 | Standardize API resources/responses | ✅ Code serialization cleanup done: no API controller `format*()` methods remain. 🚧 Remaining: final response-envelope decision and API contract tests (`API-1`, `T-11`). | 🚧 |
| P1-11 | ProfileService test coverage | ✅ 5 tests written (getProfile with/without employee, updateProfile, changePassword success, changePassword wrong current). Refactored ProfileController to use ProfileService (eliminated dead code). | ✅ |
| P1-12 | Web route smoke tests | 25+ web GET routes (Fortify auth pages, dashboard, settings) have zero test coverage. Add smoke tests. | ✅ |
| P1-13 | Middleware test coverage | Add tests for `DeviceDetection` and `GeofenceValidation` middleware (2 of 3 untested). | ⏳ |
| P1-14 | FormRequest validation tests | Add validation rule tests for 6 untested list-endpoint FormRequests + `UpdateProfileRequest`. | ⏳ |
| P1-15 | Scheduled command tests | ✅ 12 tests added: detect-missed-clock (3), auto-approve-wfa (3), send-reminders (2), knowledgebase:index (3). **2 bugs found + fixed** in AutoApproveWfaCommand (enum comparison with ->value vs enum, missing $timeoutDays in closure scope). DetectMissedClockCommand fixed (referenced nonexistent columns). All 9 commands now have test coverage. | ✅ |

## P1 — RAG Production Refactor With Laravel AI SDK

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| RAG-1 | Decide Laravel AI SDK adoption boundary | AI SDK handles provider, embeddings, agent, structured output, streaming/testing; pgvector/pg_trgm/domain storage stay custom | ⏳ |
| RAG-2 | Add and configure `laravel/ai` | Package installed, config published, env keys mapped, migrations understood, no conflict with current KB schema | ⏳ |
| RAG-3 | Replace custom Gemini generation behind adapter | Existing `KnowledgeBaseService` behavior preserved while generation uses AI SDK path | ⏳ |
| RAG-4 | Replace/adapter embeddings through AI SDK | `text-embedding-004` remains 768D; PostgreSQL vector storage remains `knowledge_bases.embedding` | ⏳ |
| RAG-5 | Create `HrKnowledgeBaseAgent` | Prompt/instructions centralized; answer refuses hallucination when no source exists | ⏳ |
| RAG-6 | Add structured RAG output | Response includes `answer`, `sources`, `confidence`, `fallback`, `model` | ⏳ |
| RAG-7 | Preserve `pg_trgm` fallback | Gemini/embedding failure falls back to keyword search without crashing chat endpoint | ⏳ |
| RAG-8 | Add optional streaming endpoint plan | Streaming API contract decided for frontend chat; implementation if backend scope includes it | ⏳ |
| RAG-9 | Add AI SDK fake tests | Agent, embedding, and fallback tests do not require real external API calls | ⏳ |

## P1 — Test Coverage Completion

| ID | Task | Minimum Coverage | Status |
|---|---|---|---|
| T-1 | Full API endpoint tests | Happy path, validation error, unauthorized, forbidden, state conflict for all V1 endpoint groups | 🚧 | Proof tests done for all 11 groups + policy tests + command tests = 585 total across 58 files. Remaining: deeper edge cases per group, web route smoke tests (T-13), form request validation tests (P1-14). |
| T-2 | Role/permission matrix tests | super-admin, hr-manager, finance, manager, employee access boundaries | ⏳ |
| T-3 | PII/CipherSweet tests | `whereBlind()`, `Rule::encryptedUnique()`, raw encrypted values, PII reveal audit logging | ✅ |
| T-4 | Attendance regression tests | Face success, face fail -> PIN, PIN streak, fake GPS, outside geofence, WFA, duplicate clock-in/out | ⏳ |
| T-5 | Leave/approval regression tests | Final approval quota deduction, reject no deduction, no negative balance, overlap, wrong approver | ⏳ |
| T-6 | Payroll regression tests | Generate, regenerate draft, reject published/paid changes, payslip gating, exports, concurrent lock behavior | 🚧 | PayrollProof covers generate, payslip gating, exports (+10 tests). Remaining: regenerate draft, reject published/paid, concurrent lock. |
| T-7 | KnowledgeBase/RAG tests | Upload, chunk, embedding job, vector search, fallback keyword search, structured response | 🚧 | KnowledgeBaseProof covers chat, upload, delete (+14 tests with mock mode + partialMock). Remaining: RAG refactor (RAG-1–9), fallback keyword search test. |
| T-8 | Queue/job tests | Payroll, payslip PDF, embedding, notifications failed handlers and retry/log behavior | ✅ |
| T-9 | Observer/cache tests | TaxConfig, BpjsConfig, Holiday, Employee, Attendance, Leave, Payroll invalidation behavior | ⏳ |
| T-10 | PostgreSQL integration expansion | pgvector, CipherSweet, constraints, payroll/approval locking, migration extension guards | 🚧 |
| T-11 | OpenAPI/Scramble contract tests | Representative `/api/v1/*` paths, bearer security, public routes, request schema alignment | ⏳ |
| T-12 | Remove/replace stale docs test references | `docs/testing/testing-strategy.md` reflects actual test suite, not nonexistent files | ⏳ |
| T-13 | Web route smoke tests | ✅ 18 smoke tests: 6 public auth pages (200), 6 unauthenticated redirects (302), 3 authenticated pages (200), 2 special pages (email_verify→dashboard, security→confirm-password), 1 2FA-challenge redirect. | ✅ |
| T-14 | Livewire component test | Add basic render test for `app/Livewire/Actions/Logout.php` | ⏳ |
| T-15 | ProfileService test | ✅ 5 tests (getProfile with/without employee, updateProfile, changePassword success/wrong current). Plus refactored ProfileController to use the service (eliminated dead code). | ✅ |
| T-16 | Pinecone search stub test | Test that Pinecone search gracefully degrades (current stub returns []) | ⏳ |
| T-17 | Policy direct tests | ✅ 15 new boundary tests for `AttendancePolicy` (6) and `OvertimePolicy` (9) covering view self/other/team, create, update/delete status gates, approveLevel1/2 scoping. All 8 policies now have direct tests. | ✅ |
| T-18 | Factory gap closure | Create factory classes for high-priority models: `Approval`, `Device`, `CompanySetting`, `FamilyDetail`, `PayrollAdjustment`, `PayrollItem`, `ShiftSchedule` | ⏳ |
| T-19 | Permission drift audit | `MANAGE_REIMBURSEMENTS` in enum but unassigned — decide: assign to finance or remove from enum | ⏳ |
| T-20 | PayrollCalculatorService dedicated tests | Extract `generatePayroll()` coverage from integration tests into dedicated service test file | ⏳ |
| T-21 | FaceRecognitionService pgvector test | Add test that actually invokes `nearestNeighbors()` against DB | ⏳ |
| T-22 | Livewire component test | Add basic render test for `app/Livewire/Actions/Logout.php` | ⏳ |
| T-23 | Overtime store policy call | ✅ Added `$this->authorize('create', Overtime::class)` to `OvertimeController::store()`. Fixed 2 test expectations (404→403). | ✅ |
| T-24 | Reimbursement store policy call | ✅ Added `$this->authorize('create', Reimbursement::class)` to `ReimbursementController::store()`. | ✅ |
| T-25 | Employee API team scope decision | Decide if API should scope employees by manager team (currently deferred to Livewire only) | ⏳ |

## P1 — Security And Data Protection Hardening

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| S-1 | Authorization audit | ✅ All 51 routes audited: 4 public (no auth), 47 protected with `auth:sanctum`. ~28 routes use `$this->authorize()`. 15 use permission middleware. 73 endpoint tests pass. 10 low-severity gaps documented (approvals no policy, leave/quota no policy, processContractEnd no policy, approveWfa inconsistent). | ✅ |
| S-2 | IDOR audit | ✅ Audit complete: 0 vulnerable methods. 14 partial/defense-in-depth gaps found: Employee team scoping in API (by design per policy comment), Overtime/Reimbursement store not invoking policy, all `viewAny` policies permission-only. See P0-6 IDOR section. | ✅ |
| S-3 | PII response audit | ✅ All 17 Resources audited: EmployeeResource properly hides nik/phone/npwp/bank_account/pin/face_embedding via #[Hidden] + manual exclusion. ProfileResource masks phone/bank_account. EmployeePiiResource gated by manage_employees + audit log. All controllers use manual field selection (no direct ->toArray()). No HIGH/CRITICAL leaks. Only finding: EmployeeResource exposes address_detail raw (LOW). | ✅ |
| S-4 | Log/audit privacy audit | Logs and activity records do not store raw sensitive PII or secrets | ⏳ |
| S-5 | Rate-limit audit | Login, 2FA, face verify, attendance writes, leave/overtime/reimbursement writes, KB chat/upload are throttled appropriately | 🚧 |
| S-6 | Secret audit | No real API keys or production credentials committed; rotate any exposed key if real | ⏳ |
| S-7 | File upload audit | PDF/reimbursement upload validates mime, size, storage failures, filename safety, and authorization | ⏳ |
| S-8 | Production env checklist | `APP_DEBUG=false`, `APP_ENV=production`, secure `APP_KEY`, `CIPHERSWEET_KEY`, AI keys, DB credentials | ⏳ |

## P2 — Operations Readiness

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| O-1 | Queue readiness | Worker command final, queue names documented, failed jobs observable/retryable, job timeouts/backoff reviewed | ⏳ |
| O-2 | Scheduler readiness | `php artisan schedule:list` verified; production cron documented; overlapping prevented | ⏳ |
| O-3 | Cache readiness | No `Cache::tags()` with database cache; all cached models have invalidation path | ⏳ |
| O-4 | Storage readiness | Payslip/export/KB/reimbursement file disks, permissions, cleanup policy, and failure behavior verified | ⏳ |
| O-5 | Health and observability | Health endpoint, logs, queue failures, scheduler logs, and alertable failure modes documented | ⏳ |
| O-6 | Backup and restore rehearsal | PostgreSQL backup and restore tested at least once with extension compatibility | ⏳ |
| O-7 | Deployment rehearsal | Fresh production-like deploy, migrate, seed required data, queue, scheduler, and smoke API flow succeed | ⏳ |

## P2 — API Contract Freeze For Frontend

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| API-1 | Standard response envelope | Success/error response shapes finalized and applied/documented | ⏳ |
| API-2 | Error code contract | 200/201/204/401/403/404/409/422/429/500 usage finalized and tested | ⏳ |
| API-3 | Pagination/filter contract | List endpoint pagination, filters, sort fields, and meta shape finalized | ⏳ |
| API-4 | Enum contract | All frontend-facing enum values, labels, and color names documented/frozen | ⏳ |
| API-5 | File upload/download contract | KB upload, reimbursement attachment, payroll export, payslip download behavior finalized | ⏳ |
| API-6 | Auth/token contract | Login, 2FA, logout, logout-all, token lifetime, and password expiry behavior finalized | ⏳ |
| API-7 | Generate/export docs | Scramble/OpenAPI output generated and checked into agreed location or CI artifact | ⏳ |

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
|---|---:|---|
| M1 — Scope + audit complete | 75-80% | P0 complete with endpoint/service matrices. |
| M2 — Feature gaps closed | ~75% | P1 API/service tasks complete. ProfileService zero coverage eliminated. Policy boundary tests done (all 8). Command tests done (all 9, with 3 bugfixes). Remaining gaps: web routes, middleware, form requests, factories, queue edge cases, Pinecone stub. |
| M3 — RAG production-ready | 85-90% | Laravel AI SDK adoption completed or explicitly deferred with stable custom implementation. |
| M4 — Test coverage complete | 90-93% | `composer test` and `composer test:pgsql` pass with required coverage. |
| M5 — Security hardened | 93-95% | Authorization, IDOR, PII, rate limit, secret audits complete. |
| M6 — Operations ready | 95-98% | Queue, scheduler, cache, storage, backup, deployment rehearsal complete. |
| M7 — API frozen | 98-100% | API contract stable for frontend implementation. |
