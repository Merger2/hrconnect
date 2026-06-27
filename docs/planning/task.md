# Task Tracker — HRConnect Skripsi: Face Recognition + GPS Geofencing + RAG Knowledge Base

> Updated: 2026-06-28 — Sesi A ✅ + Sesi B ✅. **Sesi C (RAG) 🚧 in progress** — plan dari ship-ai-with-laravel (SSE streaming, Alpine.js, Livewire minimal). 1,121 tests pass.

> **SESI A ✅ (2026-06-28):** 14/14 items completed — EV-1..7 (email verification flow), PERM-1/2/3 (permission fixes), SEC-1/2/3/4 (Sanctum expiry, middleware, 2FA validation, password rule), P0-1..4, P1-5/6/7 (business logic fixes). **EV-2 (Gmail SMTP) deferred — `MAIL_MAILER=log` active, backend ready.**

> **SESI B ✅ (2026-06-28):** 13/13 items completed + P2-2/3/4 extras — C-1..4 camera bugs, FE-1c face enrollment 6-foto sequential, FE-1d liveness micro-movement, FE-1e TinyFaceDetector default, FE-1f EAR blink, FE-1g CDN cleanup, FE-1h face crop, SEC-GPS-1/2/3 GPS 3-layer, P2-2 AttendanceRiskScorer (14 faktor), P2-3 DynamicBarcodeTokenService (HMAC-SHA256 anti-replay), P2-4 FaceDescriptor table (pgvector). **SEC-GPS-3 GeoIP deferred — framework ready, needs `torann/geoip` package.**

> **SESI C PLAN (2026-06-28):** RAG Knowledge Base UI — 3 halaman (Chat AI, Upload PDF, Manage). Pola dari `/home/merger/RAG-repo/ship-ai-with-laravel`: **Livewire minimal + Alpine.js SSE streaming via `fetch()` + `ReadableStream.getReader()`**. DS-1 tokens only (no brand colors). Backend AI sudah 100% ready (Gemini, pgvector, pg_trgm fallback, HrKnowledgeBaseAgent). Estimasi 6-8 jam.

> **DESIGN DECISION (2026-06-28):** UI follow DESIGN.md DS-1 (canvas #ffffff, body #3a3a3a, Inter font, neutral palette) — NOT copy PasPapan CSS. Only adopt UX/component patterns (flow, layout, interaction). PasPapan CSS (green/cream) is their IP.

> **EXECUTION STATUS (2026-06-28):** Sesi A+B ✅ complete. Sesi C (RAG), D (ESS), E (Approvals), F (Cleanup) remaining. ±100 karyawan, 1,121 tests pass, 51 API endpoints, 47 Blade views (36%). Target 6 sesi (A–F) ~39-55 jam — **2/6 done**.

> **SECURITY POSTURE (2026-06-28):** Full audit keamanan selesai. Ditemukan **4 critical** (Sanctum token never-expire, fake GPS 100% client-trusted, no liveness detection, no security headers middleware), **8 warning** (MustVerifyEmail, API gate, 2FA enforcement, dll), **8 sudah secure** (CipherSweet, PII masking, Argon2id, rate limiting, IDOR, session encrypted, host protection, FormRequest). Lihat §SECURITY untuk detail. **Post-Sesi A+B: 1 critical fixed (Sanctum expiry ✅, fake GPS multi-layer ✅, liveness ✅), 1 deferred (headers → Sesi F).**

## Status Legend

| Status | Meaning |
|--------|---------|
| ✅ | Done |
| 🚧 | In progress |
| ⏳ | Not started |
| 🚫 | Deferred/cancelled |

## Status Snapshot — Overall Project: **~85%** (±100 karyawan)

| Area | % | Status | Notes |
|------|:-:|:------:|-------|
| Backend (app/) | 95% | ✅ | 31 models, 34 enums, 15 services, 14 controllers. Kurang strict_types, base exception, queue consistency. |
| Database (migrations) | 90% | ✅ | 46 migrations, 48 tables. 5 models without factories (deferred V2). |
| API (routes) | 95% | ✅ | 51 endpoints, Sanctum auth, rate limits, permission guards. |
| Security | 85% | ✅ | CipherSweet ✅, PII masking ✅, Argon2id ✅, rate limiting ✅, IDOR ✅, session encrypted ✅, host protection ✅, FormRequest ✅. **Sanctum expiry ✅** (SEC-1). **Email verification ✅** (EV-1/3/4). **Force password change ✅** (EV-5). **Fake GPS multi-layer ✅** (SEC-GPS-1/2/3). **Liveness ✅** (FE-1d/f). **2FA enforcement ❌** (deferred). **Security headers ❌** (SEC-5 → Sesi F). |
| Tests | 90% | ✅ | 1,121 tests / 3,729 assertions (SQLite) + ~28 PG. All services/controllers/policies covered. |
| Flux → MD3 Migration | 100% | ✅ | **SELESAI** — Flux dihapus dari composer, views, CI, docs. 0 Flux references remain. |
| **Design System DS-1** | **40%** | 🚧 | app.css masih pakai cream palette (#fffaf0), harusnya #ffffff. 11 item perlu sync dengan DESIGN.md. |
| **Frontend Views (47 Blade)** | **36%** | 🚧 | Layouts ✅, Auth ✅, Settings ✅. Knowledge-base index + manage akan ditambah (→ 49 views). |
| **ESS Features** | **45%** | 🚧 | Clock-in page **fixed** (bugs C-1..4 ✅). **Face enrollment ✅** (FE-1c ✅). GPS ✅. **EAR blink ✅**. Belum Livewire interaktif sepenuhnya. |
| **Architecture Cleanup** | **40%** | 🚧 | strict_types, base exception, queue pattern, GeofenceMiddleware duplikasi. |
| **PWA readiness** | **40%** | 🚧 | SW ✅, manifest ✅, icons ✅. Tapi SW cache error, offline page ❌. |
| **PHPStan baseline** | 0% | 🚧 | STALE — 3 deleted notification files referenced. |

## 🔍 AUDIT FINDINGS — Full Codebase + Docs Review (2026-06-24)

### Konteks
- **±100 karyawan** — arsitektur sederhana cukup, tidak perlu Opsi B (refactor)
- **HR buat akun** — bukan self-register, `FORTIFY_REGISTRATION_ENABLED=false` (sudah tepat)
- **Tech:** Laravel 13, Livewire 4, Tailwind v4, MD3, PostgreSQL pgvector, Fortify auth, Sanctum API, face-api.js, Gemini RAG

### Ringkasan Temuan

| Area | Temuan | Sumber |
|------|--------|--------|
| **Email Verification** | `MustVerifyEmail` di-comment di User.php → fitur patah | Kode |
| **Mail Driver** | `MAIL_MAILER=log` → email tidak terkirim | `.env` |
| **API Auth** | Login API tidak cek `email_verified_at` → token tanpa verify | `AuthController.php` |
| **Force Change Password** | Middleware Tier 3 skip saat `password_changed_at = null` → seharusnya force redirect | `CheckPasswordExpired.php` |
| **Face Enrollment** | FE-1c ⏳ — belum ada UI register face | Kode |
| **2FA Enforcement** | AUTH-09: 2FA wajib untuk HR/Finance/SuperAdmin — belum diimplement | Docs (SRS) |
| **Google OAuth** | Config ada, flow belum selesai | Docs (SDD) |
| **Permission Finance** | ❌ `view_attendances` — finance gak bisa lihat absensi | PRD §3 + kode |
| **Permission Employee** | ❌ `view_knowledgebase` — employee gak bisa akses AI chat | PRD §3 + kode |
| **Permission approve_wfa** | Hanya manager, hr-manager bypass via `hasRole()` | Kode |
| **RAG Web UI** | Route ada, view tidak → error 500 | `routes/knowledge-base.php` |
| **Camera Bugs** | 4 bug: model path, try/catch, toast event, silent fail | `clock-in.blade.php` |
| **Design Sync** | 11 gap DS-1: canvas, body, error, font, radius, brand colors | DESIGN.md vs app.css |
| **Dead Code** | 3 Haversine duplikat, middleware 0 caller, anti-fake-GPS ganda | Kode |
| **PHPStan** | Baseline stale — 3 deleted notification files | `phpstan-baseline.neon` |

### Prioritas untuk ±100 Karyawan

```
🔴 PRIORITAS 1 (blocking)
  ├── Email Verification — MustVerifyEmail + Gmail SMTP + API gate + endpoint
  ├── Force Change Password — middleware Tier 3: null → redirect
  ├── Kirim email verifikasi dari EmployeeController@store
  ├── Camera Bugs — C-1, C-2, C-3, C-4
  └── Permission Fix — view_attendances (Finance), view_knowledgebase (Employee)

🟡 PRIORITAS 2 (gap fungsional)
  ├── RAG Web UI — Livewire chat + upload
  ├── Face Enrollment UI — FE-1c
  ├── Enforce 2FA untuk HR/Finance/SuperAdmin (AUTH-09)
  ├── Design Sync DS-1 — app.css
  └── Service Worker — hapus /offline dari PRECACHE

🟢 PRIORITAS 3 (housekeeping)
  ├── Google OAuth completion
  ├── Dead Code Cleanup
  ├── Factories × 5
  └── PHPStan baseline
```

### Tidak Perlu Dilakukan (Skip untuk Skala 100)
- `hasRole()` → `can()` di policies (hanya 5 role, tidak akan nambah)
- `$this->authorize()` di ApprovalController (approver_id sudah cukup)
- Team-scoped query Employee API (manager lihat 100 orang bukan masalah)
- Rename `L2_MANAGER` (label internal, tidak pengaruh fungsi)
- Pindah `assignRole` ke observer (berfungsi di controller)
- Standardisasi dual permission check (overhead tidak sebanding)

---

## 📄 PAGE INVENTORY — 130 Pages + 4 Modals (46 ✅ / 84 ❌)

Berdasarkan audit docs (PRD, SRS, SDD, wireframes) + file system `resources/views/`.

### Ringkasan

| Kategori | ✅ Existing | ❌ Missing | Total |
|----------|:----------:|:----------:|:-----:|
| **Halaman Fungsional** | 25 | 68 | **93** |
| **Layouts** | 7 | 4 | **11** |
| **Partials** | 2 | 7 | **9** |
| **Shared Components** | 7 | 9 | **16** |
| **Modals** | 3 | 1 | **4** |
| **Other (welcome, vendor)** | 2 | 0 | **2** |
| **TOTAL** | **46** | **89** | **135** |

### Per Module — Halaman Fungsional (93)

#### 🔐 AUTH (7/7 ✅ — selesai semua)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Login | `pages/auth/login.blade.php` | All | ✅ |
| 2 | Register | `pages/auth/register.blade.php` | Super Admin | ✅ |
| 3 | Forgot Password | `pages/auth/forgot-password.blade.php` | All | ✅ |
| 4 | Reset Password | `pages/auth/reset-password.blade.php` | All | ✅ |
| 5 | Verify Email | `pages/auth/verify-email.blade.php` | All | ✅ |
| 6 | Two-Factor Challenge | `pages/auth/two-factor-challenge.blade.php` | All (2FA) | ✅ |
| 7 | Confirm Password | `pages/auth/confirm-password.blade.php` | All | ✅ |

#### 📊 DASHBOARD (1/5 ✅ — 4 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | ESS Dashboard | `dashboard.blade.php` | Employee, Manager | ✅ |
| 2 | Manager Dashboard | `manager/dashboard.blade.php` | Manager | ❌ |
| 3 | HR Dashboard | `hrd/dashboard.blade.php` | HR Manager | ❌ |
| 4 | Finance Dashboard | `finance/dashboard.blade.php` | Finance | ❌ |
| 5 | Admin Dashboard | `admin/dashboard.blade.php` | Super Admin | ❌ |

#### 📍 ATTENDANCE / PRESENSI (2/6 ✅ — 4 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index / Riwayat | `attendance/index.blade.php` | All | ✅ |
| 2 | Clock-In | `attendance/clock-in.blade.php` | Employee | ✅ (bugs) |
| 3 | Clock-Out | `attendance/clock-out.blade.php` | Employee | ❌ |
| 4 | Calendar / History | `attendance/history.blade.php` | Employee, Manager | ❌ |
| 5 | Summary | `attendance/summary.blade.php` | Employee | ❌ |
| 6 | HR Attendance Mgmt | `hrd/attendance/today.blade.php` | HR Manager | ❌ |

#### 🌴 LEAVE / CUTI (2/8 ✅ — 6 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index | `leaves/index.blade.php` | All | ✅ |
| 2 | Apply | `leaves/apply.blade.php` | Employee | ✅ |
| 3 | History | `employee/leave/history.blade.php` | Employee, Manager | ❌ |
| 4 | Quota / Balance | `employee/leave/quota.blade.php` | Employee | ❌ |
| 5 | Calendar (HR) | `hrd/leaves/calendar.blade.php` | HR Manager | ❌ |
| 6 | Pending (HR) | `hrd/leaves/pending.blade.php` | HR Manager | ❌ |
| 7 | Quota Mgmt | `hrd/leaves/quota-management.blade.php` | HR Manager | ❌ |
| 8 | Types Mgmt | `hrd/leaves/types.blade.php` | HR Manager | ❌ |

#### ⏰ OVERTIME / LEMBUR (2/4 ✅ — 2 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index | `overtimes/index.blade.php` | All | ✅ |
| 2 | Apply | `overtimes/apply.blade.php` | Employee | ✅ |
| 3 | History | `employee/overtime/history.blade.php` | Employee, Manager | ❌ |
| 4 | Pending (HR) | `hrd/approvals/pending.blade.php` | Manager, HR | ❌ |

#### 💰 REIMBURSEMENT (2/4 ✅ — 2 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index | `reimbursements/index.blade.php` | All | ✅ |
| 2 | Apply | `reimbursements/apply.blade.php` | Employee | ✅ |
| 3 | Pending (Finance) | `finance/reimbursements/pending.blade.php` | Finance | ❌ |
| 4 | Report | `finance/reimbursements/report.blade.php` | Finance | ❌ |

#### 💵 PAYROLL (1/6 ✅ — 5 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Payslip | `payroll/payslip.blade.php` | Employee, Finance | ✅ |
| 2 | Index | `payroll/index.blade.php` | Finance, Super Admin | ❌ ⚠️ 500 |
| 3 | Generate | `finance/payroll/generate.blade.php` | Finance | ❌ |
| 4 | Detail | `finance/payroll/detail.blade.php` | Finance | ❌ |
| 5 | Publish | `finance/payroll/publish.blade.php` | Finance | ❌ |
| 6 | Reports (payroll, tax) | `finance/reports/payroll.blade.php` | Finance | ❌ |

#### 🏦 LOAN / KASBON — V2 (0/5 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index | `loans/index.blade.php` | All | ❌ ⚠️ 500 |
| 2 | Apply | `employee/loan/apply.blade.php` | Employee | ❌ |
| 3 | Pending (Finance) | `finance/loans/pending.blade.php` | Finance | ❌ |
| 4 | Installments | `finance/loans/installments.blade.php` | Finance | ❌ |
| 5 | Report | `finance/loans/report.blade.php` | Finance | ❌ |

#### 📦 ASSET — V2 (0/2 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index | `assets/index.blade.php` | All | ❌ ⚠️ 500 |
| 2 | Management | `hrd/assets/management.blade.php` | HR Manager | ❌ |

#### 🤖 KNOWLEDGE BASE / RAG (0/3 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index / Chat UI | `knowledge-base/index.blade.php` | All (Employee + HR) | 🚧 (RAG-1) |
| 2 | Upload | `knowledge-base/manage.blade.php` (merged with manage) | HR Manager | 🚧 (RAG-2) |
| 3 | Manage Articles | `knowledge-base/manage.blade.php` | HR Manager | 🚧 (RAG-2) |

#### ✅ APPROVAL WORKFLOW (0/4 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Index | `approvals/index.blade.php` | Manager, HR, Finance | ❌ ⚠️ 500 |
| 2 | Pending (L1) | `approvals/pending.blade.php` | Manager | ❌ |
| 3 | Pending (L2) | `approvals/l2-pending.blade.php` | HR, Finance | ❌ |
| 4 | All / History | `approvals/all.blade.php` | Manager, HR | ❌ |

#### 👥 EMPLOYEE MANAGEMENT — HR Only (0/8 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Employee List | `hrd/employees/index.blade.php` | HR Manager | ❌ |
| 2 | Create | `hrd/employees/create.blade.php` | HR Manager | ❌ |
| 3 | Edit | `hrd/employees/edit.blade.php` | HR Manager | ❌ |
| 4 | Detail | `hrd/employees/show.blade.php` | HR Manager | ❌ |
| 5 | Bulk Upload | `hrd/employees/bulk-upload.blade.php` | HR Manager | ❌ |
| 6 | Terminations Pending | `hrd/terminations/pending.blade.php` | HR Manager | ❌ |
| 7 | Termination Handover | `hrd/terminations/handover.blade.php` | HR Manager | ❌ |
| 8 | Reassignment | `hrd/terminations/reassignment.blade.php` | HR Manager | ❌ |

#### 🕐 SHIFT MANAGEMENT (0/2 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Shift List | `hrd/shifts/index.blade.php` | Super Admin | ❌ |
| 2 | Schedule | `hrd/shifts/schedule.blade.php` | HR Manager | ❌ |

#### 📈 REPORTS (0/5 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Attendance Report | `hrd/reports/attendance.blade.php` | HR Manager | ❌ |
| 2 | Leave Report | `hrd/reports/leave.blade.php` | HR Manager | ❌ |
| 3 | Employee Report | `hrd/reports/employee.blade.php` | HR Manager | ❌ |
| 4 | Payroll Report | `finance/reports/payroll.blade.php` | Finance | ❌ |
| 5 | Tax Report | `finance/reports/tax.blade.php` | Finance | ❌ |

#### ⚙️ SETTINGS (8/14 ✅ — 6 missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Settings Layout | `pages/settings/layout.blade.php` | All | ✅ |
| 2 | Profile | `pages/settings/profile.blade.php` | All | ✅ |
| 3 | Appearance | `pages/settings/appearance.blade.php` | All | ✅ |
| 4 | Security | `pages/settings/security.blade.php` | All | ✅ |
| 5 | Company | `admin/settings/company.blade.php` | Super Admin | ❌ |
| 6 | Attendance Settings | `admin/settings/attendance.blade.php` | Super Admin | ❌ |
| 7 | Leave Settings | `admin/settings/leave.blade.php` | Super Admin | ❌ |
| 8 | Branding | `admin/settings/branding.blade.php` | Super Admin | ❌ |
| 9 | Security (Admin) | `admin/settings/security.blade.php` | Super Admin | ❌ |
| 10 | System | `admin/settings/system.blade.php` | Super Admin | ❌ |

#### 👤 USER MANAGEMENT — Super Admin Only (0/3 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | User List | `admin/users/index.blade.php` | Super Admin | ❌ |
| 2 | Create | `admin/users/create.blade.php` | Super Admin | ❌ |
| 3 | Edit | `admin/users/edit.blade.php` | Super Admin | ❌ |

#### 📋 ACTIVITY LOG (0/1 ❌)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Activity Log | `admin/activity-log/index.blade.php` | Super Admin, HR | ❌ |

#### 👤 PROFILE (ESS) (0/4 ❌ — semua missing)

| # | Page | Route | Roles | Status |
|---|------|-------|-------|:------:|
| 1 | Personal Info | `employee/profile/personal-info.blade.php` | Employee | ❌ |
| 2 | Family Details | `employee/profile/family-details.blade.php` | Employee | ❌ |
| 3 | **Face Registration** | `employee/profile/face-registration.blade.php` | Employee | ✅ ⭐ |
| 4 | Devices | `employee/profile/devices.blade.php` | Employee | ❌ |

### Modals (4)

| # | Modal | Module | Status |
|---|-------|--------|:------:|
| 1 | Setup 2FA (QR + confirm) | `pages/settings/two-factor-setup-modal.blade.php` | ✅ |
| 2 | Konfirmasi Hapus Akun | `pages/settings/delete-user-modal.blade.php` | ✅ |
| 3 | Recovery Codes (tampil sekali) | `pages/settings/two-factor/recovery-codes.blade.php` | ✅ |
| 4 | Konfirmasi Umum (shared) | `components/confirmation-modal.blade.php` | ❌ |

### ⚠️ Route 500 Errors (5) — view tidak ada

| Route | View Hilang | Module |
|-------|-------------|--------|
| `payroll.index` | `payroll/index.blade.php` | ❌ |
| `approvals.index` | `approvals/index.blade.php` | ❌ |
| `knowledge-base.index` | `knowledge-base/index.blade.php` | 🚧 (Sesi C) |
| `loans.index` | `loans/index.blade.php` | ❌ |
| `assets.index` | `assets/index.blade.php` | ❌ |

---

## Completed Backend Summary

Semua task berikut ✅ **selesai dan diverifikasi** (tidak perlu diulang):

- **P0** (Scope freeze, endpoint matrix, service matrix, product decisions, gap audit) — ✅
- **P1** (API audit: Auth, Employee, Attendance, Leave, Overtime, Reimbursement, Approval, Payroll, KnowledgeBase, Face, Profile) — ✅
- **P1** (RAG refactor: Laravel AI SDK, HrKnowledgeBaseAgent, pg_trgm fallback, structured output) — ✅
- **P1** (Test coverage: 75 test files, T-1 s/d T-31, policies, factories, commands, middleware, PG integration) — ✅
- **P2** (Operations: queue, scheduler, cache, storage, backup, deployment) — ✅
- **P2** (API contract: response envelope, error codes, pagination, enum contract, Scramble docs) — ✅
- **S-1 s/d S-8** (Security: authorization, IDOR, PII, rate-limit, secret, file upload, production checklist) — ✅
- **O-1 s/d O-7** (Operations readiness) — ✅
- **Flux UI removal** (composer, app.css, DESIGN.md, Alpine.js, stubs, skills, CI, AGENTS.md) — ✅

---

## PHASE 1: Flux Removal + MD3 Foundation ✅ SELESAI

| ID | Task | Detail | Status |
|----|------|--------|:------:|
| **M1-1** | **Hapus Flux dari composer** | `composer remove livewire/flux livewire/flux-pro` | ✅ |
| **M1-2** | **Update app.css** | MD3 palette + Rubik + Material Symbols | ✅ |
| **M1-3** | **Update DESIGN.md** | DS-1 App Theme section added | ✅ |
| **M1-4** | **Update PRD.md** | — | 🚫 (tidak ada PRD.md) |
| **M1-5** | **Install Alpine.js via npm** | Alpine 3.15.12 di package.json ✅ | ✅ |
| **M1-6** | **Hapus published Flux stubs** | `resources/views/flux/` — sudah tidak ada | ✅ |
| **M1-7** | **Hapus skill files** | `.agents/skills/fluxui-development/` — sudah tidak ada | ✅ |
| **M1-8** | **Update CI config** | `.github/workflows/*.yml` — tidak ada referensi Flux | ✅ |
| **M1-9** | **Update AGENTS.md / README** | AGENTS.md sudah diperbarui (No Flux, MD3) | ✅ |

### M1 Component Migration — ✅ SELESAI

Semua komponen Flux sudah diganti dengan MD3 custom:

| Komponen Flux | Pengganti | Status |
|--------------|-----------|:------:|
| `flux:heading` | `<h1 class="text-2xl font-semibold text-ink">` | ✅ |
| `flux:card` | `<div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">` | ✅ |
| `flux:button` | `<button class="rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white">` | ✅ |
| `flux:icon.*` | `<span class="material-symbols-outlined">icon_name</span>` | ✅ |
| `flux:sidebar` | Custom flex sidebar + nav items | ✅ |
| `flux:header` | Custom top bar | ✅ |
| `flux:dropdown` | Alpine `x-data="{ open: false }"` | ✅ |
| `flux:toast` | Alpine toast + Livewire event | ✅ (ada bug — lihat C-3) |
| `flux:modal` | Alpine `x-show` + backdrop | ✅ |
| `flux:input` | `<input class="rounded-xl border border-outline-variant bg-canvas ...">` | ✅ |
| `flux:table` | `<table>` + `<thead>` + `<tbody>` | ✅ |
| `flux:separator` | `<hr class="border-outline-variant/50">` | ✅ |
| `flux:link` | `<a class="text-ink underline">` | ✅ |
| `flux:label` | `<label class="text-sm font-medium">` | ✅ |
| `flux:checkbox` | `<input type="checkbox">` | ✅ |
| `flux:radio` | `<input type="radio">` | ✅ |
| `flux:otp` | 6 custom `<input>` + Alpine | ✅ |
| `flux:badge` | `<span class="rounded-full bg-...">` | ✅ |
| `@fluxScripts` | `@vite(['resources/js/app.js'])` | ✅ |
| `@fluxAppearance` | Custom dark mode JS | ✅ |

---

## PHASE 2: Frontend ESS — Livewire + MD3

Semua komponen menggunakan: **MD3 palette** (`bg-surface-container-low`, `text-on-surface`, `rounded-2xl`), **Material Symbols**, **Rubik/Inter**, **custom Tailwind** (NO Flux).

### Mobile Layout (Bottom Nav + TopAppBar)

| ID | Task | Detail | Status |
|----|------|--------|:------:|
| L-1 | **Mobile layout: bottom nav** | 4 tab (Dasbor, Absen, Inbox, Profil) — ✅ Material Symbols, active state | ✅ |
| L-2 | **Mobile layout: TopAppBar** | Avatar + judul halaman + notif icon. `sticky top-0`. Hidden on desktop. | ⏳ |
| L-3 | **Desktop layout: sidebar** | Custom flex sidebar ✅ | ✅ |
| L-4 | **Toast notification service** | ✅ Tapi ada bug — lihat item C-3 | 🚧 |
| L-5 | **Responsive shell** | Mobile: bottom nav + header. Desktop: sidebar. | 🚧 |
| L-6 | **PWA offline.html** | Halaman offline sederhana | ⏳ |
| L-7 | **PWA theme_color update** | Manifest value | ⏳ |

### ESS Livewire Components

| ID | Task | Sub-tasks | Status |
|----|------|-----------|:------:|
| **FE-1** | **Clock In/Out** ⭐ | — | ✅ |
| | 1a. Install face-api.js + model weights | ✅ model weights di `public/models/av1/` | ✅ |
| | 1b. Camera + face detection | dynamic import face-api.js, TinyFaceDetector default, SSD fallback | ✅ |
| | 1c. Face enrollment | 6 foto sequential + progress dots + countdown baru | ✅ |
| | 1d. GPS locator + liveness | 3 sampel GPS (variance+speed) + micro-movement variance | ✅ |
| | 1e. Dynamic QR (bonus) | HMAC-SHA256 DynamicBarcodeTokenService | ✅ |
| | 1f. Camera card Blade | Bugs C-1..4 fixed | ✅ |
| | 1g. EAR blink detection | `computeEAR()` via landmarks, blink pattern confirmed | ✅ |
| | 1h. Face crop | `extractFaces()` → `canvas.toBlob` → audit trail | ✅ |
| | 1i. API integration | ✅ fetch to `/api/v1/attendance/clock-in` | ✅ |
| | 1j. Tests | + test fixes for FaceRecognitionService, AttendanceProofTest | ✅ |
| **FE-2** | **Attendance History** | Table + filters (month, status) | ⏳ |
| **FE-3** | **Leave (Apply + History + Quota)** | Form, quota cards, history table | ⏳ |
| **FE-4** | **Overtime (Apply + History)** | Form, history table | ⏳ |
| **FE-5** | **Reimbursement (Request + History)** | Form, history table | ⏳ |
| **FE-6** | **Payroll Slip** | Period list, PIN, earnings breakdown | ⏳ |
| **FE-7** | **Profile & Devices** | Personal info, device list | ⏳ |
| **FE-8** | **Missing Route Views** | — | ⏳ |
| | 8a. Payroll landing page | | ⏳ |
| | 8b. Approvals landing page | | ⏳ |
| | 8c. Knowledge Base landing page | **PRIORITAS** — route ada tapi view tidak | ⏳ |
| | 8d. Assets landing page | | ⏳ |
| | 8e. Loans landing page | | ⏳ |

### Inbox + RAG (4th Bottom Tab)

| ID | Task | Detail | Status |
|----|------|--------|:------:|
| IN-1 | **Inbox page** | Notifications + Pending Approvals | ⏳ |
| IN-2 | **RAG chat** | AI SDK integration | ⏳ |
| IN-3 | **Knowledge Base list** | Documents list + upload (HR only) | ⏳ |

---

## 🚨 PHASE 3: Critical Bugs (9 item) — PRIORITAS TERTINGGI

| # | ID | Issue | File | Status |
|:-:|:--:|-------|------|:------:|
| 1 | C-1 | **Model path salah** — `loadFromUri('/models')` harus `/models/av1` | `clock-in.blade.php:23-25` | ✅ |
| 2 | C-2 | **Pisah try/catch camera** — `getUserMedia` vs `play()` harus terpisah biar error handling jelas | `clock-in.blade.php:30-38` | ✅ |
| 3 | C-3 | **Toast event mismatch** — `window.dispatchEvent(CustomEvent)` vs handler `Livewire.on()` — sistem event beda | `clock-in.blade.php:76-82` + `app.js` | ✅ |
| 4 | C-4 | **`video.play()` silent fail** — `catch {}` swallow error, status misleading | `clock-in.blade.php:35` | ✅ |
| 5 | C-5 | **SW cache error** — `/offline` tidak ada route, precache gagal | `public/service-worker.js:4` | ⏳ |
| 6 | C-6 | **Meta deprecated** — `apple-mobile-web-app-capable` → `mobile-web-app-capable` | `partials/head.blade.php` | ⏳ |
| 7 | C-7 | **Hardcoded color** — `hover:bg-[#1f1f1f]` harus ganti variable | `clock-in.blade.php:169` | ⏳ |
| 8 | C-8 | **Knowledge Base view hilang** — route ada, view tidak → error 500 | `resources/views/knowledge-base/` | ⏳ |
| 9 | C-9 | **Duplicate SW registration** — inline script di `split.blade.php` + `simple.blade.php` duplikat dari `pwa-install.js` | 2 auth layouts | ⏳ |

---

## 🎨 PHASE 4: Design System Sync — DS-1 Compliance (17 item)

DESIGN.md sudah mendefinisikan **App Theme DS-1** dengan palet netral untuk HR pages. `app.css` masih pakai cream-warm palette yang seharusnya hanya untuk landing page.

### Colors (15 token fixes)

| # | Token | Current (`app.css`) | DS-1 Target (DESIGN.md) | Status |
|:-:|-------|:-------------------:|:------------------------:|:------:|
| D-1 | `--color-canvas` | `#fffaf0` | `#ffffff` | ⏳ |
| D-2 | `--color-on-background` | `#1c1b1b` | `#3a3a3a` (body) | ⏳ |
| D-3 | `--color-on-surface-variant` | `#444748` | `#535353` (muted) | ⏳ |
| D-4 | `--color-outline` | `#747878` | `#6a6a6a` (muted) | ⏳ |
| D-5 | `--color-outline-variant` | `#c4c7c7` | `#cfcfcf` (hairline) | ⏳ |
| D-6 | `--color-error` | `#ba1a1a` | `#ef4444` | ⏳ |
| D-7 | `--color-body` (legacy) | `#1c1b1b` | `#3a3a3a` | ⏳ |
| D-8 | `--color-body-strong` (legacy) | `#0a0a0a` | `#1a1a1a` | ⏳ |
| D-9 | `--color-muted` (legacy) | `#444748` | `#535353` | ⏳ |
| D-10 | `--color-muted-soft` (legacy) | `#747878` | `#888888` | ⏳ |
| D-11 | `--color-hairline` (legacy) | `#c4c7c7` | `#cfcfcf` | ⏳ |
| D-12 | `--color-hairline-soft` (legacy) | `#e5e2e1` | `#f0f0f0` | ⏳ |
| D-13 | `--color-surface-soft` (legacy) | `#f7f3f2` | `#f7f7f7` | ⏳ |
| D-14 | `--color-surface-card` (legacy) | `#f1edec` | `#efefef` | ⏳ |
| D-15 | `--color-surface-strong` (legacy) | `#ebe7e6` | `#d0d0d0` | ⏳ |

### Typography & Radius

| # | Token | Current | Target | Status |
|:-:|-------|:-------:|:------:|:------:|
| D-16 | Font display | `Rubik` | `Inter` weight 500 (substitute untuk Plain Black) | ⏳ |
| D-17 | `--color-primary-active` | Tidak ada | `#1f1f1f` | ⏳ |
| D-18 | `--radius-*` tokens | Tidak ada (pakai TW v4 default) | xs=6, sm=8, md=12, lg=16, xl=24 | ⏳ |
| D-19 | Brand colors di HR pages | `bg-brand-lavender` di clock-in | Ganti warna netral | ⏳ |
| D-20 | `<meta name="color-scheme">` | Tidak ada | Tambah di `head.blade.php` | ⏳ |

---

## 📐 PHASE 5: Component & Spacing Alignment (7 item)

| # | Item | Detail | Status |
|:-:|------|--------|:------:|
| S-1 | Input height → 44px | `py-2.5` (36px) → `py-3` + `h-11` | ⏳ |
| S-2 | Card padding → 32px | `p-6` (24px) → `p-8` (32px) sesuai feature-card DESIGN.md | ⏳ |
| S-3 | Button padding konsisten | `py-2.5 px-6` atau `py-4 px-6` → 12px 20px | ⏳ |
| S-4 | Camera `rounded-[2rem]` | 32px → `rounded-3xl` (24px = DESIGN.md xl) | ⏳ |
| S-5 | Feature cards → `rounded-3xl` | 24px sesuai DESIGN.md xl | ⏳ |
| S-6 | Content cards → `rounded-2xl` | 16px sesuai DESIGN.md lg (cek konsistensi) | ⏳ |
| S-7 | Hapus/migrasi legacy tokens | Setelah DS-1 sync, hapus duplikasi | ⏳ |

---

## 🏗️ PHASE 6: Architecture Cleanup (5 item)

| # | Item | Detail | Status |
|:-:|------|--------|:------:|
| A-1 | Custom base exception class | `App\Exceptions\BaseException` — 1 extends HttpException, 7 extend Exception | ⏳ |
| A-2 | GeofenceMiddleware duplikasi | Haversine formula di middleware duplikat dari `GeofenceService` | ⏳ |
| A-3 | Queue assignment konsistensi | 3 pola: `#[Queue]`, `$queue` property, `onQueue()` — pilih 1 | ⏳ |
| A-4 | `declare(strict_types=1)` | Hanya Services yang punya. Models, Controllers, Exceptions tidak | ⏳ |
| A-5 | Form Request naming konsisten | Campur `*FormRequest.php` dan `*Request.php` | ⏳ |

---

## 🧩 PHASE 7: Feature Gaps (9 item)

| # | Item | Detail | Priority | Status |
|:-:|------|--------|:--------:|:------:|
| F-1 | Factory untuk Asset | | 🟡 | ⏳ |
| F-2 | Factory untuk AssetHandover | | 🟡 | ⏳ |
| F-3 | Factory untuk Loan | | 🟡 | ⏳ |
| F-4 | Factory untuk LoanInstallment | | 🟡 | ⏳ |
| F-5 | Factory untuk PerformanceReview | | 🟡 | ⏳ |
| F-6 | Dashboard views | Masih minimal | 🟢 | ⏳ |
| F-7 | Performance Reviews UI | Model + migrasi ada, UI belum | 🟢 | ⏳ |
| F-8 | PWA offline page + route | | 🟢 | ⏳ |
| F-9 | Service worker exclude API routes | Jangan cache `/api/*` | 🟢 | ⏳ |

---

## PHASE 8: Landing Page + Polish

| ID | Task | Detail | Status |
|----|------|--------|:------:|
| LP-1 | **Landing page CTA cleanup** | Hapus "Get Started Free" / "Start Free Trial" | ⏳ |
| LP-2 | **Landing page MD3 redesign** | Menunggu Figma | 🚫 |
| P-1 | **Lint & typecheck final** | `composer lint:check` + `vendor/bin/phpstan analyse` | ⏳ |
| P-2 | **PHPStan baseline regenerate** | Hapus 3 entry deleted notifications | ⏳ |
| P-3 | **Full test suite** | `composer test` — harus green | ⏳ |
| P-4 | **buildContextSection DRY fix** | Extract duplicate method ke trait | ⏳ |

---

## Remaining Multi-Agent Audit Items

### 🔴 HIGH (4)

| # | Issue | File | Status |
|---|-------|------|:------:|
| 1 | **PHPStan BLOCKER** — baseline referensi 3 notifikasi dihapus | `phpstan-baseline.neon:741-781` | 🚧 |
| 2 | **buildContextSection duplikat** — 16 baris identik di 2 file | `GeminiClient.php:106` + `KnowledgeBaseService.php:143` | ⏳ |
| 3 | **5 missing route views** — ViewNotFound jika diakses | payrol, approval, kb, asset, loan | ⏳ (FE-8) |
| 4 | **SW cache error** — `/offline` precache gagal | `service-worker.js` | ⏳ (C-5) |

### 🟠 MEDIUM (10)

| # | Issue | Detail | Status |
|---|-------|--------|:------:|
| 1 | `bank_account_number` tanpa blind index | Deferred V1.1 | 🚫 |
| 2 | Password expiry middleware belum di-wire | `CheckPasswordExpired` belum daftar di `bootstrap/app.php` | ⏳ |
| 3 | `Device.device_type` tanpa enum cast | Bisa diisi string arbitrary | ⏳ |
| 4 | Observer tanpa `withoutEvents()` | Performance batch operations | ⏳ |
| 5 | Exception non-custom | Beberapa throw `\Exception` raw | ⏳ (A-1) |
| 6 | `(int)` cast di prorata quota | Truncate diam-diam | ⏳ |
| 7 | `AttendanceAlertNotification` campur 3 jenis alert | Missed clock-in, missed clock-out, chronic late jadi 1 | ⏳ |
| 8 | **`config/app.php` timezone hardcoded `'UTC'`** | Harus `env('APP_TIMEZONE', 'UTC')` — `.env` pakai `Asia/Jakarta` | ⏳ |
| 9 | **face-api.js di CDN AND npm** | CDN di `clock-in.blade.php` + bundled di `package.json` — redundan | ⏳ |
| 10 | **Landing page font Outfit vs app Rubik** | Landing pakai Outfit, app pakai Rubik — duaduanya substitute Plain Black, harusnya konsisten Inter | ⏳ |

### 🟡 LOW (11)

| # | Issue | Detail | Status |
|---|-------|--------|:------:|
| 1 | 5 model `HasFactory` tanpa factory | V2 modules, sengaja | 🚫 |
| 2 | 6 redundant indexes | Unique already includes index | ⏳ |
| 3 | `EmployeeSeeder` tidak idempotent | Duplikat jika run ulang | ⏳ |
| 4 | Default password di `.env.example` | Perlu placeholder | ⏳ |
| 5 | `TrustProxies` allow all | Perlu di-tighten per deployment | ⏳ |
| 6 | 16 unused private methods | Code coverage | ⏳ |
| 7 | 13 controller tanpa `declare(strict_types=1)` | Type safety | ⏳ (A-4) |
| 8 | **Exception constructor param inconsistency** | `GeofenceViolationException` pakai untyped params, yang lain typed | ⏳ |
| 9 | **Resource `paginated()` naming inconsistent** | Beberapa Resource define static helper, lainnya tidak | ⏳ |
| 10 | **No DB CHECK constraint `end_date >= start_date`** | Di `leaves` tabel — cuma di-enforce di service layer | ⏳ |
| 11 | **AI agent conversation tests missing** | Tabel `agent_conversations` ada, tapi 0 test untuk AI flow | ⏳ |

---

## Project Stats

| Metric | Value |
|--------|-------|
| App PHP files | 193 |
| App LOC | ~12,070 |
| Blade views | 46 existing / **130 total planned** |
| Route files | 13 (+5 web modules) |
| API endpoints | 42 at `/api/v1` |
| Test files | 75 |
| Tests / assertions | 1,121 / 3,702 (SQLite) + ~28 / ~61 (PG) |
| Halaman fungsional | 25 ✅ / 68 ❌ |
| Layouts / Partials / Components | 16 ✅ / 20 ❌ |
| Modals | 3 ✅ / 1 ❌ |
| **Total views** | **46 ✅ / 89 ❌ = 135 total** |
| Total perbaikan tersisa | **~55 item** |
| Estimasi waktu sisa | **~40-55 jam** |

---

## 🔐 PHASE AUTH: Email Verification + Password Policy (NEW — 2026-06-24)

Skala ±100 karyawan, HR buat akun. Flow:
1. HR buat karyawan → email verifikasi + password sementara
2. Karyawan klik link → email verified
3. Login → middleware force ganti password (password_changed_at = null)
4. Ganti password → password_changed_at = now()
5. Akses dashboard

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **EV-1** | **Uncomment MustVerifyEmail di User model** | Uncomment interface + add trait + implements | 🔴 | ⏳ |
| **EV-2** | **Setup Gmail SMTP** | App Password, MAIL_MAILER=smtp, MAIL_FROM | 🔴 | ⏳ |
| **EV-3** | **API gate verified di login** | AuthController@login: tolak token jika email belum verified | 🔴 | ⏳ |
| **EV-4** | **API endpoint verify + resend** | EmailVerificationController (baru) + 2 route di api.php | 🔴 | ⏳ |
| **EV-5** | **Force change password middleware** | CheckPasswordExpired Tier 3: jangan skip null → redirect security.edit | 🔴 | ⏳ |
| **EV-6** | **EmployeeController@store kirim verifikasi** | Panggil sendEmailVerificationNotification() setelah user dibuat | 🔴 | ⏳ |
| **EV-7** | **Set password_changed_at = null** | Di store, biarkan null untuk trigger middleware | 🔴 | ⏳ |

---

## 🛡️ PHASE PERM: Permission Fix (NEW — 2026-06-24)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **PERM-1** | **view_attendances → Finance** | RoleAndPermissionSeeder: tambah ke financePermissions() | 🔴 | ⏳ |
| **PERM-2** | **view_knowledgebase → Employee** | RoleAndPermissionSeeder: tambah ke employeePermissions() | 🔴 | ⏳ |
| **PERM-3** | **approve_wfa → hr-manager** | Seeder: tambah ke hrManagerPermissions() | 🔴 | ⏳ |

---

## 🤖 PHASE RAG: Knowledge Base UI (Sesi C — IN PROGRESS)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **RAG-1** | **Web UI chat** | Livewire minimal + Alpine.js SSE streaming. Pola dari `ship-ai-with-laravel`: `fetch POST /chat-stream` → `ReadableStream.getReader()` → parse SSE `data:` events → update `messages[]`. Chat minimalis: input, bubble, typing indicator, suggestion buttons. | 🟡 | 🚧 |
| **RAG-2** | **Upload PDF + Manage** | Satu halaman `manage.blade.php`: upload form (title, category, PDF) + document list table (status badge PROCESSING/READY/ERROR, delete). | 🟡 | ⏳ |
| **RAG-3** | **Tests** | SSE endpoint test (`Content-Type: text/event-stream`), auth/permission/throttle, service generator yield. | 🟡 | ⏳ |

---

## 📸 PHASE FACE: Face Enrollment (Selesai Sesi B)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **FE-1c** | **Face enrollment UI** | 6 foto sequential + progress dots + countdown → 6× 128D embeddings → POST /api/v1/face/register. Liveness via micro-movement variance > 0.5. Face crop audit trail. | 🟡 | ✅ |

---

## 🔑 PHASE 2FA: 2FA Enforcement (NEW — 2026-06-24)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **2FA-1** | **Enforce 2FA untuk HR/Finance/SuperAdmin** | Gate di middleware/controller: cek role, redirect ke setup 2FA jika belum (AUTH-09) | 🟡 | ⏳ |

---

## 🧹 PHASE CLEANUP: Dead Code (NEW — 2026-06-24)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **DC-1** | **Hapus GeofenceValidation middleware** | 0 caller, Haversine duplikat dari GeofenceService | 🟢 | ⏳ |
| **DC-2** | **Hapus Branch::validateRadius()** | 0 caller, Haversine ke-3 | 🟢 | ⏳ |
| **DC-3** | **Satukan anti-fake-GPS check** | Hanya di GeofenceService, hapus dari AttendanceService | 🟢 | ⏳ |
| **DC-4** | **PHPStan baseline** | Hapus 3 entry deleted notifications | 🟢 | ⏳ |

## §ANCHORING — Ringkasan Akhir Diskusi (2026-06-24)

### Goal  
Complete **3 pilar skripsi** (Face Recognition, GPS Geofencing, RAG Knowledge Base) + auth/permission fix + ESS pages + approval pages untuk ±100 karyawan.

### Constraints Final
- ±100 karyawan, HR creates accounts — `FORTIFY_REGISTRATION_ENABLED=false` (sudah tepat).
- **3 pilar wajib jadi**: Face Recognition, GPS Geofencing, RAG Knowledge Base — no feature trimmed.
- `view_knowledgebase` granted ke **semua 5 role** (Employee, Manager, Finance, HR, Super-Admin); `manage_knowledgebase` tetap HR-only.
- **PIN 6 digit** digunakan untuk **payslip download** — bukan untuk absensi.
- Face enrollment: **6 frame sequential** via camera → `detectSingleFace().withFaceLandmarks().withFaceDescriptor()` × 6 → 6× 128D embeddings → `POST /api/v1/face/register` dengan `{ embeddings: [[...], ...] }`. Micro-movement antar 6 frame = **natural liveness detection**. Jika tidak ada face data, clock-in ditolak.
- **Tidak perlu Opsi B** (architectural refactor) — skala 100 karyawan tidak memerlukan hasRole→can migration, team-scoped queries, atau policy gate di ApprovalController.
- Design: DESIGN.md DS-1 App Theme (canvas #ffffff, body #3a3a3a, Inter font, neutral palette).
- Camera di clock-in: **jangan pakai `display:none`** di iOS — gunakan `opacity-0 pointer-events-none`.

### Key Decisions Final
| # | Keputusan | Detail |
|:-:|-----------|--------|
| 1 | **6 sesi eksekusi (A–F)** | A (Auth) → B (Face) → C (RAG) → D (ESS pages) → E (Approvals) → F (Cleanup) |
| 2 | **RAG is not minimal** | Full Chat + Upload PDF + Manage articles (3 halaman) required for skripsi completeness |
| 3 | **Seed data tetap ada** | Q&A via seeder alongside real PDF upload |
| 4 | **Gmail SMTP deferred** | `MAIL_MAILER=log` sampai App Password siap; backend code ready, switchable later |
| 5 | **Sesi D hanya ESS yg relevan** | Attendance History, Leave (Apply+History+Quota), Overtime, Reimbursement, Payslip, Profile & Devices — Payroll/Loan/Asset pages wait for backend |
| 6 | **PIN → payslip saja** | Bukan fallback absensi (revisi dari catatan sebelumnya) |
| 7 | **4-layer GPS anti-spoofing** | Client flag + time-series variance + IP cross-check + Haversine anomaly score — layered defense untuk skripsi |
| 8 | **Security items baru (SEC-1..5)** | Token expiry, API password middleware, 2FA format, change password rule, security headers |

### Security Posture — Hasil Audit (2026-06-24)

#### 🔴 Critical (4)
| # | Issue | Dampak | File | Sesi |
|:-:|-------|--------|------|:----:|
| 1 | **Sanctum token never-expire** (`expiration = null`) | Bearer token bocor = akses permanen | `config/sanctum.php:53` | A |
| 2 | **Fake GPS 100% client-trusted** — `is_mocked` dari JavaScript, bisa dipalsukan | Karyawan bisa absen dari mana saja | `app/Services/GeofenceService.php:19` | B |
| 3 | **No liveness detection** — 1 foto statis bisa replay attack | Foto/video bisa lolos verifikasi | `app/Services/FaceRecognitionService.php` | B |
| 4 | **No security headers middleware** — CSP, HSTS, X-Frame-Options tidak ada | Rentan clickjacking, XSS, MIME sniffing | `bootstrap/app.php` middleware config | F |

**Approach Critical:**
- **#1 Sanctum:** Set `expiration => 525600` (1 tahun) + hapus token saat logout. Tidak perlu refresh token — cukup untuk skripsi ±100 karyawan.
- **#2 Fake GPS:** **4-layer defense** (lihat SEC-GPS-1/2/3 + Haversine existing) — jadi nilai tambah skripsi (sub-bab "Multi-layer GPS Anti-Spoofing").
- **#3 Liveness:** **6 frame sequential** + micro-movement variance antar embedding (FE-1d). Variance > 0 = hidup. Blink detection via EAR dari face landmarks sebagai bonus.
- **#4 Security headers:** Middleware Laravel atau Nginx (sudah di docs deployment). Untuk skripsi cukup docs → skip implementasi Laravel (SEC-5 deferred ke F).

#### 🟡 Warning (8)
| # | Issue | Status |
|:-:|-------|:------:|
| 5 | MustVerifyEmail di-comment (`User.php:5`) | ✅ EV-1 (Sesi A) |
| 6 | API `login()` tidak cek `hasVerifiedEmail()` | ✅ EV-3 (Sesi A) |
| 7 | 2FA tidak di-enforce per role (HR/Finance/SuperAdmin) | ✅ 2FA-1 (Sesi F) |
| 8 | `password.expired` middleware tidak dipasang di route API | ✅ SEC-2 (Sesi A) |
| 9 | Cache data tidak dienkripsi (`CACHE_STORE=database`) | ⏳ Deferred — tidak critical untuk skripsi |
| 10 | Face registration self-service tanpa konfirmasi | ✅ SEC-5 (Sesi F — confirmation gate) |
| 11 | TwoFactorChallengeRequest tidak validasi format kode | ✅ SEC-3 (Sesi A) |
| 12 | ChangePasswordRequest override production rule (min:8) | ✅ SEC-4 (Sesi A) |

#### ✅ Already Secure (8)
- **CipherSweet** ✅ — 3 model encrypted + blind index + `encryptedUnique`
- **PII masking** ✅ — EmployeeResource masking, `showPii()` via permission + audit log
- **Argon2id** ✅ — 64MB memory, 4 iterasi, `rehash_on_login`
- **Rate limiting** ✅ — 5/1 login/2FA, 10/1 face, 5/5 clock
- **IDOR protection** ✅ — Policy cek ownership `$user->employee?->id === $employee->id`
- **Session encrypted** ✅ — `SESSION_ENCRYPT=true`, JSON serialization, HttpOnly, SameSite=Lax
- **Host protection** ✅ — `trustHosts()` aktif
- **FormRequest** ✅ — Semua 28 endpoint API pakai FormRequest dengan validasi ketat

---

## §REALITY CHECK — Verifikasi Menyeluruh (2026-06-24)

Semua klaim di `task.md` diverifikasi langsung ke filesystem + test suite (run actual) + route list + database.

### ✅ Akurat (16/16)

| Klaim task.md | Realitas | Metode Verifikasi |
|:--------------|:---------|:-----------------|
| 31 models | 31 file | `ls app/Models/*.php` |
| 15 services | 15 file | `ls app/Services/*.php` |
| 8 policies | 8 file | `ls app/Policies/*.php` |
| 34 enums | 34 file | `ls app/Enums/*.php` |
| 13 API controllers | 13 file | `ls app/Http/Controllers/Api/*.php` |
| 13 route files | 13 file | `ls routes/*.php` |
| 46 Blade views | 46 file | `find resources/views -name '*.blade.php' \| wc -l` |
| **1,121 tests / 3,702 assertions** | **1,121 passed, 2 skipped** | **RUN ACTUAL** `php artisan test --compact` ✅ |
| 75 test files | 75 file | `find tests -name '*.php' \| wc -l` |
| 5 models tanpa factory | Asset, AssetHandover, Loan, LoanInstallment, PerformanceReview | Cross-check factory files |
| 5 route-500 views | Semua MISSING: payroll, approvals, kb, loans, assets | `ls` masing-masing |
| MustVerifyEmail di-comment | `// use` di User.php:5 | Read file langsung |
| Sanctum expiration = null | `config:show sanctum.expiration` = null | Run command |
| Argon2id + rehash | `HASH_DRIVER=argon2id`, `rehash_on_login=true` | `.env` + `config/hashing.php` |
| CheckPasswordExpired wired | Alias di `bootstrap/app.php` | Read file langsung |
| CipherSweet configured | 3 model, blind index, `encryptedUnique` | Read file langsung |

### ❌ Discrepancies Minor

| Klaim task.md | Realitas | Selisih |
|:--------------|:---------|:--------|
| 47 migrations | 46 | -1 (hitungan salah — mungkin 1 migration manual) |
| 42 API endpoints | 51 | +9 (task.md outdated — kode sudah bertambah) |

### 📊 Real Progress per Area

```
Backend code          ████████████░░░░  95%  — 31 models, 15 services, 13 controllers, tested ✅
API routes            ████████████████ 100%  — 51 endpoints live, rate limited, permissioned ✅
Tests                 ████████████░░░░  95%  — 1,121 pass, 3,702 assertions ✅
Security foundation   ██████████░░░░░░  80%  — CipherSweet, Argon2id, PII, rate limit ✅
                      ██░░░░░░░░░░░░░░  20%  — Sanctum expiry, fake GPS, liveness, headers ❌

Frontend views        █████░░░░░░░░░░░  35%  — 46/130 views (auth ✅, settings ✅, modules ❌)
Face recognition      ████░░░░░░░░░░░░  30%  — backend ready, camera broken, UI 0%
RAG knowledge base    █████░░░░░░░░░░░  50%  — backend AI agent ready, UI 0%
ESS interactive       ░░░░░░░░░░░░░░░░   0%  — 0 Livewire components
Design sync DS-1      ████░░░░░░░░░░░░  40%  — CSS palette still cream #fffaf0
```

### 📐 face-api.js — Dokumentasi vs Implementasi

Dibandingkan dengan docs resmi: `justadudewhohacks.github.io/face-api.js/docs/`

#### ✅ Sesuai Docs

| Aspek | Docs | Kode |
|:------|:-----|:-----|
| Model loading | `loadFromUri('/models')` | Sama ✅ |
| Detection pipeline | `detectAllFaces().withFaceLandmarks().withFaceDescriptors()` | Sama persis ✅ |
| SSD options | `SsdMobilenetv1Options({ minConfidence })` | Sama ✅ |
| 128D descriptor | `Float32Array` — array length 128 | Sama ✅ |

#### ❌ Tidak Sesuai / Gap

| # | Docs Bilang | Implementasi | Dampak ke Skripsi |
|:-:|:------------|:-------------|:-----------------|
| 1 | **TinyFaceDetector** = "your GO-TO face detector on **mobile devices**" | Hanya SSD (5.4MB), tidak ada Tiny (190KB) | **Boros CPU/memory di HP**, loading lama, risk freeze |
| 2 | `landmarks.getLeftEye()` / `getRightEye()` untuk EAR blink detection | Tidak digunakan | **Liveness 0%** — foto statis bisa lolos |
| 3 | `LabeledFaceDescriptors` support **array descriptors per person** | 1 embedding per orang | Akurasi rendah — akan diperbaiki di FE-1c ✅ |
| 4 | `extractFaces()` untuk crop & simpan face region | Tidak digunakan | Tidak ada audit trail wajah |
| 5 | CDN **atau** npm (salah satu) | **Keduanya** — redundan | Waste bandwidth, bundle ganda |

#### ✅ Recommended Adjustment untuk Sesi B (dari docs)

| Adjustment | Rationale |
|:-----------|:----------|
| **Tambah TinyFaceDetector** (190KB) sebagai default mobile, fallback SSD | Docs merekomendasikan untuk PWA mobile. 190KB vs 5.4MB — signifikan. |
| **EAR blink detection** via `landmarks.getLeftEye()` + `getRightEye()` | Liveness sederhana tanpa backend change. Pola open→closed→open = blink confirmed. |
| **6 frame → `LabeledFaceDescriptors`** array | Sesuai docs tutorial FaceMatcher + LabeledFaceDescriptors. |
| **Hapus CDN face-api.js**, bundle via npm + Vite | Satu sumber, bundle size terkontrol. |

### 🎯 Risk Assessment Realistis

| Sesi | Risk | Jam Estimasi | Notes |
|:----|:----:|:------------:|:------|
| A (Auth + Sec) | 🟢 Rendah | 4-6 jam | Backend changes minor, semua sudah tested |
| B (Face + GPS) | 🟡 Sedang | 10-14 jam | Camera bugs tricky (iOS getUserMedia), GPS layers butuh GeoIP library, +4 item baru dari docs |
| C (RAG) | 🟡 Sedang | 6-8 jam | Livewire chat from scratch, tapi backend AI agent sudah jadi |
| D (ESS) | 🔴 Tinggi | 12-16 jam | 9 Blade views + Livewire interaktif — ini paling banyak kerja |
| E (Approvals) | 🟡 Sedang | 4-6 jam | Dependen ke D (butuh data approval) |
| F (Cleanup) | 🟢 Rendah | 3-5 jam | Mostly config/css, dead code removal |
| **TOTAL** | | **39-55 jam** | Realistis untuk ±100 karyawan |

### 💡 Kesimpulan Akhir

1. **Backend genuinely strong.** 1,121 tests pass (dijalankan langsung). 51 API endpoints. CipherSweet, Argon2id, rate limiting, IDOR — semuanya berfungsi. Bukan klaim kosong.
2. **Frontend genuinely weak.** 46/130 views selesai. 0 Livewire. 550 bytes app.js. Ini yang bikin overall progress terlihat kecil.
3. **Tapi plan sudah tepat.** 6 sesi fokus ke frontend — Sesi B, C, D, E semuanya target Blade views + interaktif.
4. **Sesi B perlu adjustment dari docs face-api.js:** TinyFaceDetector (190KB) untuk mobile, EAR blink detection, 6 foto enrollment pakai `LabeledFaceDescriptors`, hapus CDN redundan.
5. **Estimasi real:** 39-55 jam. Realistis untuk ±100 karyawan.

---

### Status Perubahan Kode yang Perlu Dilakukan

#### ✅ Sesi A — Auth & Permission (14 item) — COMPLETED
| ID | Task | File | Prioritas |
|:--:|------|------|:---------:|
| EV-1 | Uncomment `MustVerifyEmail` interface + tambah trait | `app/Models/User.php:5` | ✅ |
| EV-2 | Setup Gmail SMTP (.env) — **deferred, pakai `log` dulu** | `.env` | 🚫 |
| EV-3 | API gate verified di AuthController@login | `app/Http/Controllers/Api/AuthController.php:35` | ✅ |
| EV-4 | API endpoint verify + resend (mobile) | Controller baru + 2 route di `routes/api.php` | ✅ |
| EV-5 | CheckPasswordExpired Tier 3: null → force redirect | `app/Http/Middleware/CheckPasswordExpired.php:63-68` | ✅ |
| EV-6 | EmployeeController@store — kirim verifikasi email | `app/Http/Controllers/Api/EmployeeController.php:118` | ✅ |
| EV-7 | Set `password_changed_at = null` di store | `app/Http/Controllers/Api/EmployeeController.php:118` | ✅ |
| PERM-1 | `view_attendances` → Finance | `database/seeders/RoleAndPermissionSeeder.php:120` | ✅ |
| PERM-2 | `view_knowledgebase` → Employee | `database/seeders/RoleAndPermissionSeeder.php:170` | ✅ |
| PERM-3 | `approve_wfa` → hr-manager | `database/seeders/RoleAndPermissionSeeder.php:100` | ✅ |
| **SEC-1** | **Set Sanctum token expiry 1 tahun** + hapus token di logout | `config/sanctum.php:53` | ✅ |
| **SEC-2** | **Pasang middleware `password.expired` di route group API** | `routes/api.php` | ✅ |
| **SEC-3** | **Validasi format 2FA code** — 6 digit TOTP / 8 char recovery | `app/Http/Requests/Api/TwoFactorChallengeRequest.php:14-19` | ✅ |
| **SEC-4** | **ChangePasswordRequest** — ganti `Password::min(8)` → pakai default `AppServiceProvider` | `app/Http/Requests/Api/ChangePasswordRequest.php:19` | ✅ |

#### ✅ Sesi B — Face Recognition + GPS Anti-Spoofing + Liveness (13+3 item) — COMPLETED
| ID | Task | File | Prioritas |
|:--:|------|------|:---------:|
| C-1 | Model path: `/models` → `/models/av1` | `resources/views/attendance/clock-in.blade.php` | ✅ |
| C-2 | Pisah try/catch: getUserMedia vs play() | `resources/views/attendance/clock-in.blade.php:30-38` | ✅ |
| C-3 | Toast event: `CustomEvent` → `Livewire.dispatch()` | `resources/views/attendance/clock-in.blade.php:76-82` + `app.js` | ✅ |
| C-4 | `video.play()` — hapus `catch {}` silent | `resources/views/attendance/clock-in.blade.php:35` | ✅ |
| **FE-1c** | **Face enrollment UI — 6 foto sequential** Camera → countdown → `detectSingleFace().withFaceLandmarks().withFaceDescriptor()` × 6 → `POST /api/v1/face/register` | `resources/views/employee/profile/face-registration.blade.php` (baru) | ✅ |
| **FE-1d** | **Liveness via micro-movement** — 6 frame berurutan dengan variance antar embedding. Variance > 0.5 = hidup. | `clock-in.blade.php` + `face-registration.blade.php` | ✅ |
| **FE-1e** | **TinyFaceDetector (190KB)** default mobile, SSD fallback. Weights di `public/models/av1/`. | `public/models/av1/tiny_face_detector_model*` | ✅ |
| **FE-1f** | **EAR blink detection** — `computeEAR()` via `landmarks.getLeftEye()/getRightEye()`. EAR history 10 frame, pola open→closed→open = blink. | `clock-in.blade.php` inline JS | ✅ |
| **FE-1g** | **Hapus CDN face-api.js** — dynamic `import('face-api.js')` via npm+Vite. CDN script tag dihapus. | `clock-in.blade.php` + `face-registration.blade.php` | ✅ |
| **FE-1h** | **Face crop audit trail** — `extractFaces()` → `canvas.toBlob` → dikirim di payload. | `clock-in.blade.php` inline | ✅ |
| **SEC-GPS-1** | **Layer 1: Client-side** — `is_mocked` flag + accuracy threshold 50m. | `app/Services/GeofenceService.php` | ✅ |
| **SEC-GPS-2** | **Layer 2: Time-series** — 3 sampel GPS dalam ~5dtk, variance + speed check via `validateGpsTimeSeries()`. | `app/Services/GeofenceService.php` | ✅ |
| **SEC-GPS-3** | **Layer 3: IP cross-check** — framework `crossCheckIpLocation()` di GeofenceService. GeoIP opsional via `torann/geoip` — deferred. | `app/Services/GeofenceService.php` | ✅ |

#### 🟡 Sesi C — RAG Knowledge Base UI (3 item) — IN PROGRESS
| ID | Task | Detail | Files | Status |
|:--:|------|--------|-------|:------:|
| **RAG-1** | **Chat AI (halaman utama)** | Livewire minimal + Alpine.js SSE streaming. Pola dari `ship-ai-with-laravel`: `fetch POST /chat-stream` → `ReadableStream.getReader()` → parse `data: {"text":"..."}` events. Auto-resize textarea, Enter/Shift+Enter, typing indicator 3 bouncing dots, suggestion buttons (FAQ cuti/BPJS/jam kerja), `formatMessage()` bold/bullet/newline. | **NEW:** `app/Livewire/KnowledgeBaseChat.php`, `resources/views/livewire/knowledge-base-chat.blade.php`, `resources/views/knowledge-base/index.blade.php`, `app/Http/Requests/Api/ChatStreamRequest.php`. **EDIT:** `app/Http/Controllers/Api/KnowledgeBaseController.php` (+chatStream), `app/Services/KnowledgeBaseService.php` (+chatStream generator), `routes/api.php` (+/chat-stream). | 🚧 |
| **RAG-2** | **Upload PDF (HR only)** | Form upload + document list table. `GET /knowledge-base/manage` — middleware `can:manage_knowledgebase`. Title input, category dropdown, PDF file input. Table: title, category, status badge (PROCESSING/READY/ERROR), upload date, delete action. | **NEW:** `resources/views/knowledge-base/manage.blade.php` | ⏳ |
| **RAG-3** | **Tests** | Streaming endpoint assertion (`Content-Type: text/event-stream`), auth/permission/throttle, service layer generator yield. | **EDIT:** `tests/Feature/Api/KnowledgeBaseEndpointTest.php`, `tests/Feature/Api/KnowledgeBaseProofTest.php`, `tests/Feature/Services/KnowledgeBaseServiceTest.php` | ⏳ |

#### 🟡 Sesi D — ESS Pages (7 item)
| ID | Task | View Baru |
|:--:|------|-----------|
| FE-2 | Attendance History | `resources/views/attendance/history.blade.php` |
| FE-3a | Leave Apply | `resources/views/leaves/apply.blade.php` (existing, upgrade) |
| FE-3b | Leave History | `resources/views/employee/leave/history.blade.php` |
| FE-3c | Leave Quota | `resources/views/employee/leave/quota.blade.php` |
| FE-4 | Overtime (Apply + History) | `resources/views/overtimes/apply.blade.php` + history |
| FE-5 | Reimbursement (Request + History) | `resources/views/reimbursements/index.blade.php` (upgrade) |
| FE-6 | Payslip (PIN + download) | `resources/views/payroll/payslip.blade.php` (upgrade) |
| FE-7 | Profile & Devices | `resources/views/employee/profile/*.blade.php` |
| FE-8a | Approvals landing page | `resources/views/approvals/index.blade.php` |
| FE-8b | Knowledge Base landing page | `resources/views/knowledge-base/index.blade.php` (same as RAG-1) |

#### 🟢 Sesi E — Approvals (3 item)
| ID | Task | View Baru |
|:--:|------|-----------|
| AP-1 | Approvals landing + pending L1 | `resources/views/approvals/index.blade.php` + `pending.blade.php` |
| AP-2 | Approvals pending L2 | `resources/views/approvals/l2-pending.blade.php` |
| AP-3 | Approvals history | `resources/views/approvals/all.blade.php` |

#### 🟢 Sesi F — Cleanup + Security Headers (8 item)
| ID | Task | Detail |
|:--:|------|--------|
| DC-1 | Hapus GeofenceValidation middleware | 0 caller, Haversine duplikat |
| DC-2 | Hapus Branch::validateRadius() | 0 caller, Haversine ke-3 |
| DC-3 | Satukan anti-fake-GPS check | Hanya di GeofenceService |
| DC-4 | PHPStan baseline — hapus 3 entry | `phpstan-baseline.neon` |
| C-5 | SW cache — hapus `/offline` dari PRECACHE | `public/service-worker.js` |
| D-1..D-20 | Design Sync DS-1 | app.css palette + font + radius |
| A-1..A-5 | Architecture cleanup (opsional) | strict_types, base exception, queue, etc. |
| **SEC-5** | **Security headers middleware** — CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy + face registration confirmation gate (konfirmasi sebelum overwrite embedding) | Middleware baru + `bootstrap/app.php` |

---

## §REFERENCE REPOS — 5 Cloned Repos Analysis (2026-06-28)

### Source of Truth

| Repo | Path | Primary Role | Priority |
|:-----|:-----|:-------------|:--------:|
| **PasPapan** | `/home/merger/PasPapan/` | Face enrollment, attendance flow, risk scoring, anti-replay QR, approval lock, termination checklist, offline sync | 🔴 Primary |
| **Quanta HRIS** | `/home/merger/quanta-hris-laravel/` | Indonesian payroll (PPh21 TER, BPJS, lembur PP 35/2021, potongan, tunjangan 75% rule) | 🟢 Post-skripsi |
| **Laravel-Smarthr** | `/home/merger/laravel-smarthr/` | UI component reference (141 views) | 🟢 Low |
| **HRMS Livewire** | `/home/merger/hrms-livewire/` | Queue progress bar pattern | 🟢 Low |
| **hris** | `/home/merger/hris/` | Org structure hierarchy (React stack, different tech) | 🟢 Low |

### ship-ai-with-laravel — RAG Chat Reference (Sesi C)

| Pattern | File | What We Port |
|:--------|:-----|:-------------|
| **Livewire chat minimal** | `app/Livewire/SupportChat.php` | Public props `messages`, `input`, `conversationId`, `isStreaming` — semua interaktivitas di Alpine |
| **Alpine SSE streaming** | `resources/views/livewire/support-chat.blade.php` | `fetch POST /chat/stream` → `response.body.getReader()` → `TextDecoder` → parse `data: {"text":"..."}` → update `messages[]` |
| **ChatController SSE** | `app/Http/Controllers/ChatController.php` | `$agent->stream()` wrapped in `StreamedResponse` dengan `Content-Type: text/event-stream` |
| **Typing indicator** | `support-chat.blade.php` | 3 bouncing dots + `pulse-dot` animation |
| **Suggestion buttons** | `support-chat.blade.php` | Predefined FAQ buttons yang set `input` + trigger `sendMessage()` |
| **formatMessage** | `support-chat.blade.php` | `**bold**` → `<strong>`, `- ` → `&bull; `, `\n` → `<br>` |
| **Agent + tools** | `app/Ai/Agents/SupportAgent.php` | Pola `HasTools` + `SimilaritySearch::usingModel()` → HRConnect sudah punya HrKnowledgeBaseAgent setara |

### PasPapan — Primary Reference (251 views, 101 Livewire, 79 models, 22 services)

#### Key Files & Patterns

| Pattern | File | What We Port |
|:--------|:-----|:-------------|
| **Attendance risk scoring** | `app/Support/AttendanceRiskScorer.php` | 14 faktor anti-spoofing (score 0-100) → SEC-GPS-1/2/3 + FE-1d |
| **Anti-replay QR** | `app/Support/DynamicBarcodeTokenService.php` | HMAC-SHA256 + nonce + TTL jitter → clock-in QR |
| **Device attendance lock** | `app/Services/Attendance/DeviceAttendanceService.php` | `lockForUpdate()` + radius check → P1-5 & WFA race fix |
| **Approval lock** | `app/Support/ReimbursementApprovalService.php` | `lock()` + `ensureReviewable()` → P1-5 TOCTOU fix |
| **Lifecycle termination** | `app/Support/EmployeeLifecycleService.php` | Lifecycle-based termination → P0-4 status bypass fix |
| **Offboarding checklist** | `app/Support/HrChecklistService.php` | 4-task checklist + dependency chain + assignee → termination |
| **Face enrollment** | `app/Livewire/User/FaceEnrollment.php` + view | 992 lines canvas overlay guide ellipse + auto-capture → FE-1c |
| **Face verification** | `resources/views/livewire/user/scan.blade.php` | 1,100+ lines face scan + Alpine → clock-in flow |
| **Face model** | `app/Models/FaceDescriptor.php` | Separate table for embeddings → database design |
| **App JS utilities** | `resources/js/app.js` (1,642 lines) | PasPapanAlert wrapping SweetAlert2, Flatpickr UI picker, client-side validation pattern → `resources/js/face-utils.js` |

#### UX Patterns to Adopt (NOT CSS)

| PasPapan Pattern | HRConnect Implementation |
|:-----------------|:------------------------|
| Face enrollment guide overlay (center face → hold still → turn left → center → turn right → center → auto-capture) | Alpine + canvas, colors/radius from DESIGN.md tokens |
| Face verification modal (camera preview + overlay + status indicator + verify button) | DS-1 `bg-canvas #ffffff`, `text-ink #0a0a0a`, `rounded-xl 24px` |
| Layout structure (`user-page-shell` → `user-page-container` → `user-page-header` → `user-page-body`) | Our Blade convention `x-layouts::app.sidebar` |
| Toast/Alert wrapping SweetAlert2 via Livewire | Implement fresh with DS-1 colors |
| Flatpickr MutationObserver pattern | Port logic, styling from DESIGN.md rounded tokens |
| Component BEM naming (`attendance-panel__step`, `face-enrollment-guide__steps`) | Naming pattern only, not class copy |
| Scan page flow (not checked in → QR scanner → selfie capture → processing) | UX flow sama, UI from our tokens |

#### What NOT to Copy from PasPapan

| Element | Reason |
|:--------|:-------|
| `primary-600: #57944a` (green palette) | DS-1 primary = `#0a0a0a` (near-black) |
| `canvas: #fffaf0` (cream) | DS-1 canvas = `#ffffff` |
| `--user-native-border` CSS variables | PasPapan-specific |
| `.quick-wallet-*`, `.user-list-card` classes | PasPapan-specific |
| `guest-ui` / `user-ui` layout classes | PasPapan-specific |
| Bootstrap-like utility classes | PasPapan-specific |

### Quanta HRIS — Indonesian Payroll Reference (Post-Skripsi)

| Service | File | Logic |
|:--------|:-----|:------|
| `Pph21Service` | `app/Services/Pph21Service.php` | TER PMK 168/2023, lookup by bruto bracket |
| `BpjsService` | `app/Services/BpjsService.php` | Kesehatan 1%, JHT 2%, JP 1% + batas atas |
| `LemburService` | `app/Services/LemburService.php` | PP 35/2021, formula (gaji+tunjangan)/173, multiplier hari kerja/libur |
| `PotonganService` | `app/Services/PotonganService.php` | Alfa (per hari), terlambat (3 metode: nominal/persen/jam) |
| `TunjanganService` | `app/Services/TunjanganService.php` | 3 jenis tunjangan + 75% rule compliance |
| `HitungGajiService` | `app/Services/HitungGajiService.php` | Orchestrator semua komponen |
| Migrations + Seeders | `database/migrations/` (3 tabel pajak) | `kategori_ter`, `golongan_ptkp`, `tarif_ter` + seeder |

**Limitations:** hanya 3 tests (ExampleTest), controller-based (no DI), `$casts` array bug (key-value pair tidak lengkap), MySQL/SQLite (no PostgreSQL support).

---

## §BUSINESS LOGIC AUDIT — 11 Vulnerabilities Mapped (2026-06-28)

### P0 Critical (4) ✅ — All Fixed in Sesi A

| ID | Issue | File | Fix Pattern | Status |
|:--:|:------|:-----|:------------|:------:|
| P0-1 | **No authorization gate** — `processContractEnd()` langsung eksekusi tanpa cek siapa yang panggil | `EmployeeTerminationController.php:56`, `EmployeeTerminationService.php:97` | Tambah `Gate::authorize()` atau policy check | ✅ |
| P0-2 | **Null branch.radius** — PHP cast null ke 0, radius 0m = semua lokasi valid | `GeofenceService.php:40` | Guard null + throw `GeofenceException` | ✅ |
| P0-3 | **WFA null employee** — employee bisa null, skip geofence check | `AttendanceController.php:220` | Guard null + return error response | ✅ |
| P0-4 | **Status bypass** — `EmployeeController@update` (line 149–164) allow direct status change to resigned/terminated **without cleanup** | `EmployeeController.php:149-164` | Force via `EmployeeLifecycleService` (PasPapan pattern) | ✅ |

### P1 High (3) ✅ — All Fixed in Sesi A

| ID | Issue | File | Fix Pattern | Status |
|:--:|:------|:-----|:------------|:------:|
| P1-5 | **Reimbursement TOCTOU** — `isApproved()` check sebelum `lockForUpdate()` → race condition | `ReimbursementService.php:93-99` | PasPapan `lock()` + `ensureReviewable()` pattern | ✅ |
| P1-6 | **Payroll isLocked() gap** — hanya cek PUBLISHED, tidak cek PAID | `PayrollCalculatorService.php:431` | Tambah `$payroll->status === PayrollStatus::PAID` check | ✅ |
| P1-7 | **Overtime date validation** — missing `after_or_equal:today` rule | `StoreOvertimeRequest.php:29` | Tambah rule `after_or_equal:today` | ✅ |

### P2 Medium (4) ✅ — All Fixed in Sesi A+B

| ID | Issue | File | Fix Notes | Status |
|:--:|:------|:-----|:----------|:------:|
| P2-1 | **WFA clock race** — concurrent WFA request bisa bypass daily limit | `AttendanceController.php:202-249` | PasPapan `lockForUpdate()` + DB transaction | ✅ |
| P2-2 | **Risk scoring 0** — tidak ada deteksi anomaly untuk GPS/face | `GeofenceService.php`, `FaceRecognitionService.php` | Port PasPapan `AttendanceRiskScorer` (14 faktor) | ✅ |
| P2-3 | **No anti-replay QR** — QR code statis, bisa replay attack | `clock-in.blade.php` QR | Port PasPapan `DynamicBarcodeTokenService` (HMAC-SHA256 + nonce + TTL) | ✅ |
| P2-4 | **Face embedding di Employee table** — embeddding 128D disimpan langsung di kolom employee, bukan tabel terpisah | Employee migration | Pisah ke `FaceDescriptor` model (PasPapan pattern) | ✅ |

### Audit Perlindungan yang Sudah Ada (Confirmed Secure)

| Pattern | Status | Evidence |
|:--------|:-------|:---------|
| CipherSweet encrypted at rest | ✅ | 3 model + blind index + `encryptedUnique` |
| PII masking on GET employees | ✅ | `GET /employees/{id}` masks NIK/phone/NPWP/bank |
| Argon2id hashing | ✅ | 64MB memory, 4 iterasi, `rehash_on_login` |
| Rate limiting | ✅ | 5/1 login/2FA, 10/1 face, 5/5 clock |
| IDOR policy | ✅ | Policy cek ownership via `$user->employee?->id` |
| Session encryption | ✅ | `SESSION_ENCRYPT=true`, HttpOnly, SameSite=Lax |
| Host protection | ✅ | `trustHosts()` aktif |
| FormRequest validasi | ✅ | All 28+ API endpoints |

### Key Decision: Design Direction

| Aspek | Keputusan |
|:------|:----------|
| **CSS/Warna** | Ikut DESIGN.md DS-1 (canvas putih, Inter, high contrast) — **bukan** copy PasPapan (hijau/cream) |
| **UX Pattern** | Ambil dari PasPapan (face enrollment guide overlay, liveness challenge, scan page, approval cards) |
| **Payroll Indonesia** | Post-skripsi — port dari Quanta HRIS (6 service, 3 migration, 3 seeder) |
| **UI Framework** | Pakai DESIGN.md token system (`bg-canvas`, `text-ink`, `rounded-xl`, dll) — tidak hardcode |

---

## Verification Commands

| Purpose | Command |
|---------|---------|
| Fast focused tests | `php artisan test --compact --filter=Name` |
| Full SQLite suite | `composer test` |
| PostgreSQL integration | `composer test:pgsql` |
| Route audit | `php artisan route:list --path=api --except-vendor` |
| Lint auto-fix | `vendor/bin/pint --dirty --format agent` |
| PHPStan | `vendor/bin/phpstan analyse` |
| Dev server | `composer run dev` |

## Execution Order — 6 Sesi (Final, 2026-06-28)

```
SESI A — AUTH & PERMISSION + SEC + BUSINESS LOGIC P0/P1 (🔴 blocking) ✅ COMPLETED
  │  [P0-1] Termination auth ✅ — add authorization gate
  │  [P0-2] Null radius guard ✅ — GeofenceService: throw if branch.radius null
  │  [P0-3] WFA null employee guard ✅ — AttendanceController: return error
  │  [P0-4] Status bypass ✅ — force via EmployeeLifecycleService
  │  [P1-5] Reimbursement TOCTOU ✅ — lock() + ensureReviewable()
  │  [P1-6] Payroll PAID gap ✅ — tambah PayrollStatus::PAID check
  │  [P1-7] Overtime date ✅ — tambah after_or_equal:today
  │  EV-1..7 ✅ — Email Verification + Password Policy
  │  PERM-1/2/3 ✅ — Permission fixes
  │  SEC-1 ✅ — Sanctum token expiry 1 tahun
  │  SEC-2 ✅ — password.expired middleware di API routes
  │  SEC-3 ✅ — Validasi format 2FA code
  │  SEC-4 ✅ — ChangePasswordRequest fix
  │  EV-2 (Gmail SMTP) 🚫 — deferred, MAIL_MAILER=log
  ▼
SESI B — FACE RECOGNITION + GPS ANTI-SPOOFING + LIVENESS (🔴 blocking) ✅ COMPLETED
  │  [P2-2] Risk scoring ✅ — AttendanceRiskScorer (14 faktor)
  │  [P2-3] Anti-replay QR ✅ — DynamicBarcodeTokenService (HMAC-SHA256)
  │  [P2-4] Face embedding ✅ — FaceDescriptor table (pgvector)
  │  C-1..4 ✅ — Camera bug fixes (model path, try/catch, toast, video.play)
  │  FE-1c ✅ — Face enrollment 6 foto sequential
  │  FE-1d ✅ — Liveness micro-movement variance
  │  FE-1e ✅ — TinyFaceDetector default mobile, SSD fallback
  │  FE-1f ✅ — EAR blink detection via landmarks
  │  FE-1g ✅ — Hapus CDN face-api.js, dynamic import via npm+Vite
  │  FE-1h ✅ — Face crop audit trail via extractFaces()
  │  SEC-GPS-1 ✅ — Layer 1: Client-side is_mocked + accuracy 50m
  │  SEC-GPS-2 ✅ — Layer 2: Time-series 3 sampel (variance + speed)
  │  SEC-GPS-3 ✅ — Layer 3: IP cross-check framework (GeoIP deferred)
  ▼
SESI C — RAG KNOWLEDGE BASE (🟡) 🚧 IN PROGRESS
  │  [NEW] app/Livewire/KnowledgeBaseChat.php — minimal Livewire class (public $messages, $input, $conversationId, $isStreaming)
  │  [NEW] resources/views/livewire/knowledge-base-chat.blade.php — Alpine x-data="knowledgeBaseChat()"
  │    ├── sendMessage() → fetch POST /api/v1/knowledgebase/chat-stream
  │    ├── ReadableStream.getReader() → parse SSE data: events
  │    ├── formatMessage() → bold **text**, bullet -, newline
  │    ├── Typing indicator (3 bouncing dots + pulse-dot animation)
  │    ├── Auto-resize textarea, Enter/Shift+Enter
  │    └── Suggestion buttons: "Kebijakan cuti?", "BPJS?", "Jam kerja?"
  │  [NEW] resources/views/knowledge-base/index.blade.php → <x-layouts::app.sidebar> <livewire:knowledge-base-chat />
  │  [NEW] resources/views/knowledge-base/manage.blade.php → Upload form + document list table
  │  [NEW] app/Http/Requests/Api/ChatStreamRequest.php
  │  [EDIT] routes/api.php → POST /chat-stream (throttle:10,1)
  │  [EDIT] app/Http/Controllers/Api/KnowledgeBaseController.php → +chatStream() returns StreamedResponse
  │  [EDIT] app/Services/KnowledgeBaseService.php → +chatStream() returns Generator yielding TextDelta
  │  SSE Protocol: data: {"text":"..."}\n\ndata: {"conversation_id":"...","sources":[...]}\n\ndata: [DONE]\n\n
  │  Error: Gemini/embedding gagal → event error → frontend fallback message
  │  Referensi: /home/merger/RAG-repo/ship-ai-with-laravel (SupportChat.php, support-chat.blade.php, ChatController.php)
  ▼
SESI D — ESS PAGES (🟡)
  │  FE-2: Attendance History
  │  FE-3a/b/c: Leave (Apply + History + Quota)
  │  FE-4: Overtime (Apply + History)
  │  FE-5: Reimbursement
  │  FE-6: Payslip (PIN 6 digit)
  │  FE-7: Profile & Devices
  │  FE-8a: Approvals landing page
  ▼
SESI E — APPROVALS (🟢)
  │  AP-1: Landing page + pending L1
  │  AP-2: Pending L2
  │  AP-3: History
  │  Referensi: §REFERENCE_REPOS → PasPapan HrChecklistService termination pattern
  ▼
SESI F — CLEANUP + SECURITY HEADERS (🟢)
  │  DC-1/2/3: Dead code geofencing
  │  DC-4: PHPStan baseline
  │  C-5: SW /offline
  │  D-1..D-20: Design Sync DS-1
  │  SEC-5: Security headers middleware (CSP, HSTS, dll) + face registration confirmation
  │  A-1..A-5: Architecture cleanup (opsional — jika waktu cukup)
```

### Catatan Kunci Eksekusi
- **Sesi A ✅** — 14/14 items selesai. 7 P0/P1 business logic + email verification + permission fixes + SEC items.
- **Sesi B ✅** — 16/16 items selesai. Camera, face enrollment, liveness, TinyFaceDetector, EAR blink, CDN cleanup, face crop, GPS 3-layer + P2-2/3/4.
- **Sesi C 🚧** — RAG Knowledge Base UI. Pola dari `ship-ai-with-laravel`: Livewire minimal + Alpine.js SSE streaming via `ReadableStream`. Backend AI sudah 100% ready. 3 task: RAG-1 (Chat UI), RAG-2 (Upload+Manage), RAG-3 (Tests). Estimasi 6-8 jam.
- **Sesi D tergantung sesi B** — ESS pages butuh clock-in berfungsi (camera fixed ✅).
- **Sesi E tergantung sesi D** — Approvals page butuh backend approval items.
- **Sesi F bisa di-merge** ke sesi lain jika waktu cukup.
- **Gmail SMTP (EV-2) jangan ditunda terlalu lama** — backend code pakai `MAIL_MAILER=log`, tapi perlu SMTP untuk production.
- **SEC-GPS-3 GeoIP deferred** — framework `crossCheckIpLocation()` siap, butuh `torann/geoip`.
- **Design:** UI follow DESIGN.md DS-1 (canvas putih, Inter, netral) — NOT copy PasPapan CSS.
- **Realitas:** 2/6 sesi done. **Sesi C (RAG) in progress.** 1,121 tests pass, 0 failures.
- **Estimasi total:** 3 sesi remaining ~20-30 jam kerja fokus.
