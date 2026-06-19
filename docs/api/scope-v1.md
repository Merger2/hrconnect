# Backend V1 Scope — Freeze Document

**Date:** 2026-06-18
**Status:** FINAL — No more additions to V1 scope.

## V1 — Core Modules (Implemented)

| # | Module | Routes | Status | Notes |
|---|--------|--------|--------|-------|
| 1 | Health | 1 | ✅ Live | `GET /health` — public |
| 2 | Auth | 6 | ✅ Live | Login, 2FA, forgot-password, logout, logout-all, user info. Fortify-powered. |
| 3 | Profile | 3 | ✅ Live | Read/update profile, change password. Password expiry enforced. |
| 4 | Face Recognition | 2 | ✅ Live | Register face (128D vector), verify face (cosine distance ≤0.15). PIN fallback. |
| 5 | Attendance | 5 | ✅ Live | Clock-in/out (PIN/GPS/face/WFA), today status, history, WFA approval. Geofence validation + anti-fake-GPS. |
| 6 | Leave | 5 | ✅ Live | CRUD + quota. Approval workflow (L1/L2). Proof file upload (optional). |
| 7 | Overtime | 4 | ✅ Live | CRUD + approval workflow. Overtime rate calculation. |
| 8 | Reimbursement | 4 | ✅ Live | CRUD + approval workflow. Receipt upload. Link to payroll. |
| 9 | Approval | 3 | ✅ Live | Generic approval workflow (L1 Supervisor → L2 Manager). Approve/reject/pending list. |
| 10 | Payroll | 7 | ✅ Live | Generate (async), payslip PDF, exports (monthly/1721-A1/BPJS). Lock on PUBLISHED/PAID. |
| 11 | Employee Directory | 8 | ✅ Live | CRUD, PII reveal (audited), termination, mass contract-end. |
| 12 | KnowledgeBase RAG | 3 | ✅ Live | PDF upload, chat via Gemini (vector search → pg_trgm fallback). |
| | **TOTAL** | **51** | **✅ All live** | 13 controllers, 15 services, 695 tests |

## V2 — Deferred Modules (Not In Scope)

| Module | Tables Exist? | Routes? | Reason |
|--------|-------------|---------|--------|
| Loan / Kasbon | ❌ No | ❌ No | Not implemented. Tables do not exist. |
| Asset Management | ❌ No | ❌ No | Not implemented. |
| Performance Review | ❌ No | ❌ No | Not implemented. |
| WhatsApp Notifications | ❌ No | ❌ No | Not implemented. No `app/Notifications/WhatsApp*` files. |

## V1 Design Decisions

### Token Lifetime
Sanctum tokens **never expire** (`expiration = null`). This is intentional for PWA use case where users log in once and reuse the token until explicit logout. Mitigation: logout-all revokes all tokens server-side. Can be overridden via `SANCTUM_TOKEN_EXPIRATION` env var if needed later.

### Fortify Registration
Registration is **disabled** in production. New employees are created by HR via `POST /api/v1/employees` (requires `manage_employees`). Fortify registration (`Features::registration()`) is for development convenience only — use `register` middleware group to disable in production.

### Bank Account Blind Index
**Deferred** — bank_account_number is encrypted via CipherSweet but has no blind index (unlike NIK/phone/NPWP). No current use case requires "search employee by bank account". Add blind index if needed in V1.1.

### KnowledgeBase RAG
Current implementation uses direct Gemini API calls + custom pgvector search. Laravel AI SDK adoption is **deferred** — the current implementation is stable and tested. If adopted later, it would be a V1.1 enhancement, not blocking V1.

### Face Recognition
128D embedding stored in pgvector. Browser-side face-api.js is frontend responsibility. Backend provides register + verify endpoints. PIN fallback when face fails or not registered.

### GPS Geofencing
Haversine distance calculation server-side. Block if `is_mocked` or `accuracy > 100m`. WFA bypasses GPS (requires reason ≥20 chars + approval).

## Notifications
Uses Laravel's `Notification` system with database channel. 8 notification classes exist for email/database delivery. No real mail driver configured yet (uses `log` in dev/production template). WebSocket/broadcast not implemented.

## API Contract
Full API contract at `docs/api/api-contracts.md` (v3.0).
OpenAPI spec at `docs/api/api.json` (275KB, 51 routes).

## Test Coverage
695 tests, 4042 assertions across 68 files. All pass on SQLite and PostgreSQL.
