# Task Tracker — Frontend MD3 Migration + ESS Development

> Updated: 2026-06-24 — Flux UI sudah dihapus total. Fokus sekarang: **Perbaikan bug camera, DS-1 design system sync, architecture cleanup, dan penyelesaian ESS components**.

> **AUDIT FINDINGS (2026-06-24):** Full codebase audit + docs review selesai. Menemukan 15 item baru. Konteks: ±100 karyawan, HR buat akun (bukan self-register). Tidak perlu Opsi B (architectural refactor) — skala kecil. Lihat §AUDIT untuk detail.

## Status Legend

| Status | Meaning |
|--------|---------|
| ✅ | Done |
| 🚧 | In progress |
| ⏳ | Not started |
| 🚫 | Deferred/cancelled |

## Status Snapshot — Overall Project: **~75%** (±100 karyawan)

| Area | % | Status | Notes |
|------|:-:|:------:|-------|
| Backend (app/) | 95% | ✅ | 31 models, 34 enums, 15 services, 14 controllers. Kurang strict_types, base exception, queue consistency. |
| Database (migrations) | 90% | ✅ | 47 migrations, 48 tables. 5 models without factories (deferred V2). |
| API (routes) | 95% | ✅ | 42 endpoints, Sanctum auth, rate limits, permission guards. |
| Security | 75% | 🚧 | CipherSweet ✅, Sanctum ✅, 2FA ✅, PII masking ✅. **Email verification ❌** (MustVerifyEmail di-comment). **Force change password ❌** (middleware skip null). **2FA enforcement ❌** (AUTH-09). Password expiry middleware belum di-wire. |
| Tests | 85% | ✅ | 1,121 tests / 3,702 assertions (SQLite) + ~28 PG. All services/controllers/policies covered. |
| Flux → MD3 Migration | 100% | ✅ | **SELESAI** — Flux dihapus dari composer, views, CI, docs. 0 Flux references remain. |
| **Design System DS-1** | **40%** | 🚧 | app.css masih pakai cream palette (#fffaf0), harusnya #ffffff. 11 item perlu sync dengan DESIGN.md. |
| **Frontend Views (46 Blade)** | **50%** | 🚧 | Layouts ✅, Auth ✅, Settings ✅. Modules (attendance, leave, etc.) masih static Blade. |
| **ESS Features** | **30%** | 🚧 | Clock-in page exists (dengan bugs). **Face enrollment ❌** (FE-1c not started). Belum Livewire interaktif. |
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
| 1 | Index / Chat UI | `knowledge-base/index.blade.php` | All (Employee + HR) | ❌ ⚠️ 500 |
| 2 | Upload | `knowledge-base/upload.blade.php` | HR Manager | ❌ |
| 3 | Manage Articles | `knowledge-base/manage.blade.php` | HR Manager | ❌ |

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
| 3 | **Face Registration** | `employee/profile/face-registration.blade.php` | Employee | ❌ ⭐ |
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
| `knowledge-base.index` | `knowledge-base/index.blade.php` | ❌ |
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
| **FE-1** | **Clock In/Out** ⭐ | — | 🚧 |
| | 1a. Install face-api.js + model weights | ✅ model weights di `public/models/av1/` | ✅ |
| | 1b. Camera + face detection | ✅ inline Alpine `x-data`, `getUserMedia`, face-api.js | 🚧 |
| | 1c. Face enrollment | ⏳ |
| | 1d. GPS locator | ✅ di clock-in page | ✅ |
| | 1e. Livewire component | Belum — pake Alpine dulu | ⏳ |
| | 1f. Camera card Blade | ✅ Ada, tapi ada bugs | 🚧 |
| | 1g. Location panel | ✅ | ✅ |
| | 1h. CTA button | ✅ | ✅ |
| | 1i. API integration | ✅ fetch to `/api/v1/attendance/clock-in` | ✅ |
| | 1j. Tests | ⏳ |
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
| 1 | C-1 | **Model path salah** — `loadFromUri('/models')` harus `/models/av1` | `clock-in.blade.php:23-25` | ⏳ |
| 2 | C-2 | **Pisah try/catch camera** — `getUserMedia` vs `play()` harus terpisah biar error handling jelas | `clock-in.blade.php:30-38` | ⏳ |
| 3 | C-3 | **Toast event mismatch** — `window.dispatchEvent(CustomEvent)` vs handler `Livewire.on()` — sistem event beda | `clock-in.blade.php:76-82` + `app.js` | ⏳ |
| 4 | C-4 | **`video.play()` silent fail** — `catch {}` swallow error, status misleading | `clock-in.blade.php:35` | ⏳ |
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

## 🤖 PHASE RAG: Knowledge Base UI (NEW — 2026-06-24)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **RAG-1** | **Web UI chat** | Livewire component + Blade untuk chat AI | 🟡 | ⏳ |
| **RAG-2** | **Upload PDF UI** | Form upload + status embedding | 🟡 | ⏳ |
| **RAG-3** | **view_knowledgebase di Employee** | Sudah di PERM-2 (link) | 🟡 | ⏳ |

---

## 📸 PHASE FACE: Face Enrollment (NEW — 2026-06-24)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **FE-1c** | **Face enrollment UI** | Camera capture → face-api.js 128D → POST /api/v1/face/register | 🟡 | ⏳ |

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

## Execution Order (Revised 2026-06-24)

```
PHASE AUTH (Email Verification + Password Policy)
  │  EV-1: MustVerifyEmail uncomment + trait
  │  EV-2: Gmail SMTP setup (.env)
  │  EV-3: API gate verified di AuthController@login
  │  EV-4: API endpoint verify + resend (mobile)
  │  EV-5: CheckPasswordExpired Tier 3 — force redirect
  │  EV-6: EmployeeController@store — kirim verifikasi email
  │  EV-7: Set password_changed_at = null di store
  ▼
PHASE PERM (Permission Fix)
  │  P-1: view_attendances ke Finance
  │  P-2: view_knowledgebase ke Employee
  ▼
PHASE 3 (Critical Bugs)
  │  C-1, C-2, C-3, C-4  →  C-5, C-6, C-7, C-8, C-9
  ▼
PHASE RAG (Knowledge Base UI)
  │  KB-1: Livewire chat component
  │  KB-2: Upload PDF UI
  │  KB-3: view_knowledgebase permission untuk Employee
  ▼
PHASE FACE (Face Enrollment)
  │  FE-1c: Face enrollment UI
  ▼
PHASE 2FA (2FA Enforcement)
  │  2FA-1: Enforce 2FA untuk HR/Finance/SuperAdmin (AUTH-09)
  ▼
PHASE 4 (DS-1 Design Sync)
  │  D-1 to D-15 (colors)  →  D-16 to D-20 (font, radius, brand)
  ▼
PHASE 5 (Component Alignment)
  │  S-1 to S-7
  ▼
PHASE 6 (Architecture Cleanup)
  │  A-1 to A-5  →  Dead code cleanup
  ▼
PHASE 2 (ESS Frontend)
  │  FE-1 (remaining)  →  L-2, L-5, L-6  →  FE-8 (remaining views)
  │
  ├──→  FE-2 (Attendance History)
  ├──→  FE-3 (Leave) + FE-4 (Overtime) + FE-5 (Reimbursement)
  ├──→  FE-6 (Payroll Slip)
  └──→  FE-7 (Profile) + FE-8 (remaining views)
          │
          └──→  IN-1/2/3 (Inbox + RAG)  →  LP-1 + P-1 to P-4
  ▼
PHASE 7 (Feature Gaps)
  │  F-1 to F-9
```

**Catatan penting (2026-06-24 update):**
- **AUTH PHASE = PRIORITAS BARU #1.** Email verification + force change password harus beres dulu sebelum fitur lain. Ini prasyarat keamanan dasar.
- **±100 karyawan → tidak perlu Opsi B** (hasRole → can refactor, team-scoped query, policy gate di ApprovalController). Skip semua architectural refactor.
- **Critical bugs (C-1 s/d C-9) tetap prioritas tinggi** — camera clock-in harus berfungsi.
- **FE-8c (Knowledge Base view) urgent** — route ada tapi view tidak, error 500.
- **Face enrollment (FE-1c)** perlu dibuat dari nol — belum ada UI register face.
- **2FA enforcement (AUTH-09)** untuk HR/Finance/SuperAdmin — SRS require, belum implement.
- **PIN hanya untuk payslip** (download E-Payslip), bukan untuk absensi.
- **Verifikasi Clock In:** Face recognition (128D embedding) → match → retry 3x → fallback PIN.
