# Task Tracker — Backend 100% Completion

> Source of truth untuk pekerjaan backend aktif sebelum pindah ke frontend.
> Last updated: 2026-06-18 (updated 2026-06-18: P0-3a + P0-3b completed, P0/P1 status bumped to 🚧).
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
| Backend core services | ~72% | 15 services exist (attendance, face, geofence, leave, approval, payroll, reimbursement, KB/RAG, termination, dll). RAG refactor (RAG-1–9) belum dimulai. |
| Backend API layer | ~65% | 51 routes at `/api/v1`, 13 controllers, all module routes active. Attendance/Payroll/KB endpoints masih partial test coverage. |
| Production hardening | ~55-65% | Perlu audit endpoint penuh, security/PII, queue/scheduler, deployment rehearsal, dan API contract freeze. |
| Frontend integration | ~10-20% | Ditunda sampai backend dinyatakan freeze; UI modul bisnis belum menjadi fokus file ini. |
| Test suite | 505 tests / 3,683 assertions | Fast SQLite (default) dan PostgreSQL integration suite (`phpunit.pgsql.xml`). Gaps: queue/job, observer/cache, role matrix. |

## Completed Summary

- Historical backend bugfixes `B-1` sampai `B-53` selesai atau dibatalkan by-design.
- Comprehensive audit critical/high findings mayoritas selesai: `C-1`, `C-2`, `C-3`, `C-6`, `H-1` sampai `H-10`, `H-12`, `H-13`, dan mayoritas `M-*` sudah fixed/reviewed.
- Test infrastructure split sudah ada: fast SQLite suite (`phpunit.xml`) dan PostgreSQL integration suite (`phpunit.pgsql.xml`).
- CI sudah memiliki PostgreSQL job dengan `pgvector/pgvector:pg16` dan extension `vector`, `pg_trgm`, `pgcrypto`.
- Core guards sudah diterapkan: pgvector SQLite fallback cast, CipherSweet test key, payroll/leave/overtime/reimbursement race-condition fixes, PII endpoint split, and API throttling for core write endpoints.

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
| P0-3 | Build endpoint audit matrix for all `/api/v1` routes | ✅ Inventory complete: 51 API routes grouped below. ✅ 401 smoke tests for all routes. 🚧 Remaining: per-endpoint policy/resource proof pass. | 🚧 |
| P0-4 | Build service audit matrix | Matrix service -> workflow -> transaction -> cache -> tests -> status | 🚧 |
| P0-5 | Decide remaining product decisions | Sanctum token expiration, Fortify registration in production, searchable bank-account blind index | 🚧 |

## API Endpoint Audit Matrix

Inventory source: `php artisan route:list --path=api --except-vendor` on 2026-06-18.

| Group | Routes | Controllers | Request Coverage | Auth/Permission | Test Status | Gaps |
|---|---:|---|---|---|---|---|
| Health | 1 | `HealthController` | N/A | Public | Needs smoke proof | None known |
| Auth public | 3 | `AuthController@login`, `twoFactorChallenge`, `forgotPassword` | `LoginRequest`, `TwoFactorChallengeRequest`, `ForgotPasswordRequest` | Public + throttle | Partial | Confirm OpenAPI/security contract |
| Auth protected/user | 3 | `AuthController@logout`, `logoutAll`, `me` | Basic `Request` | `auth:sanctum` | Partial | Token lifetime decision `P0-5` |
| Profile | 3 | `ProfileController` | `UpdateProfileRequest`, `ChangePasswordRequest`; show uses `Request` | `auth:sanctum` | Partial | Final profile response contract |
| Face | 2 | `FaceController` | `RegisterFaceRequest` | `auth:sanctum` + throttle | Partial | Browser/client face-api flow deferred to FE |
| Attendance | 5 | `AttendanceController` | `ClockInRequest`, `ClockOutRequest`, `ListAttendanceRequest`; `today/approveWfa` use `Request` | `auth:sanctum`, throttles on writes | Partial | Need full API tests for today/index/WFA approval edge cases |
| Leave | 5 | `LeaveController` | `StoreLeaveRequest`, `ListLeaveRequest`; quota/show/delete use `Request` | `auth:sanctum`, policy, throttle on store | Partial | Quota response contract and role matrix proof |
| Overtime | 4 | `OvertimeController` | `StoreOvertimeRequest`, `ListOvertimeRequest`; show/delete use `Request` | `auth:sanctum`, policy, throttle on store | Partial | Role matrix and payroll-impact proof |
| Reimbursement | 4 | `ReimbursementController` | `StoreReimbursementRequest`, `ListReimbursementRequest`; show/delete use `Request` | `auth:sanctum`, policy, throttle on store | Partial | Upload failure and payment-state proof |
| Approval | 3 | `ApprovalController` | `PendingApprovalsRequest`, `ApproveRequest`, `RejectRequest` | `auth:sanctum`, policy/service checks | Partial | L1/L2 and double-processing coverage exists but needs matrix link |
| Payroll | 7 | `PayrollController` | `ListPayrollRequest`, `GeneratePayrollRequest`, `ExportMonthlyRequest`, `ExportPeriodRequest`; show/payslip use `Request` | `auth:sanctum`, policy, `process_payroll` permissions | Partial | Export/download contract and published-lock proof |
| Employees | 8 | `EmployeeController`, `EmployeeTerminationController` | `ListEmployeeRequest`, `StoreEmployeeRequest`, `UpdateEmployeeRequest`, `TerminateEmployeeRequest`; contract-end uses inline validation | `auth:sanctum`, `view_employees`/`manage_employees`, policies | Partial | Contract-end FormRequest decision; PII audit proof |
| KnowledgeBase | 3 | `KnowledgeBaseController` | `ChatRequest`, `UploadDocumentRequest`; destroy uses `Request` | `auth:sanctum`, throttle chat, `manage_knowledgebase` for mutations | Partial | Laravel AI SDK RAG refactor and fallback tests |

## P1 — API And Service Completion

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|---|
| P1-1 | Auth/Profile API audit | Login, logout, logout-all, 2FA challenge, forgot password, change password, token revoke, password expiry behavior documented/tested | 🚧 |
| P1-2 | Employee API audit | CRUD, PII reveal, encrypted uniqueness, termination, contract-end processing, ownership/permission checks tested | 🚧 |
| P1-3 | Attendance API audit | Clock-in/out, today, index, WFA approval, face/PIN fallback, fake GPS, geofence, WFA note, duplicate state conflicts tested | 🚧 |
| P1-4 | Leave API audit | Apply, index, show, quota, delete/cancel, approval quota deduction, overlap, insufficient balance tested | 🚧 |
| P1-5 | Overtime API audit | Submit, index, show, delete/cancel, approval workflow, overnight and payroll impact tested | 🚧 |
| P1-6 | Reimbursement API audit | Submit, index, show, delete, approval/payment state, upload failure behavior tested | 🚧 |
| P1-7 | Approval API audit | Pending, approve, reject, L1/L2 sequencing, wrong approver, double-processing tested | 🚧 |
| P1-8 | Payroll API audit | List, show, generate, payslip, monthly export, 1721-A1 export, BPJS export, lock behavior and race handling tested | 🚧 |
| P1-9 | KnowledgeBase API audit | Chat, upload, delete, owner morph, source citations, fallback behavior, authorization tested | 🚧 |
| P1-10 | Standardize API resources/responses | ✅ Code serialization cleanup done: no API controller `format*()` methods remain. 🚧 Remaining: final response-envelope decision and API contract tests (`API-1`, `T-11`). | 🚧 |

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
| T-1 | Full API endpoint tests | Happy path, validation error, unauthorized, forbidden, state conflict for all V1 endpoint groups | 🚧 |
| T-2 | Role/permission matrix tests | super-admin, hr-manager, finance, manager, employee access boundaries | ⏳ |
| T-3 | PII/CipherSweet tests | `whereBlind()`, `Rule::encryptedUnique()`, raw encrypted values, PII reveal audit logging | 🚧 |
| T-4 | Attendance regression tests | Face success, face fail -> PIN, PIN streak, fake GPS, outside geofence, WFA, duplicate clock-in/out | ⏳ |
| T-5 | Leave/approval regression tests | Final approval quota deduction, reject no deduction, no negative balance, overlap, wrong approver | ⏳ |
| T-6 | Payroll regression tests | Generate, regenerate draft, reject published/paid changes, payslip gating, exports, concurrent lock behavior | 🚧 |
| T-7 | KnowledgeBase/RAG tests | Upload, chunk, embedding job, vector search, fallback keyword search, structured response | ⏳ |
| T-8 | Queue/job tests | Payroll, payslip PDF, embedding, notifications failed handlers and retry/log behavior | ⏳ |
| T-9 | Observer/cache tests | TaxConfig, BpjsConfig, Holiday, Employee, Attendance, Leave, Payroll invalidation behavior | ⏳ |
| T-10 | PostgreSQL integration expansion | pgvector, CipherSweet, constraints, payroll/approval locking, migration extension guards | 🚧 |
| T-11 | OpenAPI/Scramble contract tests | Representative `/api/v1/*` paths, bearer security, public routes, request schema alignment | ⏳ |
| T-12 | Remove/replace stale docs test references | `docs/testing/testing-strategy.md` reflects actual test suite, not nonexistent files | ⏳ |

## P1 — Security And Data Protection Hardening

| ID | Task | Acceptance Criteria | Status |
|---|---|---|---|
| S-1 | Authorization audit | Every non-public endpoint has correct `auth:sanctum`, permission middleware, and/or policy | 🚧 |
| S-2 | IDOR audit | Employee/manager/finance/hr access boundaries tested on show/update/delete/download endpoints | ⏳ |
| S-3 | PII response audit | General resources never expose NIK, phone, NPWP, bank account, PIN, or face embedding | 🚧 |
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
| M2 — Feature gaps closed | 80-85% | P1 API/service tasks complete. |
| M3 — RAG production-ready | 85-90% | Laravel AI SDK adoption completed or explicitly deferred with stable custom implementation. |
| M4 — Test coverage complete | 90-93% | `composer test` and `composer test:pgsql` pass with required coverage. |
| M5 — Security hardened | 93-95% | Authorization, IDOR, PII, rate limit, secret audits complete. |
| M6 — Operations ready | 95-98% | Queue, scheduler, cache, storage, backup, deployment rehearsal complete. |
| M7 — API frozen | 98-100% | API contract stable for frontend implementation. |
