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

---

# 🔴 Browser Console Error Analysis — 1 Juli 2026

Hasil inspeksi browser console pada semua halaman. **Error JS runtime menghalangi rendering halaman, bukan error route Laravel.**

## F1 — `Failed to resolve module specifier 'face-api.js'` 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **Error** | `Uncaught TypeError: Failed to resolve module specifier 'face-api.js'` |
| **Sumber** | `face-registration.blade.php:22` (startCamera) + `clock-in.blade.php:21` (init) |
| **Akar masalah** | `import('face-api.js')` dipanggil di dalam string Alpine `x-data` expression. Alpine mengevaluasi string ini di **luar konteks module Vite** — bare module specifier `'face-api.js'` tidak bisa di-resolve oleh Vite. |
| **Dampak** | Semua fitur face (registrasi + clock-in) **tidak berfungsi sama sekali** |
| **Fix** | Import statis di `app.js`, expose ke `window.faceapi` (sama seperti pattern `window.L = L` untuk Leaflet). Hapus `import()` dari blade, pakai `window.faceapi` langsung. |

## F2 — `face_crop` vs `photo_selfie` mismatch 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **File** | `clock-in.blade.php:166` vs `ClockInRequest.php:48` |
| **Sekarang** | JS kirim `payload.face_crop`, server validasi `photo_selfie` |
| **Dampak** | `$data['photo_selfie']` = null → **foto wajah tidak pernah tersimpan** di DB |
| **Fix** | Ganti `payload.face_crop` → `payload.photo_selfie` di `clock-in.blade.php:166` |

## F3 — `gps_variance` missing dari ClockInRequest rules 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **File** | `ClockInRequest.php` (rules) |
| **Sekarang** | `gps_variance` tidak ada di rules array |
| **Dampak** | Controller panggil `$request->validated()` — field tidak masuk validasi. `$data['gps_variance']` = null di AttendanceService → risk scorer dapat null |
| **Fix** | Tambah `'gps_variance' => ['nullable', 'numeric', 'min:0']` ke `ClockInRequest::rules()` |

## F4 — `is_mocked: false` hardcoded 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **File** | `clock-in.blade.php:159` |
| **Sekarang** | `is_mocked: false` selalu dikirim tanpa pengecekan GPS mock |
| **Dampak** | Anti-fake-GPS check di `AttendanceService::clockIn()` (cek `$data['is_mocked'] == true`) **tidak pernah trigger** — GPS palsu tidak terdeteksi |
| **Fix** | Hapus hardcode; biarkan server handle null |

---

# 🔴 Comprehensive Code Audit — 4 Parallel Agents (1 Juli 2026)

Audit mendalam kode HRConnect oleh 4 agen paralel. **40+ temuan terverifikasi.**

## CRITICAL (Harus diperbaiki segera)

| ID | Temuan | File | Dampak |
|:--:|--------|------|--------|
| **C1** | SQL injection via `LIKE` wildcard | `EmployeeController` | Search `%_%` match unintended records. `%` + `_` wildcard tidak di-escape. |
| **C2** | Mass assignment di EmployeeController | `EmployeeController::store()` | Field `face_embedding`, `pin` bisa di-set via mass request — tidak ada Guard |
| **C3** | PII leak via EmployeeResource | `app/Http/Resources/EmployeeResource.php` | `nik`, `phone`, `npwp`, `bank_account_number` terexpose di listing — Hidden attribute hanya untuk serialization langsung |
| **C4** | Dual face embedding desync | `FaceController::register()` | Embedding disimpan di **2 tempat**: `face_descriptors` + `employee.face_embedding`. Tidak ada mekanisme sinkronisasi — desync jika salah satu gagal. |
| **C5** | View expose full model | `BelongsTo` + `HasMany` tanpa select guard | Resource query `load()` bisa expose hidden fields via relationship eager loading |
| **C6** | Amount integer truncation | Migration `decimal` → PHP `int` cast | Payroll amount dengan decimal bisa truncated saat kalkulasi |
| **C7** | FeaturePolicy / ApprovalPolicy missing | `ApprovalController` | Tidak ada Policy class — autorisasi manual `approver_id` check. Satu-satunya controller tanpa Policy. |
| **C8** | `import('face-api.js')` gagal di Alpine | face-registration, clock-in | **Lihat F1** — semua fitur face broken |
| **C9** | `face_crop` vs `photo_selfie` mismatch | clock-in, ClockInRequest | **Lihat F2** — foto wajah tidak tersimpan |
| **C10** | `gps_variance` missing dari rules | ClockInRequest | **Lihat F3** — risk scorer tidak dapat data |
| **C11** | `is_mocked: false` hardcoded | clock-in blade | **Lihat F4** — anti-fake-GPS tidak berfungsi |

## HIGH (Prioritas setelah CRITICAL)

| ID | Temuan | File | Detail |
|:--:|--------|------|--------|
| **H1** | 7 route closures disable `route:cache` | 7 route files di `routes/` | Route closures prevent Laravel route caching — gunakan invokable controller |
| **H2** | Missing `verified` middleware di `profile.edit` | `routes/settings.php` | Profile bisa diakses tanpa verifikasi email |
| **H3** | Email enumeration timing | `FortifyServiceProvider` | Login response time berbeda untuk email exist vs tidak — timing attack vector |
| **H4** | Route closures di 7 files | `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php` | Multiple files pakai closure → `route:cache` skip semua |
| **H5** | Missing auth on `categories()` | Route definition | Endpoint tanpa auth guard |
| **H6** | Livewire navigated toast re-registration | `app.js` | `livewire:navigated` listener mungkin daftarkan multiple handler → multiple toast muncul |
| **H7** | `Number::currency` intl fallback | `Number::currency($amount, 'IDR')` | Fallback locale mungkin tidak support IDR formatting |
| **H8** | Payroll period LIKE full scan | Period query | Full table scan untuk filter period |
| **H9** | Dual font loading | CSS | Font dimuat 2x — sekali dari Google Fonts, sekali dari local |
| **H10** | Face liveness variance threshold 0.5 terlalu longgar | `face-registration.blade.php:104` | Variance 0.5 sebagai threshold liveness — terlalu rendah |
| **H11** | EAR (Eye Aspect Ratio) tidak pernah digunakan untuk liveness | `clock-in.blade.php:105-107` | EAR dihitung (`computeEAR`) tapi tidak dipakai untuk apa pun |
| **H12** | Descriptor dihitung setiap frame | `clock-in.blade.php:104` | `lastDescriptor` di-update tiap frame (300ms) — sia-sia, cukup 1x saat clock-in |
| **H13** | Dual storage FaceDescriptor vs Employee.face_embedding | `FaceRecognitionService` | Service cek 2 tempat berbeda — desync risk tinggi |
| **H14** | Old FaceDescriptor tidak di-deactivate saat re-enroll | `FaceController::register()` | Tidak ada `is_active = false` untuk descriptor lama → multiple active descriptors |

## MEDIUM

| ID | Temuan | File | Detail |
|:--:|--------|------|--------|
| **M1** | No model timeout di FaceRecognition queries | `FaceRecognitionService` | Query tanpa timeout — bisa hang |
| **M2** | Empty catch swallows errors | `face-registration.blade.php:70,114,156` | `catch {}` tanpa logging — error siluman |
| **M3** | 6 JPEG base64 dikirim ke server via captures | `face-registration.blade.php:139-146` | Base64 JPEG captures terkirim tapi hanya `captures_count` yang disimpan. Boros bandwidth. |
| **M4** | `computeVariance()` math salah | `face-registration.blade.php:120-133` | Variance dibagi `embeddings.length` bukan `(embeddings.length * dims)` |
| **M5** | Vite dynamic import mungkin gagal di production | `face-registration.blade.php:22`, `clock-in.blade.php:21` | Dynamic `import()` di production = chunk split issue |
| **M6** | No `beforeunload` cleanup | `face-registration.blade.php` | Stream tidak dihentikan saat user navigasi away |
| **M7** | No CSRF rotation | Rate limiter config | CSRF token tidak rotate |
| **M8** | Password expiry not notified | Password expiry config | User tidak dapat notifikasi sebelum password expired |
| **M9** | Device verification whitelist no-op | Device verification | Whitelist tidak diimplementasi — bypass verification repeat |
| **M10** | Audit log untuk PII access tidak detail | Audit Log | Tidak ada detail field apa yang diakses |

## LOW

| ID | Temuan | Detail |
|:--:|--------|--------|
| **L1** | `for` attribute di label tidak cocok dengan `id` input | Beberapa form komponen |
| **L2** | `alt` text missing di beberapa img | Aksesibilitas |
| **L3** | Console log statement di production | `console.log` masih ada di beberapa JS file |
| **L4** | Magic number `0.5` untuk scoreThreshold | Tidak ada konstanta bernama |
| **L5** | `setTimeout(..., 1500)` untuk GPS sampling — hardcoded | Tanpa konstanta |
| **L6** | No empty state untuk loading error | Beberapa tampilan loading tanpa error handling UI |
| **L7** | Inconsistent `__()` usage (ID vs EN mix) | Beberapa blade masih pakai English |
| **L8** | `destroy()` method di Alpine tidak selalu dipanggil | Beberapa komponen tanpa cleanup |

---

# 🔴 Role-Based UI Audit — 1 Juli 2026

Temuan: **Semua role mendapatkan UI yang identik.** Single layout untuk HR-Manager, Finance, Manager, dan Employee.

## Root Cause

| Aspek | Detail |
|-------|--------|
| **Layout** | Semua view pakai `x-layouts::app.sidebar` — tidak ada perbedaan untuk admin vs user |
| **Sidebar** | Semua role lihat 10+ menu item yang sama. Hanya 1 item (`Karyawan`) digate dengan `@can('viewAny', Employee::class)` |
| **Bottom nav** | `components/bottom-nav.blade.php` — **zero role gating**. Staff lihat menu Master Data, Penggajian, dll. |
| **Dashboard** | `DashboardController` sudah role-aware untuk statistik, tapi QuickActions dan menu navigasi tidak dibedakan |
| **Route prefix** | Tidak ada prefix `admin/*` untuk fitur admin — semua route flat |

## Temuan Spesifik

| ID | Issue | Dampak |
|:--:|-------|--------|
| **R1** | Sidebar item sama untuk semua role | Staff bisa lihat menu Master Data, Penggajian yang tidak relevan |
| **R2** | Bottom nav tanpa gate | Mobile navigation expose fitur admin ke semua user |
| **R3** | QuickActions sudah partial fix (approvals, dokumen) | Tapi 8 action lainnya masih sama untuk semua role |
| **R4** | Tidak ada `admin-ui` vs `user-ui` body class | CSS targeting per role tidak mungkin |
| **R5** | Tidak ada admin route prefix pattern | `admin.*` route names tidak bisa di-detect di layout |

## Pattern Fix (Dari PasPapan)

```
Detect route name di layout:
- admin.* route prefix → body class `admin-ui` → sidebar penuh
- employee.* atau user.* → body class `user-ui` → sidebar minimal + bottom nav

Route naming:
- admin.employees.index, admin.payroll.index → HR/Finance
- employee.attendance.index, employee.leave.index → Staff
```

---

# 🔴 Face Registration & Recognition Audit — 26 Issues (1 Juli 2026)

Hasil audit mendalam terhadap flow face registration (face-registration.blade.php + FaceController) dan face recognition (clock-in.blade.php + FaceRecognitionService + AttendanceService).

## CRITICAL (3)

| ID | Issue | File | Fix |
|:--:|-------|------|-----|
| **FC1** | `gps_variance` di-drop oleh `$request->validated()` | `ClockInRequest.php` | Tambah `'gps_variance' => ['nullable', 'numeric', 'min:0']` — **sama dengan F3** |
| **FC2** | `face_crop` dikirim JS tapi `photo_selfie` divalidasi server | `clock-in.blade.php:166` vs `ClockInRequest.php:48` | Ganti `face_crop` → `photo_selfie` — **sama dengan F2** |
| **FC3** | `is_mocked: false` hardcoded | `clock-in.blade.php:159` | Hapus hardcode — **sama dengan F4** |

## HIGH (5)

| ID | Issue | File | Detail |
|:--:|-------|------|--------|
| **FC4** | Liveness variance threshold 0.5 terlalu longgar | `face-registration.blade.php:104` | Variance 0.5 tidak membedakan gerakan real vs statis. Perlu threshold lebih ketat atau challenge-response |
| **FC5** | EAR (Eye Aspect Ratio) dihitung tapi tidak digunakan | `clock-in.blade.php:105-107, 119-126` | `computeEAR()` dipanggil setiap deteksi, hasilnya di-push ke `earHistory`, tapi **tidak pernah** digunakan untuk verifikasi liveness (blink detection) |
| **FC6** | Descriptor dihitung setiap frame — sia-sia | `clock-in.blade.php:104` | `lastDescriptor` di-update tiap 300ms; seharusnya hanya dihitung 1x saat user klik clock-in (`captureFaceCrop`) |
| **FC7** | Dual storage: FaceDescriptor vs Employee.face_embedding | `FaceController::register():82-83` | Embedding disimpan di 2 tempat tanpa sinkronisasi. Lapisan lama (`employee.face_embedding`) seharusnya dihapus setelah FaceDescriptor stabil. |
| **FC8** | Old FaceDescriptor tidak di-deactivate saat re-enroll | `FaceController::register()` | `FaceDescriptor::create()` tanpa `where('is_active', false)` update dulu. Multiple active descriptors bisa muncul. |

## MEDIUM (10)

| ID | Issue | Detail |
|:--:|-------|--------|
| **FC9** | `nearestNeighbors` tanpa timeout di pgvector | Query bisa hang jika indeks rusak |
| **FC10** | Empty `catch {}` di 3 tempat (face-registration) | Error siluman — tidak ada logging |
| **FC11** | 6 base64 JPEG capture dikirim ke server | Hanya `captures_count` yang disimpan — 6 gambar full-res base64 terkirim percuma |
| **FC12** | `computeVariance()` math wrong | Variance dibagi `embeddings.length` bukan `embeddings.length * dims` |
| **FC13** | Vite dynamic import di production | `import('face-api.js')` di production bikin code split terpisah |
| **FC14** | No `beforeunload` cleanup di face-registration | Stream kamera tetap jalan saat navigasi |
| **FC15** | `faceapi.euclideanDistance()` tidak dipakai | Service pake cosine distance via pgvector, tapi JS bisa compute client-side untuk real-time feedback |
| **FC16** | Tidak ada max retry untuk model loading | Jika model gagal load, infinite retry tiap 300ms di detection loop |
| **FC17** | No empty state jika camera izin ditolak permanent | Hanya set statusText, tidak ada UI guidance |
| **FC18** | `photo_selfie` column expects base64 string no size limit | Payload bisa sangat besar (~100KB per capture) tanpa validasi |

## LOW (8)

| ID | Issue | Detail |
|:--:|-------|--------|
| **FC19** | No aria-label di video element | Aksesibilitas |
| **FC20** | `captureFrame()` tidak stop detection loop selama capture | Race condition: detection loop hitung descriptor bersamaan dengan capture |
| **FC21** | `modelsLoading` flag tidak direset jika gagal | User stuck di loading state forever |
| **FC22** | No loading indicator untuk `registerFace()` API call | User tidak tahu request sedang diproses |
| **FC23** | Magic number `0.3` dan `0.5` untuk scoreThreshold | Tidak ada konstanta |
| **FC24** | `setTimeout(() => sample(i + 1), 1500)` hardcoded | Tidak ada konstanta untuk interval GPS |
| **FC25** | No validation bahwa captureCount === embeddings.length | Bisa mismatch (1:1 mapping diperlukan) |
| **FC26** | `destroy()` method ada tapi tidak di-trigger otomatis oleh Alpine | Perlu `@cleanup` atau `x-effect` untuk cleanup |

---

# 🔴 Prioritas Eksekusi — Update 1 Juli 2026

Berdasarkan semua temuan di atas + referensi pola dari PasPapan (studi repo: `/home/merger/PasPapan/`).

Setiap fix harus mengacu ke pola PasPapan yang sudah diverifikasi — jangan buat dari nol.

| Tier | ID | Task | Referensi PasPapan | File | Effort |
|:----:|:--:|------|:-------------------|------|:------:|
| **P0** | **F1/FC13** | **Fix face-api.js import** — ganti `import('face-api.js')` di Alpine eval (pasti gagal) dengan static `<script>` tag atau `import` di `app.js` → `window.faceapi` global | PasPapan: `<script src="/assets/js/face-api.min.js">` + model `loadFromUri('/models')`. Tidak pakai dynamic `import()`. | `app.js`, `face-registration.blade.php`, `clock-in.blade.php` | 15 menit |
| **P0** | **F2/FC2** | **Fix `face_crop` → `photo_selfie`** — ganti nama field agar cocok dengan server validation. Ikuti pola PasPapan: simpan foto ke disk (`attendance_photos/`), bukan base64 di DB. | PasPapan: `storeAttendancePhoto()` — `getimagesizefromstring()` validasi MIME, simpan ke `storage/app/attendance_photos/{Y/m/d}/{id}.jpg`, simpan path di kolom `attachment` (JSON). | `clock-in.blade.php`, `AttendanceService.php`, tambah migration `gps_variance_in/out` + `photo_selfie_in` ubah ke `string` path | 30 menit |
| **P0** | **F3/FC1** | **Tambah `gps_variance`** ke ClockInRequest rules + migration kolom DECIMAL(12,8) | PasPapan: `DeviceBarcodeScanRequest` — `'gps_variance' => ['nullable', 'numeric', 'min:0']`. Kolom `gps_variance_in`/`gps_variance_out` DECIMAL(12,8). | `ClockInRequest.php`, migration baru | 10 menit |
| **P0** | **F4/FC3** | **Fix hardcoded `is_mocked: false`** — hapus hardcode, kirim dari hasil deteksi GPS mocking. Ikuti PasPapan: `mock_location_detected` boolean di-risk scoring (score=100) — bukan disimpan di kolom. | PasPapan: tidak ada kolom `is_mocked` di DB. `mock_location_detected` langsung feed ke `AttendanceRiskScorer` → factor score **100** (immediate HIGH risk). | `clock-in.blade.php`, `AttendanceService.php` | 5 menit |
| **P1** | **FC7/FC8** | **Single storage FaceDescriptor** — hapus `employee.face_embedding`, deactivate old descriptor. Ikuti PasPapan: `updateOrCreate` + `unique:user_id` (1 descriptor per user). | PasPapan: `FaceDescriptor::updateOrCreate(['user_id' => $user->id], ['descriptor' => $descriptor])` + `unique:user_id` constraint di migration. | `FaceController.php`, `FaceRecognitionService.php` | 10 menit |
| **P1** | **FC4/FC12** | **Ganti liveness variance → head-turn challenge** Ikuti PasPapan: yaw score dari nose vs eye midpoint. Threshold turn=0.05, recenter=0.12. Required 2 stable frames per step. | PasPapan: `getYawScore(landmarks)` — `(noseTip.x - eyeMidX) / eyeDistance`. Challenge steps: `turn-first-side` → `recenter` → `turn-opposite-side` → `recenter-final`. | `face-registration.blade.php` | 45 menit |
| **P1** | **FC5** | **Implement EAR blink detection** untuk liveness tambahan. Ikuti PasPapan: hitung EAR tiap frame, deteksi pola open→closed→open. | PasPapan: `eyeAspectRatio(landmarks)` — `(p1-p5 + p2-p4) / (2 * p0-p3)` untuk kedua mata. Threshold closed=0.2, open=0.25. | `clock-in.blade.php` | 15 menit |
| **P1** | **C1** | SQL LIKE escape — `LIKE` wildcard `%` + `_` tidak di-escape di search. | Gunakan prepared statement + `addslashes()` atau str_replace wildcard chars. | `EmployeeController`, `AssetController`, `EmbeddingService` | 10 menit |
| **P1** | **C7** | **Buat ApprovalPolicy** — extract `view`, `approve`, `reject` dari manual `approver_id` check. | Pattern: `ApprovalPolicy` → `$user->can('approve_*_l1')` | `app/Policies/ApprovalPolicy.php` | 15 menit |
| **P1** | **H10** | **Ganti face descriptor ke geometry-based** (129 float) — lebih ringan, tidak butuh `faceRecognitionNet` (5MB). | PasPapan: `buildFaceGeometryDescriptor()` — normalize by eye distance, rotate by roll, exclude 4 points, return `[2] + 64 (x,y) pairs`. | `face-registration.blade.php`, `FaceRecognitionService.php` | 30 menit |
| **P2** | **R1-R5** | **Role-based UI split** — route name detection `request()->routeIs('admin.*')` → body class `admin-ui`/`user-ui`, split sidebar per role. | PasPapan: `$isAdminRoute = request()->routeIs('admin.*')` di `layouts/app.blade.php:3`. Body class `$isAdminRoute ? 'admin-ui' : 'user-ui'`. Routes split: `routes/web/admin.php` (prefix `admin`, middleware `admin`), `routes/web/user.php`. | Layout files, routes files | 3 jam |
| **P2** | **H1** | **Route closures → controller** — 7 route files pakai closure, prevent `route:cache`. | Ganti `Route::get('/', fn() => view('...'))` → invokable controller. | 7 route files | 30 menit |
| **P2** | **FC6** | **Pindah descriptor compute** ke saat clock-in (1x), bukan tiap frame (300ms). Simpan `lastDescriptor` hanya saat user klik clock-in. | PasPapan: descriptor computed only in `faceVerificationModal().verify()` — sekali saat user trigger verify. | `clock-in.blade.php` | 10 menit |
| **P2** | **FC11** | **Hapus `captures` dari payload** — 6 base64 JPEG dikirim tapi hanya `captures_count` disimpan. | Hapus field `captures[]` dari `RegisterFaceRequest` dan `face-registration.blade.js:146`. | `FaceController.php`, `RegisterFaceRequest.php`, `face-registration.blade.php` | 5 menit |
| **P2** | **C3** | **Fix PII leak EmployeeResource** — pastikan `#[Hidden]` attribute di Employee model mencakup semua field sensitif. | Sudah aman (cek audit), hanya perlu verifikasi tidak ada resource manual yang expose PII. | `EmployeeResource.php`, `EmployeePiiResource.php` | 5 menit |
| **P2** | **APP_DEBUG** | **Set `APP_DEBUG=false`** di production — stack trace + env var leak | `.env` | 1 menit |
| **P2** | **DB_SSLMODE** | **Set `DB_SSLMODE=require`** — koneksi DB plaintext di production | `.env` | 1 menit |
| **P2** | **SESSION_SECURE** | **Set `SESSION_SECURE_COOKIE=true`** — session cookie via HTTP | `.env` | 1 menit |
| **P2** | **H2** | **Tambah `verified` middleware** ke `profile.edit` route | `routes/settings.php` | 1 menit |
| **P3** | **Security headers** | **Buat `EnsureSecurityHeaders` middleware** — CSP, HSTS, X-Frame-Options, Permissions-Policy, Referrer-Policy. | PasPapan: dedicated middleware class → `$response->headers->set(...)`. CSP: `default-src 'self'`, `frame-ancestors 'self'`, `form-action 'self'`, `base-uri 'self'`. | Middleware baru + `bootstrap/app.php` | 30 menit |
| **P3** | **H3-H9, M1-M10, L1-L8** | Perbaikan kualitas kode lainnya | Various | Variatif |

# UI Adaptation Plan — HRConnect

Berdasarkan analisis 5 repositori referensi. Prioritas P1→P3.

---

## P1 — Selesai ✅

### 1. Page Shell Component ✅
**Sumber**: PasPapan `admin/page-shell.blade.php`
**Pola**: Container halaman dengan slot title, actions, toolbar, content.
```blade
<x-page-shell title="Daftar Karyawan" description="...">
  <x-slot name="actions">
    <x-button>Tambah</x-button>
  </x-slot>
  <x-slot name="toolbar">
    <!-- search + filter -->
  </x-slot>
  <!-- konten utama -->
</x-page-shell>
```

### 2. Form Components ✅
**Sumber**: PasPapan `forms/` (input, select, textarea, label, input-error, checkbox, radio, switch)
**Pola**: Blade component per tipe input dengan dukungan `wire:model`, validasi otomatis via `$errors`.
```blade
<x-forms.input wire:model="name" label="Nama" required />
<x-forms.select wire:model="department" label="Departemen" :options="$departments" />
<x-forms.input-error name="name" />
```

### 3. Status Badge ✅
**Sumber**: PasPapan `admin/status-badge.blade.php`
**Pola**: Badge pill/rectangle dengan tone: neutral, info, success, warning, danger.
```blade
<x-badge tone="success">Disetujui</x-badge>
<x-badge tone="warning">Menunggu</x-badge>
```

### 4. Empty State ✅
**Sumber**: PasPapan `admin/empty-state.blade.php`
**Pola**: Halaman kosong dengan ikon + title + deskripsi + aksi.
```blade
<x-empty-state icon="o-inbox" title="Belum ada data" description="...">
  <x-button>Tambah Data</x-button>
</x-empty-state>
```

### 5. SweetAlert2 Toast ✅
**Sumber**: PasPapan `app.js` → `PasPapanAlert.toast()`
**Pola**: Livewire event listener → SweetAlert2 toast (bottom-right, 3.2s).
```
PHP:  $this->dispatch('notify', type: 'success', message: '...')
JS:   Livewire.on('notify', ...) → Swal.fire({ toast: true, ... })
```

---

## P2 — Selesai ✅

### 6. Modal System ✅
**Sumber**: PasPapan `overlays/modal.blade.php`
**Pola**: `x-teleport="body"` + `x-trap.inert.noscroll` + backdrop + transisi.
```blade
<x-modal wire:model="showModal" max-width="lg">
  <x-slot name="title">Judul</x-slot>
  <x-slot name="content">...</x-slot>
  <x-slot name="footer">
    <x-button wire:click="save">Simpan</x-button>
  </x-slot>
</x-modal>
```
Varian: `dialog-modal` (form), `confirmation-modal` (warning icon + confirm/cancel).

### 7. Flatpickr Date Picker ✅
**Sumber**: PasPapan `app.js` — `data-ui-picker` attribute + `initUiPickers()`
**Pola**: Attribut `data-ui-picker="date|time|datetime|date-range"` pada input → auto-init Flatpickr via MutationObserver.
```blade
<x-forms.input data-ui-picker="date" wire:model="tanggal" label="Tanggal" />
```

### 8. Responsive Table ✅
**Sumber**: PasPapan admin views (inline)
**Pola**: Desktop `<table>` (hidden on mobile) + Mobile card grid (hidden on desktop).
```blade
<!-- Desktop -->
<table class="hidden md:table">
  <thead>...</thead>
  <tbody>...</tbody>
</table>

<!-- Mobile -->
<div class="md:hidden space-y-3">
  @foreach($items as $item)
    <div class="card">...</div>
  @endforeach
</div>
```

### 9. Rp Formatting + Color-coded Financial ✅
**Sumber**: Quanta HRIS
**Pola**: Helper `Rp` formatting hijau (income), merah (deductions), amber (adjustments).
```blade
<span class="text-success">{{ Number::currency($gaji, 'IDR') }}</span>
<span class="text-error">{{ Number::currency($potongan, 'IDR') }}</span>
```

---

## P3 — Selesai ✅
### 10. Queue Progress Bar ✅ (`ImportProgress` model + migration)

### 11. Payroll Status Workflow ✅ (udah ada — DRAFT→PUBLISHED→PAID + badge)
**Sumber**: Quanta HRIS — Draf→Diajukan→Diverifikasi→Disetujui→Ditolak
**Pola**: Badge + icon per status dengan warna berbeda.

### 12. Salary Calculator ✅ (`SalaryCalculator` Livewire + formula PPh21/BPJS/Alfa port dari Quanta)

### 13. Dashboard Stat Cards ✅
**Sumber**: Laravel SmartHR `dash-widget` + HRConnect existing patterns
**Pola**: Card grid (2-4 column) dengan icon + angka + label per metrik.

### 14. SweetAlert2 Delete Confirmation ✅ (`wire:confirm` + `HRConnectAlert.confirm()` interceptor)
**Sumber**: PasPapan `installSweetAlertConfirmations()` — intercept `wire:confirm` → SweetAlert2
**Pola**: `wire:confirm="Hapus items ini?"` → otomatis intercept + SweetAlert2 modal via `installSweetAlertConfirmations()` + MutationObserver.

### 15. Three-dot Action Menu ✅ (`x-dropdown-menu` component, inline Alpine per row)
**Sumber**: PasPapan `x-data="{ openOptions: false }"` + `@click.stop` + `@click.away`
**Pola**: `x-data="{ open: false }"` + `@click.stop="open = !open"` + `@click.away="open = false"` + `x-transition` dropdown panel. Reusable `x-dropdown-menu` component wrapping this pattern.

### 16. RAG Chat Enhancement ✅ (udah ada — welcome screen + suggestion buttons)
**Sumber**: ship-ai-with-laravel
**Pola**: Welcome screen + suggestion buttons untuk `KnowledgeBaseChat.php`.

---

## Konvensi Naming

| Jenis | Prefix | Contoh |
|-------|--------|--------|
| Blade component | `x-` prefix | `x-page-shell`, `x-badge`, `x-empty-state` |
| Form component | `x-forms.*` | `x-forms.input`, `x-forms.select` |
| Modal component | `x-modal` (with slot variants) | `x-modal`, `x-modal.confirmation` |
| Livewire event | `notify`, `close-modal` | `$this->dispatch('notify', ...)` |
| CSS classes | MD3 tokens from `app.css` `@theme` | `bg-canvas`, `text-ink`, `rounded-xl` |

## Design Constraint (from AGENTS.md)
- **Warna**: Hanya pakai `bg-canvas`/`text-ink`/`rounded-xl` dari `@theme` di `app.css`
- **Font**: Rubik 500 (display) + Inter (body) — jangan pakai font lain
- **Layout**: `x-layouts::app.sidebar`
- **Larangan**: jangan copy CSS PasPapan (green #57944a, cream #fffaf0)
- **Status enum**: 16 enum Status/Indicator punya metode `color()` → MD3 semantic (success/warning/error/info)

---

# Code Fix Plan — Audit Findings

Hasil audit kode (Juni 2026). **15 error + 3 warning** ditemukan. Task terkecil agar mudah dikerjakan.

## 🔴 PHASE 1 — Application Code Crash Fixes

### Task 1.1 — Fix Blade typo
- **File:** `resources/views/attendance/clock-in.blade.php`
- **Baris:** 1
- **Sekarang:** `op<x-layouts::app.sidebar>`
- **Jadi:** `<x-layouts::app.sidebar>`

### Task 1.2 — Fix `Route::livewire()` → settings/profile
- **File:** `routes/settings.php`
- **Baris:** 9
- **Sekarang:** `Route::livewire('settings/profile', 'pages::settings.profile')`
- **Jadi:** `Route::get('settings/profile', \App\Livewire\Pages\Settings\Profile::class)->name('profile.edit');`
- (atau sesuaikan dengan nama class Livewire yg ada)

### Task 1.3 — Fix `Route::livewire()` → settings/appearance
- **File:** `routes/settings.php`
- **Baris:** 13
- **Sekarang:** `Route::livewire('settings/appearance', 'pages::settings.appearance')`
- **Jadi:** `Route::get('settings/appearance', \App\Livewire\Pages\Settings\Appearance::class)->name('appearance.edit');`

### Task 1.4 — Fix `Route::livewire()` → settings/security
- **File:** `routes/settings.php`
- **Baris:** 15
- **Sekarang:** `Route::livewire('settings/security', 'pages::settings.security')`
- **Jadi:** `Route::get('settings/security', \App\Livewire\Pages\Settings\Security::class)->name('security.edit');`

### Task 1.5 — Fix `use Pdo\Mysql;`
- **File:** `config/database.php`
- **Baris:** 4, 63, 83
- **Sekarang:** `use Pdo\Mysql;` (tidak ada di PHP < 8.5) + `Mysql::ATTR_SSL_CA`
- **Jadi:** Hapus `use Pdo\Mysql;`, ganti `Mysql::ATTR_SSL_CA` → `PDO::MYSQL_ATTR_SSL_CA` (tersedia di semua versi PHP)

### Task 1.6 — Tambah missing import
- **File:** `routes/api.php`
- **Baris:** ~58
- **Sekarang:** `[EmailVerificationController::class, 'verify']` tanpa import
- **Jadi:** Tambah `use App\Http\Controllers\Api\EmailVerificationController;` setelah `use App\Http\Controllers\Api\ReimbursementController;` (baris 15)

### Task 1.7 — Hapus import tidak terpakai
- **File:** `app/Services/GeofenceService.php`
- **Baris:** 11
- **Sekarang:** `use Torann\GeoIP\GeoIP;` (tidak ada di composer.json)
- **Jadi:** Hapus baris 11 (import tidak dipakai langsung — cuma di `class_exists()`)

### Task 1.8 — Fix event name toast
- **File:** `resources/js/app.js`
- **Baris:** 88
- **Sekarang:** `Livewire.on('notify', ...)`
- **Jadi:** `Livewire.on('toast', ...)` (karena semua view dispatch `Livewire.dispatch('toast', ...)`)

### Task 1.9 — Fix property name toast handler
- **File:** `resources/js/app.js`
- **Baris:** 22-23
- **Sekarang:** `data.type`, `data.message`
- **Jadi:** `data.variant`, `data.text` (karena view kirim `{ variant: ..., text: ... }`)

## 🔴 PHASE 2 — Test Suite Error Fixes

### Task 2.1 — Guard duplicate function
- **File:** `tests/Unit/Phase3BugFixesTest.php`
- **Baris:** 22
- **Sekarang:** `function makeBranch(...)` tanpa guard
- **Jadi:** Bungkus dengan `if (! function_exists('makeBranch')) { function makeBranch(...) { ... } }`

### Task 2.2 — Fix hardcoded employee_id
- **File:** `tests/Feature/Api/OvertimeProofTest.php`
- **Baris:** 16
- **Sekarang:** `'employee_id' => 1`
- **Jadi:** Ganti dengan ID dari employee yg dibuat di `beforeEach()`, atau buat Employee factory dulu

### Task 2.3 — Tambah FK di PayrollProofTest
- **File:** `tests/Feature/Api/PayrollProofTest.php`
- **Fungsi:** `makePayrollEmployee()`
- **Tambah:** `'company_id' => $this->companyId, 'branch_id' => $this->branchId, 'department_id' => $this->deptId, 'position_id' => $this->positionId, 'user_id' => ...` (ambil dari user yg dibuat)

### Task 2.4 — Tambah FK di EmployeeProofTest
- **File:** `tests/Feature/Api/EmployeeProofTest.php`
- **Fungsi:** `makeProofEmployee()`
- **Tambah:** Same FK fields seperti Task 2.3

### Task 2.5 — Tambah FK di ReimbursementProofTest
- **File:** `tests/Feature/Api/ReimbursementProofTest.php`
- **Fungsi:** `makeReimEmployee()`
- **Tambah:** Same FK fields seperti Task 2.3

### Task 2.6 — Tambah explicit imports
- **File:** `tests/Feature/Api/AuthProofTest.php`
- **Tambah:** `use Illuminate\Support\Facades\Hash;` dan `use Illuminate\Support\Str;`
- (Cache sudah ada import-nya, Hash dan Str hanya pakai alias global)

## 🟡 PHASE 3 — Test Warning (opsional)

### Task 3.1 — Ganti hardcoded ID
- **File:** `tests/Feature/Services/PayslipPdfServiceTest.php`
- **Baris:** 24-60
- **Sekarang:** `'company_id' => 1` hardcode
- **Jadi:** Pakai `DB::table(...)->insertGetId(...)` atau factory

### Task 3.2 — Restore cache singleton
- **File:** `tests/Feature/Cache/CacheIntegrationTest.php`
- **Baris:** 13-18
- **Sekarang:** Overwrite `cache` singleton global
- **Jadi:** Restore ke instance original di `afterEach()`

## ✅ PHASE 4 — Verifikasi

| Task | Perintah |
|------|----------|
| 4.1 Lint check | `composer lint:check` |
| 4.2 PHPStan | `vendor/bin/phpstan analyse` |
| 4.3 Run test suite | `php artisan test --compact` |
| 4.4 Run PG tests | `composer test:pgsql` |

---

# UX Porting Plan — PasPapan → HRConnect

Berdasarkan analisis kode PasPapan (58 Blade components, 1.642 baris app.js, 2.700+ baris CSS).

**Sumber referensi (wajib):** Semua Blade/Livewire component harus merujuk ke repo clone di `/home/merger/`:
- **PasPapan** (`/home/merger/PasPapan/`) — components, layout, Alpine patterns, face/scan UX, approval, termination
- **Quanta HRIS** (`/home/merger/quanta-hris-laravel/`) — payroll components, status badge, financial formatting
- **ship-ai-with-laravel** (`/home/merger/RAG-repo/ship-ai-with-laravel/`) — RAG chat SSE streaming
- **Laravel SmartHR** (`/home/merger/laravel-smarthr/`) — dashboard widgets, UI component reference

**Aturan porting:**
- Jangan copy CSS/colors PasPapan (green #57944a, cream #fffaf0) — pakai MD3 tokens HRConnect
- Jangan copy Heroicons — konversi ke Material Symbols
- Jangan copy BEM classes — konversi ke Tailwind utility classes
- Tes di semua layout (sidebar admin + mobile)

**Urutan prioritas development (business logic):**
| Prioritas | Role | Halaman Utama |
|-----------|------|---------------|
| **P1** | **HR-Manager** | Master data (branches, departments, positions, shifts, holidays), Employee CRUD + lifecycle, Payroll config (BPJS, tax), RAG docs manage, Reports, Company settings |
| **P2** | **Finance** | Payroll execution + adjustment, Reimbursement payment, Loan management, Asset management, Tax reports |
| **P3** | **Manager** | Approval L1 dashboard, Team attendance monitoring |
| **P4** | **Employee** | Clock-in/out, apply cuti/lembur/reimbursement, view payslip, chat RAG |

## 🔴 PHASE U1 — Foundation Components (HIGH, port segera)

### Task U1.1 — Modal system upgrade
- **Sumber:** PasPapan `components/overlays/modal.blade.php` (47L)
- **Port ke:** `resources/views/components/overlays/modal.blade.php` (HRConnect punya `x-modal`)
- **Tambah:**
  - `x-teleport` ke body (hindari stacking context issue)
  - `x-trap.inert.noscroll` untuk focus trap (WCAG)
  - `env(safe-area-inset-*)` padding di mobile
  - Reinit datepicker via `x-effect` setelah modal terbuka
  - Ukuran: sm, md, lg, xl, 2xl, full
- **Catatan:** HRConnect punya `x-modal`, `x-form-modal`, `x-confirm-modal` — upgrade base component dulu, turunan akan ikut

### Task U1.2 — Icon Button component
- **Sumber:** PasPapan `components/actions/icon-button.blade.php` (33L)
- **Buat:** `resources/views/components/actions/icon-button.blade.php`
- **Props:** `icon` (Material Symbol name), `label` (aria-label), `variant` (neutral/primary/success/warning/danger), `href` (opsional, jadi <a>), `wire:click` support
- **Output:** `<button class="h-10 w-10 inline-flex items-center justify-center rounded-xl ...">` dengan aria-label

### Task U1.3 — Switch/Toggle component
- **Sumber:** PasPapan `components/forms/switch.blade.php` (40L)
- **Buat:** `resources/views/components/forms/switch.blade.php`
- **Props:** `wire:model.live`, `size` (sm/md/lg), `label`, `disabled`, `onValue`/`offValue`
- **Pattern:** `<button role="switch" aria-checked="...">` — bukan `<input type="checkbox">`

### Task U1.4 — TomSelect integration
- **Sumber:** PasPapan `components/forms/tom-select.blade.php` (283L) + Alpine data `tomSelectInput` (200L)
- **Buat:** `resources/views/components/forms/tom-select.blade.php`
- **Tambah dependensi:** `tom-select` npm package
- **Pattern:** Alpine x-data wrapper, Livewire entangle, retry mechanism 20x, clean up on destroy
- **Catatan:** Butuh port JS Alpine data + CSS + npm install. Prioritas tinggi karena select-heavy HR forms.

### Task U1.5 — Page Shell dengan toolbar slot
- **Sumber:** PasPapan `components/admin/page-shell.blade.php` (49L)
- **Port ke:** `resources/views/components/page-shell.blade.php`
- **Tambah:**
  - `toolbar` slot di antara header dan konten (border separator, padding)
  - `descriptionId` untuk aria-describedby
  - `containerClass` untuk fleksibilitas layout
- **Catatan:** HRConnect punya `x-page-shell` — upgrade, bukan buat ulang

### Task U1.6 — Form Section component
- **Sumber:** PasPapan `components/sections/form-section.blade.php` (39L)
- **Buat:** `resources/views/components/sections/form-section.blade.php`
- **Structure:** Card dengan icon header (opsional), title, description, 6-column grid body (`sm:grid-cols-6`), footer dengan actions reversed di mobile
- **Pattern:** `<section aria-labelledby="...">` → `<form wire:submit="...">`

### Task U1.7 — Password Confirmation overlay
- **Sumber:** PasPapan `components/overlays/confirms-password.blade.php` (46L)
- **Buat:** `resources/views/components/overlays/confirms-password.blade.php`
- **Pattern:** Wrapper component — intercept click → show password modal via Livewire → konfirmasi → dispatch custom event
- **Catatan:** Kritis untuk payroll lock, termination, dan action sensitif lain

### Task U1.8 — Action Section component
- **Sumber:** PasPapan `components/sections/action-section.blade.php` (33L)
- **Buat:** `resources/views/components/sections/action-section.blade.php`
- **Pattern:** Mirip form-section tapi tanpa form wrapper. Icon + title + content + actions. Untuk halaman settings/profile.

### Task U1.9 — Theme Toggle + Dark Mode store
- **Sumber:** PasPapan layout inline script + `components/navigation/theme-toggle.blade.php` (13L)
- **Buat:** `resources/views/components/navigation/theme-toggle.blade.php`
- **Tambah di layout:** Alpine store `darkMode` — `on`/`init()`/`toggle()` — localStorage + prefers-color-scheme
- **Pattern:** `<button @click="$store.darkMode.toggle()" :aria-pressed="$store.darkMode.on">`
- **Catatan:** CSS dark mode sudah ada di `app.css` (`.dark, .dark *`) — tinggal toggle class `<html>`

## 🟡 PHASE U2 — Forms & Feedback (MEDIUM)

### Task U2.1 — Form client-side validation
- **Sumber:** PasPapan `app.js` `validateUiForm` (~200L)
- **Buat:** `resources/js/validation.js`
- **Pattern:** Intercept form submit → Constraint Validation API → show error inline → scroll to first error → TomSelect-aware
- **Integrasi:** Inisialisasi via `initUiValidation()` di app.js, MutationObserver untuk dynamic forms

### Task U2.2 — Flatpickr enhanced integration
- **Sumber:** PasPapan `app.js` `initUiPickers` (200L) + CSS (630L)
- **Port ke:** `resources/js/datepicker.js`
- **Tambah:** Range date support, static positioning di modal, custom styling sesuai MD3, keyboard guard (baca: readonly), calendar normalization
- **Catatan:** Jangan copy CSS PasPapan — buat styling Flatpickr sesuai MD3 HRConnect

### Task U2.3 — Alert component (inline messages)
- **Sumber:** PasPapan `components/admin/alert.blade.php` (17L)
- **Buat:** `resources/views/components/alert.blade.php`
- **Props:** `tone` (success/warning/danger/info), `dismissible` (opsional)
- **Pattern:** Rounded-xl border + background sesuai tone

### Task U2.4 — Validation Errors summary
- **Sumber:** PasPapan `components/forms/validation-errors.blade.php` (11L)
- **Buat:** `resources/views/components/forms/validation-errors.blade.php`
- **Pattern:** Error summary block dengan red border, role="alert", daftar error

### Task U2.5 — Empty State framed variant
- **Sumber:** PasPapan `components/admin/empty-state.blade.php` (32L)
- **Upgrade:** `resources/views/components/empty-state.blade.php`
- **Tambah:** `framed` prop (border+background), `icon` slot instead of string

### Task U2.6 — Status Badge tones upgrade
- **Sumber:** PasPapan `components/admin/status-badge.blade.php` (24L)
- **Upgrade:** `resources/views/components/status-badge.blade.php`
- **Tambah:** Tones `primary`, `accent` — ring-based (`ring-1 ring-inset`) instead of background

### Task U2.7 — File Input component
- **Sumber:** PasPapan `components/forms/file-input.blade.php` (38L)
- **Buat:** `resources/views/components/forms/file-input.blade.php`
- **Pattern:** Alpine x-data showing selected filename, hidden `<input type=file>`, styled label button

### Task U2.8 — Checkbox & Radio components
- **Sumber:** PasPapan `components/forms/checkbox.blade.php` (1L), `radio.blade.php` (1L)
- **Buat:** `resources/views/components/forms/checkbox.blade.php`, `forms/radio.blade.php`
- **Pattern:** Styled input element dengan Tailwind + MD3 tokens

### Task U2.9 — WCAG touch targets utility
- **Sumber:** PasPapan `app.css` `.wcag-touch-target` (4L)
- **Tambah di:** `resources/css/app.css` `@layer components`
- **Pattern:** `.wcag-touch-target { min-height: 2.75rem; min-width: 2.75rem; }`
- **Integrasi:** Apply ke semua button kecil dan icon button

### Task U2.10 — Toast styling upgrade
- **Sumber:** PasPapan `app.js` `PasPapanAlert.toast()` (42L)
- **Upgrade:** `resources/js/app.js` `window.HRConnectAlert`
- **Tambah:** Dark mode classes, rounded-xl styling, progress bar, custom button classes MD3
- **Fix event name:** `Livewire.on('notify', ...)` → `Livewire.on('toast', ...)` (dari Phase 1 Task 1.8)

### Task U2.11 — Page Tools grid component
- **Sumber:** PasPapan `components/admin/page-tools.blade.php` (35L)
- **Buat:** `resources/views/components/page-tools.blade.php`
- **Pattern:** Toolbar section dengan title, description, summary slot, actions slot, grid layout

### Task U2.12 — Dropdown upgrade (contentClasses, flexibilitas)
- **Sumber:** PasPapan `components/navigation/dropdown.blade.php` (49L)
- **Upgrade:** `resources/views/components/dropdown-menu.blade.php`
- **Tambah:** `contentClasses`, `width` prop, `align` (left/right/top)

## 🔵 PHASE U3 — Native User Components (LOW, stretch)

### Task U3.1 — Native Text/Date Fields
- Port icon-enhanced form fields untuk mobile UI

### Task U3.2 — User Page Header
- Port user-facing page header dengan back button

### Task U3.3 — Section Border component
- Port horizontal divider sebagai komponen

### Task U3.4 — Profile Photo Cropper
- Port canvas-based crop + upload via Livewire

### Task U3.5 — Map/Leaflet integration
- Port location picker untuk geofence

## ✅ PHASE U4 — Rollout & Verifikasi

| Task | Perintah | Verifikasi |
|------|----------|------------|
| U4.1 | `npm run build` | Tidak ada error Vite |
| U4.2 | `php artisan test --compact` | Semua test pass |
| U4.3 | `composer lint:check` | Pint lulus |
| U4.4 | Buka halaman settings/profile | Modal, form section, dan toggle berfungsi |
| U4.5 | Buka modal di mobile | Focus trap, teleport, safe-area berfungsi |
| U4.6 | Test TomSelect di form | Select dengan search berfungsi |
| U4.7 | Test dark mode toggle | Class `.dark` bertambah/hilang di `<html>` |

---

# 🔮 Pekerjaan Tersisa — Dari AGENTS.md

## 🔴 Phase 3 — Test Warning (opsional)

### Task 3.1 — Ganti hardcoded ID
- **File:** `tests/Feature/Services/PayslipPdfServiceTest.php`
- **Baris:** 24-60
- **Sekarang:** `'company_id' => 1` hardcode
- **Jadi:** Pakai `DB::table(...)->insertGetId(...)` atau factory

### Task 3.2 — Restore cache singleton
- **File:** `tests/Feature/Cache/CacheIntegrationTest.php`
- **Baris:** 13-18
- **Sekarang:** Overwrite `cache` singleton global
- **Jadi:** Restore ke instance original di `afterEach()`

## 🟠 Theme Split — App vs Landing

**app.css masih cream (#fffaf0), harus putih (#ffffff) untuk halaman HR.**

### Task T1 — Finalisasi canvas split
- **File:** `resources/css/app.css`
- **Baris:** 21 (`--color-canvas: #fffaf0`)
- **Sekarang:** `#fffaf0` (cream)
- **Jadi:** `#ffffff` (putih) untuk halaman HR
- **Catatan:** `welcome.blade.php` sudah punya `.landing-theme` class — pastikan landing tetap cream via class override

### Task T2 — Verifikasi landing
- Buka halaman welcome → masih cream
- Buka halaman HR (dashboard, employees) → putih
- Cek dark mode tidak broken

## 🟠 Broadcast Notifications — Channel Setup

### Task B1 — Install Pusher/laravel-websockets
- **File:** `composer.json` + `.env`
- **Tambah:** `pusher/pusher-php-server` atau `beyondcode/laravel-websockets`
- **Config:** `config/broadcasting.php`, `config/websockets.php`

### Task B2 — Pasang event broadcasting
- **File:** 7 notification classes
- **Tambah:** `ShouldBroadcast` interface + `broadcastOn()` / `broadcastAs()`
- **Channel:** Private channel per user (`App.Models.User.{id}`)

### Task B3 — Echo + Laravel Echo setup
- **File:** `resources/js/app.js`
- **Tambah:** `laravel-echo` + Pusher connector
- **Integrasi:** Livewire presence channel listener

## ✅ PostgreSQL Tests (Selesai)

### Task P1 ✅ — Tambah PG test coverage
- **File:** `tests/Integration/Postgres/PostgresEnvironmentTest.php`
- **Hasil:** 41 test (pgvector 768D/128D, pg_trgm, pgcrypto, EmbeddingService, PgVector cast, CipherSweet, jsonb, HNSW index, check constraints, payroll upsert)

### Task P2 ✅ — CI PG test stabilkan
- **File:** `.github/workflows/tests.yml`
- **Hasil:** `composer install` → create extensions → `pest --configuration=phpunit.pgsql.xml`. Hapus `.env.testing.pgsql.example`, `APP_KEY` langsung di `phpunit.pgsql.xml`.

## 🟠 Clock-out PIN — Streak Check

### Task C1 — Fix streak validation bypass
- **File:** `app/Http/Controllers/Api/AttendanceController.php` (clock-out method)
- **Sekarang:** PIN clock-out bypass streak check
- **Jadi:** Tambah validasi streak sebelum PIN verification
- **Test:** Tambah test case streak → clock-out ditolak

---

# 🔴 Temuan Audit — Juni 2026

## 🔴 Design System Compliance (D1-D4)

### Task D1 ✅ — TomSelect hardcoded colors
- **File:** `resources/views/components/forms/tom-select.blade.php`
- **Issue:** 20 hardcoded hex colors di `<style>` block
- **Jadi:** Ganti semua dengan CSS variables (`var(--color-canvas)`, `var(--color-outline-variant)`, `var(--color-ink)`, `var(--color-on-background)`, dll)
- **Status:** ✅ Selesai

### Task D2 ✅ — Hover arbitrary colors
- **Files:** `resources/views/attendance/clock-in.blade.php:272`, `resources/views/attendance/index.blade.php:94,106`, `resources/views/employee/profile/face-registration.blade.php:198,256,281`
- **Issue:** `hover:bg-[#1f1f1f]`, `hover:bg-[#d48a0a]` — hardcoded hex
- **Jadi:** Ganti dengan MD3 tokens (`hover:bg-surface-container`, `hover:bg-warning-container`)
- **Status:** ✅ Selesai (1 remnant di attendance/index.blade.php:94 — lihat J2)

### Task D3 ✅ — Legacy Tailwind color classes
- **Files:** `resources/views/components/status-badge.blade.php:14` (`bg-purple-500/10 text-purple-700`), `resources/views/livewire/quick-actions.blade.php:23` (`bg-blue-100 text-blue-700`)
- **Jadi:** Ganti dengan MD3 semantic tokens (`bg-surface-dim text-on-surface-variant`, `bg-info/10 text-info`)
- **Status:** ✅ Selesai

### Task D4 ✅ — Arbitrary size values
- **Files:** Multiple views (`text-[11px]`, `text-[0.68rem]`, `tracking-[0.24em]`)
- **Jadi:** Ganti dengan Tailwind utility (`text-xs`, `text-sm`, `tracking-wide`)
- **Status:** ✅ Selesai

## 🔴 Test Coverage Gap (T1-T6)

### Task T1 — Test AssetService + AssetController
- **Files:** `app/Services/AssetService.php`, `app/Http/Controllers/Api/AssetController.php`
- **Buat:** `tests/Unit/Services/AssetServiceTest.php` + `tests/Feature/Api/AssetProofTest.php`
- **Cakupan:** CRUD, handover, return, authorization

### Task T2 — Test LoanService + LoanController
- **Files:** `app/Services/LoanService.php`, `app/Http/Controllers/Api/LoanController.php`
- **Buat:** `tests/Unit/Services/LoanServiceTest.php` + `tests/Feature/Api/LoanProofTest.php`
- **Cakupan:** CRUD, approval workflow, cicilan kalkulasi

### Task T3 ✅ — Test BpjsService
- **File:** `app/Services/Payroll/BpjsService.php`
- **Buat:** `tests/Unit/Services/Payroll/BpjsServiceTest.php`
- **Cakupan:** Kesehatan 1%, JHT 2%, JP 1%, batas atas, edge cases
- **Status:** ✅ 7 test, 39 assertions — Selesai

### Task T4 — Test DynamicBarcodeTokenService
- **File:** `app/Services/DynamicBarcodeTokenService.php`
- **Buat:** `tests/Unit/Services/DynamicBarcodeTokenServiceTest.php`
- **Cakupan:** HMAC-SHA256, nonce, TTL jitter, anti-replay

### Task T5 ✅ — Test AttendanceRiskScorer
- **File:** `app/Services/AttendanceRiskScorer.php`
- **Buat:** `tests/Unit/Services/AttendanceRiskScorerTest.php`
- **Cakupan:** 14 faktor risk scoring, score range 0-100, threshold
- **Status:** ✅ 31 test, 46 assertions — Selesai

### Task T6 — Test master data controllers
- **Files:** `DepartmentController`, `PositionController`, `CompanyController`, `BranchController`
- **Buat:** `tests/Feature/Api/MasterDataProofTest.php`
- **Cakupan:** index, show, authorization, 404 handling

## 🟠 PHPStan & Code Quality (S1-S2)

### Task S1 — Bersihkan stale phpstan-baseline
- **File:** `phpstan-baseline.neon`
- **Issue:** 7 entries referencing deleted `app/Notifications/Leave*` files
- **Jadi:** Hapus 7 entry atau set `reportUnmatchedIgnoredErrors: false`

### Task S2 — update api-contracts.md
- **File:** `docs/api/api-contracts.md`
- **Issue:** Bilang 47 endpoint, realitanya 80
- **Jadi:** Update jumlah endpoint atau ganti ke referensi `docs/api/api.json` saja

## 🟠 API Consistency (A1-A2)

### Task A1 — Buat ApprovalPolicy
- **File:** `app/Http/Controllers/Api/ApprovalController.php`
- **Issue:** Satu-satunya controller tanpa Policy (manual `approver_id` check)
- **Buat:** `app/Policies/ApprovalPolicy.php`
- **Jadi:** Extract `view`, `approve`, `reject` ke Policy, pakai `$this->authorize()` di controller

### Task A2 — Pakai orphaned API Resources
- **Files:** `ApprovalController`, `AttendanceController`, `PayrollController`, `KnowledgeBaseController`
- **Issue:** `ApprovalResource`, `AttendanceResource`, `PayrollResource`, `KnowledgeBaseResource` exist tapi controller pake inline JSON
- **Jadi:** Refactor endpoint return value pake Resource classes

## 🟢 Theme Split — App vs Landing (Selesai)

### Task T1 ✅ — Finalisasi canvas split
### Task T2 ✅ — Verifikasi landing

## 🟢 PostgreSQL Tests (Selesai)

### Task P1 ✅ — Tambah PG test coverage
### Task P2 ✅ — CI PG test stabilkan

## 🟢 Payroll Alpine Fix (Selesai)

### Task J-Payroll ✅ — Ekstrak payrollIndex ke JS file
- **File:** `resources/views/payroll/index.blade.php` → `resources/js/payroll-index.js`
- **Issue:** `loading`, `formatCurrency`, `payrolls` undefined karena inline script tidak jalan dengan `wire:navigate`
- **Fix:** Ekstrak ke file JS terpisah, register via `app.js` di `alpine:init`
- **Status:** ✅ Build + lint pass

---

# Prioritaskan Sesuai Role

| Prioritas | Role | Fokus |
|-----------|------|-------|
| **P1** | **HR-Manager** | Alpine inline migration J1, Hover remnant J2 |
| **P2** | **Finance** | Test Asset (T1), Loan (T2), ApprovalPolicy (A1), API Resources (A2) |
| **P3** | **Manager** | Master data tests (T6), PHPStan baseline S1, Broadcast (B1-B3) |
| **P4** | **Employee** | DynamicBarcodeToken tests (T4), Clock-out streak (C1), Docs update (S2) |

---

# 🔴 Temuan Audit — Sesi 2 (30 Juni 2026)

## 🔴 JS Alpine wire:navigate (J1-J2)

### Task J1 — Ekstrak 14 inline Alpine.data ke JS files
- **Files:** 14 Blade views menggunakan `document.addEventListener('alpine:init', () => { Alpine.data('...', ...) })`
- **Issue:** Semua akan broken dengan `wire:navigate` — fix sama seperti payroll
- **Files affected:**
  - `resources/views/loans/index.blade.php` — `loansIndex`
  - `resources/views/overtimes/apply.blade.php` — `overtimeApply`
  - `resources/views/overtimes/index.blade.php` — `overtimesIndex`
  - `resources/views/leaves/apply.blade.php` — `leaveApply`
  - `resources/views/leaves/index.blade.php` — `leavesIndex`
  - `resources/views/reimbursements/apply.blade.php` — `reimbursementApply`
  - `resources/views/reimbursements/index.blade.php` — `reimbursementsIndex`
  - `resources/views/attendance/index.blade.php` — `attendanceIndex`
  - `resources/views/approvals/index.blade.php` — `approvalsIndex`
  - `resources/views/employee/terminate-modal.blade.php` — `terminateEmployeeForm`
  - `resources/views/employee/index.blade.php` — `employeesIndex`
  - `resources/views/employee/create-edit-modal.blade.php` — `createEmployeeForm`
  - `resources/views/employee/import-export-modal.blade.php` — `importEmployeesForm`
  - `resources/views/assets/index.blade.php` — `assetsIndex`
- **Fix:** Ekstrak masing-masing ke `resources/js/{nama}.js`, register via `alpine:init` di `app.js`

### Task J2 — Hover remnant di attendance/index
- **File:** `resources/views/attendance/index.blade.php:94`
- **Issue:** `hover:bg-[#1f1f1f]` masih ada
- **Fix:** Ganti `hover:bg-[#1f1f1f]` → `hover:bg-primary-container`

## 🟠 Stale PHPStan baseline (P1)

### Task P1 — Hapus 7 stale entries phpstan-baseline
- **File:** `phpstan-baseline.neon` lines 745, 751, 757, 763, 769, 775, 781
- **Issue:** Mereferensi `app/Notifications/LeaveApproved.php`, `LeaveRejected.php`, `LeaveRequestSubmitted.php` yang sudah dihapus
- **Fix:** Hapus 7 entry atau set `reportUnmatchedIgnoredErrors: false`

---

# 🔴 Temuan End-to-End Testing — 30 Juni 2026

Hasil test manual sebagai end user di semua role. Server `http://127.0.0.1:8000` (PostgreSQL, PHP 8.5, Laravel 13.4).

## Login Flow

| Akun | Email | Password | Hasil |
|------|-------|----------|-------|
| Super Admin | `admin@hrconnect.local` | `ChangeMe!2026` | ✅ Login → dashboard, semua page 200 |
| Finance | `finance@hrconnect.local` | `password` | ✅ Login → dashboard |
| Manager | `manager@hrconnect.local` | `password` | ✅ Login → dashboard |
| Employee | `staff@hrconnect.local` | `password` | ✅ Login → dashboard |
| Employee (real) | `gilda18@example.com` (Salwa) | `password` | ✅ Login → ⚠️ redirect `/settings/security` (password change) |

## 🟢 E1 — API Leaves & Reimbursements (Sudah Ada ✅)

**Severity:** ~~Critical~~ → **Bukan issue.** Routes sudah ada di `routes/api.php:119-154`, tapi pakai **singular** naming:
- `/api/v1/leave` → 6 records ✅ (bukan `/api/v1/leaves`)
- `/api/v1/reimbursement` → 7 records ✅ (bukan `/api/v1/reimbursements`)

Temuan di sesi testing adalah **false alarm** — URL yang dites salah (plural). Masalah naming inkonsisten diliput di E4.

## 🟢 E2 — Demo users tidak punya employee record (Selesai ✅)

**Fix:** Hapus guard `Employee::count() > 5` di `DemoDataSeeder.php` (ganti ke `updateOrCreate`), tambah field NOT NULL yang kurang (`phone`, `nik`, `npwp`, `bank_account_number`, `birth_date`, `marital_status`, `blood_type`, `education_level`, `institution_name`, `major`, `graduation_year`, `bank_name`). Semua demo user (id 1, 50, 51, 52) sekarang punya `employees` record.
- ✅ `GET /api/v1/attendance/today` → `success`
- ✅ `GET /api/v1/approvals/pending` → `success`
- ✅ `GET /api/v1/leave/quota` → `success`

## 🟢 E3 — password_changed_at null → redirect loop (Selesai ✅)

**Fix:** Tambah `'password_changed_at' => $now` di `DemoDataSeeder` User creation (line 65). Update massal 49 user dengan `password_changed_at = null` via query `User::whereNull('password_changed_at')->update(['password_changed_at' => now()])`.
- ✅ Semua 55 users sekarang punya `password_changed_at`

## 🟡 E4 — API route naming inconsistent

**Severity:** Medium
**Files:** `routes/api.php`
**Issue:** Naming convention tidak konsisten:
| Endpoint | Convention | Seharusnya |
|----------|:----------:|:----------:|
| `/api/v1/payroll` | singular | `/api/v1/payrolls` (plural) |
| `/api/v1/overtime` | singular | `/api/v1/overtimes` (plural) |
| `/api/v1/attendance` | singular | `/api/v1/attendances` (plural) |

**Dampak:** Developer confusion, REST convention violation.
**Fix:** Rename routes atau set `Route::apiResource` dengan parameter name yang plural.

## 🟡 E5 — RAG Gemini offline (fallback pg_trgm)

**Severity:** Medium
**Issue:** `POST /api/v1/knowledgebase/chat` return `answer: "Sistem AI sedang offline."`, `confidence: "low"`, `model: "pg_trgm"`.
**Akar masalah:** Gemini API key tidak terkonfigurasi atau rate limited. `RAG_MOCK_MODE=true` di `.env` adalah dead config (kode PHP tidak membacanya).
**CURL Test:**
```json
POST /api/v1/knowledgebase/chat {"question":"Apa itu cuti tahunan?"}
→ {"answer":"Maaf, tidak ada informasi yang cocok... Sistem AI sedang offline.","confidence":"low","fallback":true,"model":"pg_trgm"}
```
**Fix:** Set `GEMINI_API_KEY` valid di `.env`, atau implementasi mock yang benar.

## 🟡 E6 — Seed data coverage tidak merata

**Severity:** Medium
**Data di DB:**
| Tabel | Jumlah | Bisa diakses? |
|-------|:------:|:-------------:|
| `employees` | 46 | ✅ Ya (via API) |
| `branches` | 11 | ✅ Ya |
| `departments` | 9 | ✅ Ya |
| `positions` | 11 | ✅ Ya |
| `leaves` | 6 | ✅ Ya (via web) |
| `payrolls` | 4 | ✅ Ya |
| `assets` | 0 | ❌ 0 record |
| `loans` | 0 | ❌ 0 record |
| `overtimes` | 0 | ❌ 0 record |
| `knowledge_base` | ✅ Ada | ✅ Ya |

**Dampak:** Asset, loan, dan overtime features tidak bisa di-test secara end-to-end.

## 🟢 E7 — Database cache lock error (Selesai ✅)

**Fix:** `config/cache.php:47` — tambah default `'cache'` untuk `lock_table`: `env('DB_CACHE_LOCK_TABLE', 'cache')`. Sebelumnya `env('DB_CACHE_LOCK_TABLE')` return null → SQL `update "" set ...`.

## 🟢 API Endpoints — Working (untuk referensi)

**Master Data (semua via SA token):**
```
GET /api/v1/branches      → 11 records ✅
GET /api/v1/employees     → 46 records ✅ (paginated)
GET /api/v1/departments   → 9 records ✅
GET /api/v1/positions     → 11 records ✅
GET /api/v1/companies     → data ✅
```

**HR Features:**
```
GET /api/v1/payroll       → 4 records ✅
GET /api/v1/overtime      → 0 records ✅
GET /api/v1/assets        → 0 records ✅
GET /api/v1/loans         → 0 records ✅
GET /api/v1/knowledgebase → data ✅
GET /api/v1/attendance    → 0 records ✅
```

**Auth:**
```
POST /api/v1/auth/login    → Sanctum token ✅
POST /api/v1/auth/logout   → ✅
GET  /api/v1/health        → all services up ✅
```

## Prioritaskan Sesuai Role (Update)

| Prioritas | Role | Fokus |
|-----------|------|-------|
| **P1** | **HR-Manager** | E6 (seed data), J1 ✅, J2 ✅, E2 ✅, E3 ✅, E7 ✅ |
| **P2** | **Finance** | T1, T2, A1, A2 |
| **P3** | **Manager** | T6, S1, B1-B3 |
| **P4** | **Employee** | E4 (route naming), T4, C1, S2 |

---

# 🔴 Temuan Audit — Deep Dive Verifikasi (1 Juli 2026)

Hasil inspeksi paralel 6 agen terhadap 7 temuan "mati" dari audit sebelumnya.
**7 dari 20 temuan awal adalah false positive. 13 temuan valid.**

## Ringkasan Verifikasi

| Temuan | Status | Rekomendasi |
|--------|--------|-------------|
| 4 API Resources tidak terpakai ✅ Valid | PayrollResource: drop-in replace / PayslipResource: hapus / Sisanya: skip |
| 2 Livewire components mati ✅ Valid | SalaryCalculator: embed + fix JS bug / ImportProgressBar: butuh pipeline |
| Settings routes broken ❌ FALSE POSITIVE | Berfungsi normal — tidak ada perubahan |
| ApprovalPolicy missing ✅ Valid | Buat ApprovalPolicy |
| Stale PHPStan baseline ✅ Valid | 7 entries dari 3 file Leave* → hapus |
| API naming inkonsisten ✅ Valid | Payroll→Payrolls, Overtime→Overtimes, Attendance→Attendances |
| 8 controller zero test coverage ✅ Valid | Lihat task T1-T6 |
| 2 major dependency updates ✅ Valid | Perlu update |
| 23 unused API Resources ❌ FALSE POSITIVE | Hanya 4 yang benar-benar tidak terpakai |
| 4 dead Livewire classes ❌ FALSE POSITIVE | Hanya 2 benar-benar mati (SalaryCalculator + ImportProgressBar) |
| Route::livewire() deprecated ❌ FALSE POSITIVE | Macro valid di Livewire 4 |
| Pdo\Mysql crash ❌ FALSE POSITIVE | PHP 8.5 sudah support `Pdo\Mysql` |
| EmailVerificationController import ❌ FALSE POSITIVE | Class sudah auto-import (same namespace) |
| GeoIP import crash ❌ FALSE POSITIVE | Import tidak dipakai — hanya `class_exists()` |
| notify event listener ❌ FALSE POSITIVE | Event listener handle BOTH `notify` + `toast` |
| Duplicate function makeBranch ❌ FALSE POSITIVE | Helper, bukan test — `--process-isolation` tidak terpengaruh |
| Hardcoded employee_id ❌ FALSE POSITIVE | ID valid dari seeder |

## Detail API Resources (Valid 4)

| Resource | Putusan | Alasan |
|----------|---------|--------|
| **PayrollResource** | ✅ **Gunakan sekarang** | Field identik dengan inline array di `PayrollController::show()`. Drop-in replace — simpan 17 baris. |
| **PayslipResource** | ❌ **Hapus** | Tidak ada endpoint JSON payslip. Satu-satunya endpoint (`/payroll/{id}/payslip`) return PDF binary. |
| **AttendanceResource** | ⏸ **Skip** | Field name mismatch (`clock_in_time` vs `clock_in`), tambah field `face_similarity_score` + `work_duration_hours` di controller. Perlu restruktur. |
| **KnowledgeBaseResource** | ⏸ **Skip** | Perlu conditional field suppression (`content` blob dihilangkan di listing). |

### Task D1 — Pakai PayrollResource (drop-in replace)
- **File:** `app/Http/Controllers/Api/PayrollController.php`
- **Baris:** 88-106
- **Sekarang:** `return response()->json([... 18 fields ...])`
- **Jadi:** `return PayrollResource::make($payroll->load('employee', 'items'))`
- **Effort:** ~2 menit, hapus 17 baris boilerplate

### Task D2 — Hapus PayslipResource
- **File:** `app/Http/Resources/PayslipResource.php`
- **Cek:** Pastikan tidak ada referensi lain (`grep -r PayslipResource`)
- **Effort:** ~1 menit

## Detail Livewire Components (Valid 2)

### SalaryCalculator 🟢 Siap Produksi — Tinggal Embed
Kode **100% siap produksi** — hanya belum di-embed di view manapun.

**Yang sudah:**
- ✅ PHP class `SalaryCalculator.php` (77 baris) — panggil BpjsService, Pph21Service, PotonganService
- ✅ Blade `salary-calculator.blade.php` (166 baris) — form input + hasil hitung + client-side JS kalkulator

**Yang kurang:**
- ❌ Tidak ada `@livewire('salary-calculator')` di view manapun
- 🐛 **JS bug** baris 120-121: client-side alfa deduction tidak include `tunjanganMakan` + `tunjanganTransport` (beda dengan server-side calculation)

### Task D3 — Embed SalaryCalculator + fix JS bug
- **File:** `resources/views/employee/show.blade.php`
- **Tambah:** Tab ke-5 "Kalkulator Gaji" dengan:
  ```blade
  @livewire('salary-calculator', ['employee-id' => $employee->id])
  ```
- **File:** `resources/views/livewire/salary-calculator.blade.php`
- **Fix:** Baris 120-121 — tambah `tunjanganMakan` + `tunjanganTransport` ke client-side alfa deduction
- **Effort:** ~30 menit

### ImportProgressBar 🟡 Setengah Jadi — Butuh Backend Pipeline
Component + model + migration siap. Tapi pipeline backend tidak ada.

**Yang sudah:**
- ✅ PHP class `ImportProgressBar.php` (63 baris) — polling pattern, Livewire events
- ✅ Blade `import-progress-bar.blade.php` (16 baris) — UI progress bar
- ✅ Model `ImportProgress.php` (55 baris) + migration ✅ — tabel `import_progress` siap

**Yang kurang:**
- ❌ Tidak ada endpoint `POST /api/v1/employees/import` di `routes/api.php`
- ❌ Tidak ada queue job untuk proses CSV baris per baris
- 🐛 **Bug** `ImportProgressBar.php:57`: dispatch `notify` pakai `type` bukan `variant` → toast tampil hijau
- ❌ **JS** `resources/js/import-employees-form.js` panggil endpoint `/api/v1/employees/import` → 404

### Task D4 — Fix ImportProgressBar toast bug
- **File:** `app/Livewire/ImportProgressBar.php`
- **Baris:** 57
- **Sekarang:** `$this->dispatch('notify', type: 'success', text: '...')`
- **Jadi:** `$this->dispatch('toast', variant: 'success', text: '...')`
- **Effort:** ~1 menit

### Task D5 — Complete ImportProgressBar pipeline
- **Buat:** `POST /api/v1/employees/import` route di `routes/api.php`
- **Buat:** `app/Jobs/ImportEmployeesJob.php` — queue job untuk proses CSV
- **Buat:** `app/Http/Requests/ImportEmployeesRequest.php` — validasi upload CSV
- **Update:** `resources/js/import-employees-form.js` — ganti endpoint dummy dengan yang beneran
- **Effort:** ~setengah hari, 5-6 file baru

## Update Prioritas

| Prioritas | Task | Role | Effort | Dampak |
|:---------:|------|------|--------|--------|
| **1** | **D1** — PayrollResource drop-in | Finance | ~2 menit | Hapus 17 baris boilerplate |
| **2** | **D3** — Embed SalaryCalculator | HR-Manager | ~30 menit | Hidupkan fitur kalkulator gaji |
| **3** | **D4** — Fix toast bug ImportProgressBar | HR-Manager | ~1 menit | Toast error tampil benar |
| **4** | **D2** — Hapus PayslipResource | Finance | ~1 menit | Bersihkan dead code |
| **5** | **D5** — Import pipeline lengkap | HR-Manager | ~4 jam | Fitur import CSV karyawan |
| — | **AttendanceResource + KnowledgeBaseResource** | — | Skip | Tidak worth the refactor |

---

# 🔴 Browser Console Error Analysis — 1 Juli 2026

Hasil inspeksi browser console pada semua halaman. **Semua route error disebabkan oleh JS runtime errors, bukan route Laravel.**

## F1 — `Failed to resolve module specifier 'face-api.js'` 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **Error** | `Uncaught TypeError: Failed to resolve module specifier 'face-api.js'` |
| **Sumber** | `face-registration.blade.php:22` (startCamera) + `clock-in.blade.php:21` (init) |
| **Akar masalah** | `import('face-api.js')` dipanggil di dalam string Alpine `x-data` expression. Alpine mengevaluasi string ini di **luar konteks module Vite** — bare module specifier `'face-api.js'` tidak bisa di-resolve oleh Vite. |
| **Dampak** | Semua fitur face (registrasi + clock-in) **tidak berfungsi** |
| **Fix** | Import statis di `app.js`, expose ke `window.faceapi` (sama seperti pattern `window.L = L` untuk Leaflet) |
| **Referensi** | PasPapan: `<script src="/assets/js/face-api.min.js">` (UMD global) |

### Detail implementasi
- `app.js`: `import * as faceapi from 'face-api.js'; window.faceapi = faceapi;`
- `face-registration.blade.php`: Hapus `faceapi: null` dari x-data, hapus `import('face-api.js')` dari `startCamera()`, pakai `window.faceapi` langsung
- `clock-in.blade.php`: Sama — hapus `faceapi: null`, hapus `import()`, pakai `window.faceapi`

## F2 — `face_crop` vs `photo_selfie` mismatch 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **File** | `clock-in.blade.php:166` vs `ClockInRequest.php:48` |
| **Sekarang** | JS kirim `payload.face_crop`, server validasi `photo_selfie` |
| **Dampak** | `$data['photo_selfie']` = null → **foto wajah tidak pernah tersimpan** di DB |
| **Fix** | Ganti `payload.face_crop` → `payload.photo_selfie` di `clock-in.blade.php:166` |

## F3 — `gps_variance` missing dari ClockInRequest rules 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **File** | `ClockInRequest.php` (rules) |
| **Sekarang** | `gps_variance` tidak ada di rules array |
| **Dampak** | Controller panggil `$request->validated()` (line 43 AttendanceController) — field tidak masuk validasi. `$data['gps_variance']` = null di AttendanceService:169 → risk scorer dapat null |
| **Fix** | Tambah `'gps_variance' => ['nullable', 'numeric', 'min:0']` ke `ClockInRequest::rules()` |

## F4 — `is_mocked: false` hardcoded 🔴 CRITICAL

| Aspek | Detail |
|-------|--------|
| **File** | `clock-in.blade.php:159` |
| **Sekarang** | `is_mocked: false` selalu dikirim tanpa pengecekan GPS mock |
| **Dampak** | Anti-fake-GPS check di `AttendanceService::clockIn()` (cek `$data['is_mocked'] == true`) **tidak pernah trigger** |
| **Fix** | Hapus hardcode; biarkan `undefined` (server handle null) atau implementasi deteksi mock GPS client-side |

---

# 🔴 Comprehensive Code Audit — 4 Parallel Agents (1 Juli 2026)

Audit mendalam kode HRConnect oleh 4 agen paralel. **40+ temuan terverifikasi.**

## CRITICAL (Harus diperbaiki segera)

| ID | Temuan | File | Dampak |
|:--:|--------|------|--------|
| **C1** | SQL injection via `LIKE` wildcard | `EmployeeController` | Search `%_%` match unintended records. `%` + `_` wildcard tidak di-escape. |
| **C2** | Mass assignment di EmployeeController | `EmployeeController::store()` | Field `face_embedding`, `pin` bisa di-set via mass request — tidak ada Guard |
| **C3** | PII leak via EmployeeResource | `app/Http/Resources/EmployeeResource.php` | `nik`, `phone`, `npwp`, `bank_account_number` terexpose di listing — Hidden attribute hanya untuk serialization langsung |
| **C4** | Dual face embedding desync | `FaceController::register()` | Embedding disimpan di **2 tempat**: `face_descriptors` + `employee.face_embedding`. Tidak ada mekanisme sinkronisasi — desync jika salah satu gagal. |
| **C5** | View expose full model | `BelongsTo` + `HasMany` relationships tanpa select guard | Resource query `load()` bisa expose hidden fields via relationship eager loading |
| **C6** | Amount integer truncation | Migration `decimal` → PHP `int` cast | Payroll amount dengan decimal bisa truncated saat kalkulasi |
| **C7** | FeaturePolicy / ApprovalPolicy missing | `ApprovalController` | Tidak ada Policy class — autorisasi manual `approver_id` check. 1 controller tanpa Policy. |
| **C8** | `import('face-api.js')` gagal di Alpine | face-registration, clock-in | **Lihat F1 di atas** — semua fitur face broken |
| **C9** | `face_crop` vs `photo_selfie` mismatch | clock-in, ClockInRequest | **Lihat F2** — foto wajah tidak tersimpan |
| **C10** | `gps_variance` missing dari rules | ClockInRequest | **Lihat F3** — risk scorer tidak dapat data |
| **C11** | `is_mocked: false` hardcoded | clock-in blade | **Lihat F4** — anti-fake-GPS tidak berfungsi |

## HIGH (Prioritas setelah CRITICAL)

| ID | Temuan | File | Detail |
|:--:|--------|------|--------|
| **H1** | 7 route closures disable `route:cache` | 7 route files di `routes/` | Route closures prevent Laravel route caching — gunakan invokable controller |
| **H2** | Missing `verified` middleware di `profile.edit` | `routes/settings.php` | Profile bisa diakses tanpa verifikasi email |
| **H3** | Email enumeration timing | `FortifyServiceProvider` | Login response time berbeda untuk email exist vs tidak — timing attack vector |
| **H4** | Route closures di 7 files | `routes/{attendance,leave,overtime,payroll,approval,knowledge-base,asset,loan,reimbursement}.php` | Multiple files pakai closure → `route:cache` skip semua |
| **H5** | Missing auth on `categories()` | Route definition | Endpoint tanpa auth guard |
| **H6** | Livewire navigated toast re-registration | `app.js` | `livewire:navigated` listener mungkin daftarkan multiple handler → multiple toast muncul |
| **H7** | `Number::currency` intl fallback | `Number::currency($amount, 'IDR')` | Fallback locale mungkin tidak support IDR formatting |
| **H8** | Payroll period LIKE full scan | Period query | Full table scan untuk filter period |
| **H9** | Dual font loading | CSS | Font dimuat 2x — sekali dari Google Fonts, sekali dari local |
| **H10** | Face liveness variance threshold 0.5 terlalu longgar | `face-registration.blade.php:104` | Variance 0.5 sebagai threshold liveness — terlalu rendah |
| **H11** | EAR (Eye Aspect Ratio) tidak pernah digunakan untuk liveness | `clock-in.blade.php:105-107` | EAR dihitung (`computeEAR`) tapi tidak dipakai untuk apa pun |
| **H12** | Descriptor dihitung setiap frame | `clock-in.blade.php:104` | `lastDescriptor` di-update tiap frame (300ms) — sia-sia, cukup 1x saat clock-in |
| **H13** | Dual storage FaceDescriptor vs Employee.face_embedding | `FaceRecognitionService` | Service cek 2 tempat berbeda — desync risk tinggi |
| **H14** | Old FaceDescriptor tidak di-deactivate saat re-enroll | `FaceController::register()` | Tidak ada `is_active = false` untuk descriptor lama → multiple active descriptors |

## MEDIUM

| ID | Temuan | File | Detail |
|:--:|--------|------|--------|
| **M1** | No model timeout in FaceRecognition queries | `FaceRecognitionService` | Query tanpa timeout — bisa hang |
| **M2** | Empty catch swallows errors | `face-registration.blade.php:70,114,156` | `catch {}` tanpa logging — error siluman |
| **M3** | 6 JPEG base64 dikirim ke server via captures | `face-registration.blade.php:139-146` | Base64 JPEG captures terkirim tapi hanya `captures_count` yang disimpan. Boros bandwidth. |
| **M4** | `computeVariance()` math salah | `face-registration.blade.php:120-133` | Variance dibagi `embeddings.length` bukan `(embeddings.length * dims)` |
| **M5** | Vite dynamic import mungkin gagal di production | `face-registration.blade.php:22`, `clock-in.blade.php:21` | Dynamic `import()` di production = chunk split issue |
| **M6** | No `beforeunload` cleanup | `face-registration.blade.php` | Stream tidak dihentikan saat user navigasi away |
| **M7** | No CSRF rotation | Rate limiter config | CSRF token tidak rotate |
| **M8** | Password expiry not notified | Password expiry config | User tidak dapat notifikasi sebelum password expired |
| **M9** | Device verification whitelist no-op | Device verification | Whitelist tidak diimplementasi — bypass verification repeat |
| **M10** | Audit log untuk PII access tidak detail | Audit Log | Tidak ada detail field apa yang diakses |

## LOW

| ID | Temuan | Detail |
|:--:|--------|--------|
| **L1** | `for` attribute di label tidak cocok dengan `id` input | Beberapa form komponen |
| **L2** | `alt` text missing di beberapa img | Aksesibilitas |
| **L3** | Console log statement di production | `console.log` masih ada di beberapa JS file |
| **L4** | Magic number `0.5` untuk scoreThreshold | Tidak ada konstanta bernama |
| **L5** | `setTimeout(..., 1500)` untuk GPS sampling — hardcoded | Tanpa konstanta |
| **L6** | No empty state untuk loading error | Beberapa tampilan loading tanpa error handling UI |
| **L7** | Inconsistent `__()` usage (ID vs EN mix) | Beberapa blade masih pakai English |
| **L8** | `destroy()` method di Alpine tidak selalu dipanggil | Beberapa komponen tanpa cleanup |

---

# 🔴 Role-Based UI Audit — 1 Juli 2026

Temuan: **Semua role mendapatkan UI yang identik.** Single layout untuk HR-Manager, Finance, Manager, dan Employee.

## Root Cause

| Aspek | Detail |
|-------|--------|
| **Layout** | Semua view pakai `x-layouts::app.sidebar` — tidak ada perbedaan untuk admin vs user |
| **Sidebar** | Semua role lihat 10+ menu item yang sama. Hanya 1 item (`Karyawan`) digate dengan `@can('viewAny', Employee::class)` |
| **Bottom nav** | `components/bottom-nav.blade.php` — **zero role gating**. Staff lihat menu Master Data, Penggajian, dll. |
| **Dashboard** | `DashboardController` sudah role-aware untuk statistik, tapi QuickActions dan menu navigasi tidak dibedakan |
| **Route prefix** | Tidak ada prefix `admin/*` untuk fitur admin — semua route flat |

## Temuan Spesifik

| ID | Issue | Dampak |
|:--:|-------|--------|
| **R1** | Sidebar item sama untuk semua role | Staff bisa lihat menu Master Data, Penggajian yang tidak relevan |
| **R2** | Bottom nav tanpa gate | Mobile navigation expose fitur admin ke semua user |
| **R3** | QuickActions sudah partial fix (approvals, dokumen) | Tapi 8 action lainnya masih sama untuk semua role |
| **R4** | Tidak ada `admin-ui` vs `user-ui` body class | CSS targeting per role tidak mungkin |
| **R5** | Tidak ada admin route prefix pattern | `admin.*` route names tidak bisa di-detect di layout |

## Pattern Fix (Dari PasPapan)

```
Detect route name di layout:
- `admin.*` route prefix → body class `admin-ui` → sidebar penuh
- `employee.*` atau `user.*` → body class `user-ui` → sidebar minimal + bottom nav

Route naming:
- admin.employees.index, admin.payroll.index → HR/Finance
- employee.attendance.index, employee.leave.index → Staff
```

---

# 🔴 Face Registration & Recognition Audit — 26 Issues (1 Juli 2026)

Hasil audit mendalam terhadap flow face registration (face-registration.blade.php + FaceController) dan face recognition (clock-in.blade.php + FaceRecognitionService + AttendanceService).

## CRITICAL (3)

| ID | Issue | File | Fix |
|:--:|-------|------|-----|
| **FC1** | `gps_variance` di-drop oleh `$request->validated()` | `ClockInRequest.php` | Tambah `'gps_variance' => ['nullable', 'numeric', 'min:0']` — **sama dengan F3** |
| **FC2** | `face_crop` dikirim JS tapi `photo_selfie` divalidasi server | `clock-in.blade.php:166` vs `ClockInRequest.php:48` | Ganti `face_crop` → `photo_selfie` — **sama dengan F2** |
| **FC3** | `is_mocked: false` hardcoded | `clock-in.blade.php:159` | Hapus hardcode atau implementasi deteksi — **sama dengan F4** |

## HIGH (5)

| ID | Issue | File | Detail |
|:--:|-------|------|--------|
| **FC4** | Liveness variance threshold 0.5 terlalu longgar | `face-registration.blade.php:104` | Variance 0.5 tidak membedakan gerakan real vs statis. Pasang threshold lebih ketat (misal 0.15) atau implementasi challenge-response |
| **FC5** | EAR (Eye Aspect Ratio) dihitung tapi tidak digunakan | `clock-in.blade.php:105-107, 119-126` | `computeEAR()` dipanggil setiap deteksi, hasilnya di-push ke `earHistory`, tapi **tidak pernah** digunakan untuk verifikasi liveness (blink detection) |
| **FC6** | Descriptor dihitung setiap frame — sia-sia | `clock-in.blade.php:104` | `lastDescriptor` di-update tiap 300ms; seharusnya hanya dihitung 1x saat user klik clock-in (`captureFaceCrop`) |
| **FC7** | Dual storage: FaceDescriptor vs Employee.face_embedding | `FaceController::register():82-83` | Embedding disimpan di 2 tempat tanpa sinkronisasi. Lapisan lama (`employee.face_embedding`) seharusnya dihapus setelah FaceDescriptor stabil. |
| **FC8** | Old FaceDescriptor tidak di-deactivate saat re-enroll | `FaceController::register()` | `FaceDescriptor::create()` tanpa `where('is_active', false)` update dulu. Multiple active descriptors bisa muncul. |

## MEDIUM (10)

| ID | Issue | Detail |
|:--:|-------|--------|
| **FC9** | `nearestNeighbors` tanpa timeout di pgvector | Query bisa hang jika indeks rusak |
| **FC10** | Empty `catch {}` di 3 tempat (face-registration) | Error siluman — tidak ada logging |
| **FC11** | 6 base64 JPEG capture dikirim ke server | Hanya `captures_count` yang disimpan — 6 gambar full-res base64 terkirim percuma |
| **FC12** | `computeVariance()` math wrong | Variance dibagi `embeddings.length` bukan `embeddings.length * dims` |
| **FC13** | Vite dynamic import di production | `import('face-api.js')` di production bikin code split terpisah |
| **FC14** | No `beforeunload` cleanup di face-registration | Stream kamera tetap jalan saat navigasi |
| **FC15** | `faceapi.euclideanDistance()` tidak dipakai | Service pake cosine distance via pgvector, tapi JS bisa compute client-side untuk real-time feedback |
| **FC16** | Tidak ada max retry untuk model loading | Jika model gagal load, infinite retry tiap 300ms di detection loop |
| **FC17** | No empty state jika camera izin ditolak permanent | Hanya set statusText, tidak ada UI guidance |
| **FC18** | `photo_selfie` column expects base64 string no size limit | Payload bisa sangat besar (~100KB per capture) tanpa validasi |

## LOW (8)

| ID | Issue | Detail |
|:--:|-------|--------|
| **FC19** | No aria-label di video element | Aksesibilitas |
| **FC20** | `captureFrame()` tidak stop detection loop selama capture | Race condition: detection loop hitung descriptor bersamaan dengan capture |
| **FC21** | `modelsLoading` flag tidak direset jika gagal | User stuck di loading state forever |
| **FC22** | No loading indicator untuk `registerFace()` API call | User tidak tahu request sedang diproses |
| **FC23** | Magic number `0.3` dan `0.5` untuk scoreThreshold | Tidak ada konstanta |
| **FC24** | `setTimeout(() => sample(i + 1), 1500)` hardcoded | Tidak ada konstanta untuk interval GPS |
| **FC25** | No validation bahwa captureCount === embeddings.length | Bisa mismatch (1:1 mapping diperlukan) |
| **FC26** | `destroy()` method ada tapi tidak di-trigger otomatis oleh Alpine | Perlu `@cleanup` atau `x-effect` untuk cleanup |

---



# 🟡 UX Issues — Remaining (9 item, belum diperbaiki)

Hasil audit UX E2E (1 Juli 2026). 11 dari 20 item sudah fixed.

| # | Issue | Lokasi | Severity | Status |
|:-:|-------|--------|:--------:|:------:|
| UX-1 | **Knowledge Base, Loans, Assets tidak punya link navigasi** — fitur exist tapi user tidak bisa nemuin | Sidebar + bottom-nav | HIGH | ⏳ |
| UX-2 | **Approvals & clock-in page title kosong** — browser tab cuma "Laravel" | `approvals/index.blade.php`, `clock-in.blade.php` | HIGH | ⏳ |
| UX-3 | **Mobile leave list pakai Inggris, desktop Indonesia** — status: Mobile "Pending" vs Desktop "Menunggu" | `leaves/index.blade.php` | MEDIUM | ⏳ |
| UX-4 | **Auto-capture 620ms tanpa countdown** — user kaget kamera tiba-tiba motret | `face-registration.blade.php` | HIGH | ⏳ |
| UX-5 | **Tidak ada panah arah liveness** — user cuma dikasih teks "tengok ke satu sisi" | `face-registration.blade.php` | HIGH | ⏳ |
| UX-6 | **Tidak ada progress bar liveness** — 3-step challenge tanpa feedback sejauh mana | `face-registration.blade.php` | HIGH | ⏳ |
| UX-7 | **Tidak ada tombol navigasi setelah sukses** — registrasi berhasil tapi user bingung mau ke mana | `face-registration.blade.php` | HIGH | ⏳ |
| UX-8 | **Bottom nav "Pengajuan" href ke `/leaves`** — tapi active pattern include overtimes/reimbursements/approvals → klik salah | `bottom-nav.blade.php` | MEDIUM | ⏳ |
| UX-9 | **Bottom nav zero authorization** — employee lihat Payroll/Pengajuan sama persis dengan HR | `bottom-nav.blade.php` | MEDIUM | ⏳ |
