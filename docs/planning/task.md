# Task Tracker — HRConnect Skripsi: Face Recognition + GPS Geofencing + RAG Knowledge Base

> Updated: 2026-06-28 — Sesi A ✅ B ✅ C ✅ D ✅ E ✅. **Launching 7-day sprint: ~100 Blade files**. 1,121 tests pass.

> **SESI A ✅ (2026-06-28):** 14/14 items completed — EV-1..7, PERM-1/2/3, SEC-1/2/3/4, P0-1..4, P1-5/6/7. **EV-2 (Gmail SMTP) deferred.**

> **SESI B ✅ (2026-06-28):** 13+3 items — C-1..4 camera bugs, FE-1c..h face enrollment/liveness/TinyFaceDetector/EAR blink/CDN cleanup/face crop, SEC-GPS-1/2/3 GPS 3-layer, P2-2/3/4.

> **SESI C ✅ (2026-06-28):** RAG Knowledge Base UI — Chat AI (SSE streaming), Upload PDF, Manage.

> **SESI D ✅ (2026-06-28):** ESS pages — Attendance, Leave, Overtime, Reimbursement, Payroll.

> **SESI E ✅ (2026-06-28):** Approvals — index (Pending+History), detail modal.

> **EXECUTION STATUS (2026-06-28):** Ses A+B+C+D+E ✅ complete. **Launching 7-day sprint — ~100 Blade files. Target berdasarkan fitur HRIS ±100 karyawan (bukan ratio model count).** Backend 100% ready (51 endpoints, 1,121 tests).

> **SECURITY POSTURE (2026-06-28):** Full audit keamanan selesai. Ditemukan **4 critical** (Sanctum token never-expire, fake GPS 100% client-trusted, no liveness detection, no security headers middleware), **8 warning** (MustVerifyEmail, API gate, 2FA enforcement, dll), **8 sudah secure** (CipherSweet, PII masking, Argon2id, rate limiting, IDOR, session encrypted, host protection, FormRequest). Lihat §SECURITY untuk detail. **Post-Sesi A+B: 1 critical fixed (Sanctum expiry ✅, fake GPS multi-layer ✅, liveness ✅), 1 deferred (headers → Day 7).**

## Status Legend

| Status | Meaning |
|--------|---------|
| ✅ | Done |
| 🚧 | In progress |
| ⏳ | Not started |
| 🚫 | Deferred/cancelled |

## Status Snapshot — Overall Project: **~25% selesai** (target ~100 Blade files)

| Area | % | Status | Notes |
|------|:-:|:------:|-------|
| Backend (app/) | 100% | ✅ | 33 models, 34 enums, 15 services, 14 controllers. 51 endpoints. Production-ready. |
| Database (migrations) | 100% | ✅ | 46 migrations, 48 tables. All features supported. |
| API (routes) | 100% | ✅ | 51 endpoints, Sanctum auth, rate limits, permission guards. |
| Security | 85% | ✅ | CipherSweet ✅, PII masking ✅, Argon2id ✅, rate limiting ✅, IDOR ✅, session encrypted ✅, host protection ✅, FormRequest ✅. **All Sesi A fixes ✅. Security headers ❌** (Day 7). |
| Tests | 95% | ✅ | 1,121 tests / 3,729 assertions (SQLite) + ~28 PG. |
| **Frontend** | **~25%** | 🚧 | **~27 functional pages + 3 components = ~30 Blade files selesai.** Butuh ~55 pages + ~15 components + ~8 layouts/partials + ~8 email = ~100 total. |
| **Component Library** | **~20%** | 🚧 | Ada 3 component. Butuh 12 baru (Day 1). |
| **Design System DS-1** | **40%** | 🚧 | app.css still cream palette. Day 7. |
| **Architecture Cleanup** | **40%** | 🚧 | Day 7. |
| **PWA** | **40%** | 🚧 | Day 7. |
| **PHPStan** | 0% | 🚧 | Day 7. |

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

## 📄 PAGE INVENTORY — ~100 Blade Files Target

**PasPapan Reference Data (real, verified):**
- 80 models → **89 route-defined pages** (auth 7 + user 24 + admin 57 + profile 1)
- Plus 57 components + 91 Livewire views (~60 full-page, ~31 embedded) + 7 email + 16 mail vendor + 16 errors + 2 layouts + 7 auth + 4 PDF = ~240 Blade files
- Extra modules HRConnect doesn't have: Toko/POS (12 pages), Collaboration (3), Projects/Operations (4), Commerce (8+), Custom Forms (2), API Integrations (1) = ~30 pages
- Core HR pages (tanpa extra modules): ~59 pages

**HRConnect target: ~59 core pages + ~10 extra (payroll Indonesia, reports) = ~69 pages.**
Dengan modal pattern (create/edit = modal, bukan page), efektif **~55 pages**.

**Total Blade files: 55 pages + 12 components + 8 layouts/partials + 8 email + 4 vendor + ~10 profile/auth modals ≈ ~100 file.**

| Modul | Pages | Modal/Partial | ✅ Sekarang | ❌ Baru | Day |
|-------|:-----:|:-------------:|:-----------:|:------:|:---:|
| Auth + Lock | 8 | — | 7 | 1 | ✅ |
| Dashboard | 2 | — | 0 | 2 | 6 |
| Employee | 2 | 2 | 0 | 4 | 2 |
| Organization | 2 | 2 | 0 | 4 | 3 |
| Attendance | 6 | 2 | 3 | 5 | 3 |
| Leave | 3 | 2 | 2 | 3 | 4 |
| Overtime | 2 | 1 | 2 | 1 | 4 |
| Reimbursement | 2 | 1 | 2 | 1 | 4 |
| Payroll | 8 | 3 | 2 | 9 | 5 |
| Loan | 2 | 1 | 0 | 3 | 5 |
| Asset | 2 | 1 | 0 | 3 | 4 |
| Approvals | 1 | 1 | 2 | 0 | ✅ |
| KB | 2 | — | 2 | 0 | ✅ |
| Reports | 6 | — | 0 | 6 | 6 |
| Notifications | 2 | 1 | 0 | 3 | 6 |
| Settings | 3 | 2 | 3 | 2 | ✅ |
| Email Templates | — | 8 | 0 | 8 | 7 |
| Shared Components | — | 12 | 3 | 9 | 1 |
| **Total ~100** | **~53** | **~40** | **~28** | **~72** | **7 hari** |

### Detail per Modul

Lihat `docs/planning/pages-masterplan.md` untuk listing lengkap per item. Ringkasan:

| Day | Modul | Halaman Baru | Kompleksitas |
|:---:|-------|:------------:|:------------:|
| 1 | Component Library | 9 komponen | 🟡 Sedang — foundation |
| 2 | Employee | 5 (3 pages + 2 modal) | 🟡 Sedang — form heavy |
| 3 | Organization + Attendance | 11 (8 pages + 3 modal) | 🔴 Tinggi — banyak |
| 4 | Leave/Overtime/Reimburse + Asset | 11 (6 pages + 5 modal) | 🟡 Sedang — pola sama |
| 5 | Payroll + Loan | 12 (8 pages + 4 modal) | 🔴 Tinggi — payroll wizard |
| 6 | Reports + Dashboard + Notif | 11 (10 pages + 1 partial) | 🟢 Rendah — tabel/chart |
| 7 | Polish + Email + Cleanup | 12 (1 page + 8 email + 3 cleanup) | 🟢 Rendah — config/css |

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

## 🎯 7-DAY SPRINT — ~72 Blade Files Baru

| Day | Baru | Pages | Modal | Modul |
|:---:|:----:|:-----:|:-----:|-------|
| **1** | 9 | — | 9 | **Component Library** ✅ — page-shell, toolbar, table, card-grid, form-modal, confirm-modal, filter-bar, skeleton, pagination, empty-state |
| **2** | 4 | 2 | 2 | **Employee** — list (grid+table), detail+tabs, create/edit modal, terminate/import modal |
| **3** | 9 | 6 | 3 | **Organization (2 + 2 modal) + Attendance Admin (4 + 1 modal)** — dept, position, company, org-chart, shift, holiday, matrix, balance |
| **4** | 10 | 5 | 5 | **Leave (2+1) + Overtime (1+1) + Reimburse (1+1) + Asset (1+2)** — admin views + CRUD |
| **5** | 12 | 8 | 4 | **Payroll (8+2) + Loan (0+2)** — generate, approve, PDF, allowances, deductions, tax, BPJS, loan |
| **6** | 11 | 10 | 1 | **Reports (6) + Dashboard (2) + Notifications (2+1) + Lock (1)** — recap, chart, center, preferences |
| **7** | 12 | 1 | 11 | **Polish + Email (8) + Cleanup (3)** — DS-1 sync, templates, PHPStan, SW, security headers |
| **Total** | **~72** | **~32 pages** | **~40 modal/partial** | **+~28 existing = ~100 Blade files** |

### Day 1 — Component Library

Komponen reusable yang dibangun sekali, dipakai semua halaman:

| Komponen | Tipe | Slot/Props | Untuk |
|----------|------|------------|-------|
| `x-page-shell` | Layout wrapper | title, subtitle, actions | Setiap halaman |
| `x-page-toolbar` | Action bar | search, filters, buttons | Semua index page |
| `x-simple-table` | Data table | columns, rows, sort, striped, hover, actions | Semua list |
| `x-card-grid` | Card grid | items, columns (2/3/4) | Employee, asset cards |
| `x-form-modal` | Modal form | title, fields, submit, loading, validation | Semua create/edit |
| `x-confirm-modal` | Confirm dialog | title, message, confirm, cancel, variant | Delete, terminate |
| `x-filter-bar` | Filter group | date-range, status, search, apply/reset | Semua admin list |
| `x-loading-skeleton` | Shimmer | type (table/card/form), rows, cols | Semua page |
| `x-pagination` | Page nav | current, total, per-page, on-page-change | Semua table |
| `x-empty-state` | Empty state | icon, title, desc, action-button | Semua list kosong |
| `x-status-badge` | Status pill | status, size (sm/md) | ✅ existing, upgrade |
| `x-button` | Button | variant, size, loading, disabled | ✅ existing, upgrade |

### Day 2 — Employee (5 Blade: 3 pages + 2 modal)

| Item | Tipe |
|------|------|
| Employee list (grid+table toggle) | page — `x-page-shell` + `x-card-grid`/`x-simple-table` |
| Employee detail + 4 tabs (profile/bank/family/documents) | page — `x-page-shell` + tab sections |
| Employee create/edit | modal — `x-form-modal` |
| Terminate employee | confirm modal — `x-confirm-modal` + lifecycle checklist |
| Bulk import/export | modal — file upload + preview |

### Day 3 — Organization + Attendance Admin (11 Blade: 8 pages + 3 modal)

| Item | Tipe |
|------|------|
| Department list + create/edit | page + modal |
| Position list + create/edit | page + modal |
| Company settings | page |
| Org chart | page |
| Shift list + create/edit | page + modal |
| Holiday list + create/edit | page |
| Holiday calendar (FullCalendar) | page |
| Attendance admin matrix | page |
| Leave balance adjustment | modal |

### Day 4 — Leave/Overtime/Reimburse + Asset (11 Blade: 6 pages + 5 modal)

| Item | Tipe |
|------|------|
| Leave admin view (all requests + filter) | page |
| Leave types list + create/edit | page + modal |
| Leave calendar (team view) | page |
| Overtime admin view (filter + approve) | page |
| Overtime rates config | page |
| Reimbursement admin view (filter + approve) | page |
| Reimbursement categories + create/edit | modal |
| Asset list (grid+table toggle) | page |
| Asset create/edit | modal |
| Asset detail (info + history) | page |

### Day 5 — Payroll + Loan (12 Blade: 8 pages + 4 modal)

| Item | Tipe | Notes |
|------|------|-------|
| Payroll ESS index | page | ✅ existing, upgrade PIN modal |
| Payslip detail | page | ✅ existing, upgrade breakdown |
| Payslip PDF | PDF | dompdf |
| Payroll generation wizard | page | period → preview → generate |
| Payroll approval L1 (HR) | page + modal | Review draft |
| Payroll approval L2 (Finance) | page + modal | Final approval |
| Payroll detail admin | page | Employee breakdown |
| Batch payslip PDF | PDF | ZIP |
| Allowances CRUD | page + modal | |
| Deductions CRUD | page + modal | |
| PTKP/TER config | page | |
| BPJS config | page | |
| Loan ESS index | page | |
| Loan apply | modal | |
| Loan admin view | page | |

### Day 6 — Reports + Dashboard + Notifications (11 Blade: 10 pages + 1 partial)

| Item | Tipe |
|------|------|
| Admin dashboard (cards + chart) | page |
| ESS dashboard (status + quick actions) | page |
| Attendance recap + PDF | page |
| Payroll financial report | page |
| PPh21 report | page |
| BPJS report | page |
| Performance report | page |
| Custom export | modal |
| Notification center | page |
| Notification dropdown | partial |
| Notification preferences | page |
| Lock screen | page |

### Day 7 — Polish + Email + Cleanup (12 Blade: 1 page + 8 email + 3 components)

| Task | Detail |
|------|--------|
| **DS-1 sync (D-1..20)** | app.css: canvas #ffffff, body #3a3a3a, Inter font, radius tokens, hapus brand colors |
| **Loading skeletons** | `x-loading-skeleton` di semua page yang belum |
| **Error states** | Error boundary + retry button di semua async fetch |
| **Empty states** | `x-empty-state` + ilustrasi inline untuk semua list kosong |
| **Responsive check** | Mobile cards view untuk semua table |
| **Email templates (8)** | leave approved/rejected/cancelled, overtime approved/rejected, reimbursement approved/rejected, account created |
| **SW offline fix (C-5)** | Hapus `/offline` dari PRECACHE, exclude `/api/*` |
| **PHPStan baseline (DC-4)** | Hapus 3 entry deleted notifications |
| **Dead code cleanup (DC-1/2/3)** | GeofenceValidation middleware, Branch::validateRadius(), anti-fake-GPS duplikat |
| **Security headers (SEC-5)** | CSP, HSTS, X-Frame-Options middleware |
| **Bug approvals `:title` fix** | Ganti `:title` dengan Alpine `x-show` untuk empty state |
| **Full test suite** | `composer test` — harus green |

---

## Project Stats

| Metric | Value |
|--------|-------|
| App PHP files | 193 |
| App LOC | ~12,070 |
| Models | 33 |
| API endpoints | 51 at `/api/v1` |
| Backend tests | 1,121 / 3,702 (SQLite) + ~28 (PG) |
| Sesi sebelumnya | **A+B+C+D+E ✅** (44 item) |
| **Functional pages ✅** | **~27** (auth 7 + settings 3 + ESS 8 + approvals 1 + KB 2 + attendance 2 + face 1 + payroll 2 + dashboard 1) |
| **Components/layouts ✅** | **~11** |
| **Total existing Blade** | **~56** (including vendor, partials, modals) |
| **Target pages baru** | **~28** (dari ~55 target - 27 existing) |
| **Target Blade total** | **~100** (55 pages + 15 components + 8 layouts/partials + 8 email + 4 vendor + 10 profile/auth modals) |
| Estimasi waktu | **7 hari × 24 jam** |

---

## 🔐 PHASE AUTH: Email Verification + Password Policy ✅ (Selesai Sesi A)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **EV-1** | **Uncomment MustVerifyEmail di User model** | ✅ | 🔴 | ✅ |
| **EV-2** | **Setup Gmail SMTP** | 🚫 Deferred — `MAIL_MAILER=log` | 🔴 | 🚫 |
| **EV-3** | **API gate verified di login** | ✅ | 🔴 | ✅ |
| **EV-4** | **API endpoint verify + resend** | ✅ | 🔴 | ✅ |
| **EV-5** | **Force change password middleware** | ✅ | 🔴 | ✅ |
| **EV-6** | **EmployeeController@store kirim verifikasi** | ✅ | 🔴 | ✅ |
| **EV-7** | **Set password_changed_at = null** | ✅ | 🔴 | ✅ |

---

## 🛡️ PHASE PERM: Permission Fix ✅ (Selesai Sesi A)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **PERM-1** | **view_attendances → Finance** | ✅ | 🔴 | ✅ |
| **PERM-2** | **view_knowledgebase → Employee** | ✅ | 🔴 | ✅ |
| **PERM-3** | **approve_wfa → hr-manager** | ✅ | 🔴 | ✅ |

---

## 🤖 PHASE RAG: Knowledge Base UI (Sesi C ✅)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **RAG-1** | **Web UI chat** | ✅ Livewire minimal + Alpine.js SSE streaming. | 🟡 | ✅ |
| **RAG-2** | **Upload PDF + Manage** | ✅ `manage.blade.php` upload form + document list table. | 🟡 | ✅ |
| **RAG-3** | **Tests** | ✅ SSE endpoint, auth/permission/throttle. | 🟡 | ✅ |

---

## 📸 PHASE FACE: Face Enrollment ✅ (Selesai Sesi B)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **FE-1c** | **Face enrollment UI** | 6 foto sequential + progress dots + countdown → 6× 128D embeddings → POST /api/v1/face/register. Liveness via micro-movement variance > 0.5. Face crop audit trail. | 🟡 | ✅ |
| **FE-1d** | **Liveness micro-movement** | Variance antar embedding frame > 0.5 | 🟡 | ✅ |
| **FE-1e** | **TinyFaceDetector default** | 190KB, SSD fallback | 🟡 | ✅ |
| **FE-1f** | **EAR blink detection** | `computeEAR()` via landmarks, open→closed→open | 🟡 | ✅ |
| **FE-1g** | **Hapus CDN face-api.js** | Dynamic import via npm+Vite | 🟡 | ✅ |
| **FE-1h** | **Face crop audit trail** | `extractFaces()` → `canvas.toBlob` | 🟡 | ✅ |

---

## 🔑 PHASE 2FA: 2FA Enforcement (Deferred)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **2FA-1** | **Enforce 2FA untuk HR/Finance/SuperAdmin** | Gate di middleware/controller: cek role, redirect ke setup 2FA jika belum (AUTH-09) | 🟡 | 🚫 Deferred — post-skripsi |

---

## 🧹 PHASE CLEANUP: Dead Code (NEW — 2026-06-24)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **DC-1** | **Hapus GeofenceValidation middleware** | 0 caller, Haversine duplikat dari GeofenceService | 🟢 | ⏳ |
| **DC-2** | **Hapus Branch::validateRadius()** | 0 caller, Haversine ke-3 | 🟢 | ⏳ |
| **DC-3** | **Satukan anti-fake-GPS check** | Hanya di GeofenceService, hapus dari AttendanceService | 🟢 | ⏳ |
| **DC-4** | **PHPStan baseline** | Hapus 3 entry deleted notifications | 🟢 | ⏳ |

## 🎯 7-DAY SPRINT — Ringkasan Strategi (2026-06-28)

### Goal
Complete **~100 Blade files (~55 functional pages + ~45 modals/components/emails)** dalam 7 hari menggunakan component-based system. Backend 100% ready (51 endpoints, 1,121 tests pass). Yang kurang hanya **layer Blade + Alpine**.

**Referensi PasPapan (verified):** 80 models → 89 route-defined pages (57 admin + 24 user + 7 auth + 1 profile). Extra 30 pages untuk Toko/POS/Collaboration/Commerce yang HRConnect tidak butuh. Core HR pages: ~59. HRConnect butuh ~55 pages + extra payroll Indonesia/reports = ~55 pages + ~45 modals = ~100 Blade files.

### Strategy
1. **Day 1**: Bangun 10 reusable components → semua halaman setelahnya = assembly, not from scratch.
2. **Day 2-6**: ~12-15 halaman/hari dengan pola identik: `x-page-shell` + table/card-grid + `x-form-modal` untuk CRUD.
3. **Day 7**: Polish — DS-1 sync, loading/error/empty states, responsive, security headers, email templates.
4. **Form apply (leave/overtime/reimbursement)** pakai **`x-form-modal`** (drawer-style) — tidak perlu page baru.
5. **Employee create/edit** pakai `x-form-modal` — form cukup pendek untuk ±10-15 field.
6. **Payroll complex flow** (generation wizard, L1/L2 approval) pakai page standalone + modals untuk action points.
7. **CSS**: 100% utility classes + CSS variables dari DS-1 — no custom CSS per halaman.

### Prinsip Kecepatan
| Prinsip | Detail |
|---------|--------|
| **Component-first** | Setiap halaman = `<x-page-shell>` + `<x-simple-table>` + `<x-form-modal>` |
| **Pattern copy** | Employee = template untuk Department, Asset, Loan, Allowance, dll |
| **Modal for CRUD** | Tidak pernah bikin page terpisah untuk create/edit — selalu `x-form-modal` |
| **Table for list** | Tidak pernah bikin custom list view — selalu `x-simple-table` atau `x-card-grid` |
| **No custom CSS** | Hanya utility classes. Kalau butuh style baru, tambah ke CSS variables |
| **Backend first** | Semua data dari API endpoint existing — tidak perlu backend change

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

### ✅ Status Realistis — Semua Sesi Sebelumnya Selesai

| Sesi | Status | Notes |
|:----|:------:|:------|
| A (Auth + Sec) | ✅ | 14 items — email verification, permission, security, business logic |
| B (Face + GPS) | ✅ | 16 items — camera fix, face enrollment, liveness, GPS 3-layer, TinyFaceDetector, EAR blink, face crop |
| C (RAG) | ✅ | 3 items — chat UI + SSE streaming, upload/manage, tests |
| D (ESS) | ✅ | 8 items — attendance index redesign, apply/index leave/overtime/reimbursement/payroll |
| E (Approvals) | ✅ | 3 items — index with pending/history tabs, detail modal |
| **Selesai** | **✅ A+B+C+D+E** | **44 items** |
| **Sekarang: 7-Day Sprint** | **🚧** | **~72 Blade baru = ~100 total** |

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

### PasPapan — Primary Reference (251 Blade, 80 models, 58 components, 96 Livewire)

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

## Execution Order — 7-Day Full Pages Sprint

```
SESI A ✅ — Auth & Permission + Security + Business Logic (14 items)
SESI B ✅ — Face Recognition + GPS Anti-Spoofing + Liveness (16 items)
SESI C ✅ — RAG Knowledge Base UI (3 items)
SESI D ✅ — ESS Pages (8 items)
SESI E ✅ — Approvals (3 items)
  ═══════════════════════════════════════════════════
  NOW: 7-DAY SPRINT — ~100 Blade Files
  ═══════════════════════════════════════════════════
DAY 1 — Component Library (10 reusable components)
DAY 2 — Employee CRUD (12 halaman)
DAY 3 — Organization + Attendance Admin (11 halaman)
DAY 4 — Leave/Overtime/Reimburse Admin + Asset (12 halaman)
DAY 5 — Payroll + Loan (15 halaman)
DAY 6 — Reports + Dashboard + Notifications (12 halaman)
DAY 7 — Polish + Email + Cleanup + Security Headers
  ═══════════════════════════════════════════════════
  TOTAL: ~72 Blade baru + ~28 existing = ~100 Blade
  ═══════════════════════════════════════════════════
```

### Catatan Kunci Eksekusi
- **Sesi A+B+C+D+E ✅** — Semua selesai. Backend 100% ready (51 endpoints, 1,121 tests).
- **Component library adalah kunci** — Day 1 menentukan kecepatan Day 2-6. Jangan skip.
- **Form modal pattern** — Semua create/edit pakai `x-form-modal`, bukan page terpisah. Kecuali payroll generation (wizard) dan company settings (form panjang).
- **Gmail SMTP (EV-2)** masih deferred — `MAIL_MAILER=log` aktif, bisa dialihkan kapan saja.
- **SEC-GPS-3 GeoIP** masih deferred — framework `crossCheckIpLocation()` siap, butuh `torann/geoip`.
- **Design:** UI follow DESIGN.md DS-1 (canvas putih, Inter, netral) — NOT copy PasPapan CSS.
- **~100 Blade files target** — berdasarkan analisis fitur HRIS ±100 karyawan, diverifikasi dengan data PasPapan (89 route-defined pages untuk core HR = ~59 pages tanpa Toko/Commerce). HRConnect ~55 pages + ~45 modals/emails/components = ~100.
