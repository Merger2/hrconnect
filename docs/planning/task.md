# Task Tracker — HRConnect Skripsi: Face Recognition + GPS Geofencing + RAG Knowledge Base

> Updated: 2026-07-02 — Sesi A ✅ B ✅ C ✅ D ✅ E ✅ F ✅ G ✅. **P0 bugs fixed: Approval JS + Finance dashboard + Dead sidebar**. Audit multi-repo (PRD + 5 repos) selesai. 1,173 tests pass.

> **SESI A ✅ (2026-06-28):** 14/14 items completed — EV-1..7, PERM-1/2/3, SEC-1/2/3/4, P0-1..4, P1-5/6/7. **EV-2 (Gmail SMTP) deferred.**

> **SESI B ✅ (2026-06-28):** 13+3 items — C-1..4 camera bugs, FE-1c..h face enrollment/liveness/TinyFaceDetector/EAR blink/CDN cleanup/face crop, SEC-GPS-1/2/3 GPS 3-layer, P2-2/3/4.

> **SESI C ✅ (2026-06-28):** RAG Knowledge Base UI — Chat AI (SSE streaming), Upload PDF, Manage.

> **SESI D ✅ (2026-06-28):** ESS pages — Attendance, Leave, Overtime, Reimbursement, Payroll.

> **SESI E ✅ (2026-06-28):** Approvals — index (Pending+History), detail modal.

> **SESI F ✅ (2026-07-01):** 22+ items — Face-registration rewrite (PasPapan head-turn liveness, geometry descriptor, guide overlay, auto-capture, countdown, progress bar, post-success nav), Employee index rewrite (summary bar, avatar, badge tone, ID labels, mobile cards), Employee show rewrite (section cards, info grid, PII gate), Dashboard role split (admin vs employee), API 401 auth fix (42 fetch calls via `window.apiHeaders()`), Sidebar gating 12 items, Bottom nav gating per permission, Route `can:` middleware (8 route files), ApprovalPolicy created, AssetController SQL driver-aware, EAR blink detection clock-in, FaceController `updateOrCreate`+`is_active`, JS silent catch → toast (9 files), EmployeeResource `photo_url` added, UX-1..9 all fixed, language consistency (80+ ID keys). **Reference repos studied: PasPapan, Quanta HRIS, Laravel-Smarthr, HRMS, hris.**

> **EXECUTION STATUS (2026-07-01):** Ses A+B+C+D+E+F ✅ complete. **Next priorities: P1 route closures (15 closures) + Branch geofencing UI + Finance payroll admin UI + Manager team view.** Backend 100% ready (51 endpoints, 1,173 tests, 4,128 assertions).

> **SECURITY POSTURE (2026-07-01):** Full audit keamanan selesai. Ditemukan **4 critical** (Sanctum token never-expire, fake GPS 100% client-trusted, no liveness detection, no security headers middleware), **8 warning** (MustVerifyEmail, API gate, 2FA enforcement, dll), **8 sudah secure** (CipherSweet, PII masking, Argon2id, rate limiting, IDOR, session encrypted, host protection, FormRequest). **Post-Sesi A+B+F: 1 critical fixed (Sanctum expiry ✅, fake GPS multi-layer ✅, liveness ✅, face-api.js import ✅, face_crop→photo_selfie ✅, gps_variance ✅, is_mocked ✅), 1 deferred (headers).**

## Status Legend

| Status | Meaning |
|--------|---------|
| ✅ | Done |
| 🚧 | In progress |
| ⏳ | Not started |
| 🚫 | Deferred/cancelled |

## Status Snapshot — Overall Project: **~40% selesai** (target ~100 Blade files)

| Area | % | Status | Notes |
|------|:-:|:------:|-------|
| Backend (app/) | 100% | ✅ | 34 models, 34 enums, 19 services, 21 controllers. 80+ endpoints. Production-ready. |
| Database (migrations) | 100% | ✅ | 50 migrations, 52 tables. All features supported. |
| API (routes) | 100% | ✅ | 80+ endpoints, Sanctum auth, rate limits, permission guards. |
| Security | 90% | ✅ | CipherSweet ✅, PII masking ✅, Argon2id ✅, rate limiting ✅, IDOR ✅, session encrypted ✅, host protection ✅, FormRequest ✅. **All Sesi A+F fixes ✅. Security headers ❌** |
| Tests | 95% | ✅ | 1,173 tests / 4,128 assertions (SQLite) + ~28 PG. |
| **Frontend** | **~40%** | 🚧 | **~35 functional pages selesai.** Butuh ~55 pages + ~15 components + ~8 layouts/partials + ~8 email = ~100 total. |
| **Component Library** | **~30%** | 🚧 | 3 components. Butuh 9 baru. |
| **Design System DS-1** | **50%** | 🚧 | app.css white canvas, app vs landing split final. |
| **Architecture Cleanup** | **45%** | 🚧 | Route closures (15) masih blocking `route:cache`. |
| **PWA** | **40%** | 🚧 | |
| **PHPStan** | 0% | 🚧 | |

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
| Dashboard | 2 | — | 2 | 0 | ✅ |
| Employee | 2 | 2 | 2 | 2 | ✅ |
| Organization | 2 | 2 | 0 | 4 | 3 |
| Attendance | 6 | 2 | 4 | 4 | ✅ |
| Leave | 3 | 2 | 2 | 3 | ✅ |
| Overtime | 2 | 1 | 2 | 1 | ✅ |
| Reimbursement | 2 | 1 | 2 | 1 | ✅ |
| Payroll | 8 | 3 | 3 | 8 | 2+4 |
| Loan | 2 | 1 | 1 | 2 | 6 |
| Asset | 2 | 1 | 1 | 2 | 6 |
| Approvals | 1 | 1 | 2 | 0 | ✅ |
| KB | 2 | — | 2 | 0 | ✅ |
| Reports | 6 | — | 0 | 6 | 5 |
| Notifications | 2 | 1 | 0 | 3 | 6 |
| Settings | 3 | 2 | 3 | 2 | ✅ |
| Email Templates | — | 8 | 0 | 8 | 7 |
| Shared Components | — | 12 | 3 | 9 | 7 |
| **Total ~100** | **~53** | **~40** | **~38** | **~62** | **7 hari** |

### Detail per Modul

Lihat `docs/planning/pages-masterplan.md` untuk listing lengkap per item. Ringkasan:

| Day | Modul | Halaman Baru | Kompleksitas |
|:---:|-------|:------------:|:------------:|
| 1 | Route Closures→Controllers + Branch Geofencing UI | 9 WebController + 1 Livewire | 🟡 Sedang — `route:cache` blocker |
| 2 | Payroll Admin UI (Finance) | 1 Livewire component (payroll-manager) | 🔴 Tinggi — generate/workflow/publish |
| 3 | Manager Team View | Tab integration di approvals existing | 🟡 Sedang — filter by team scope |
| 4 | Payroll Settings (Allowances, Deductions, PTKP, BPJS) | 4 page/modals | 🟡 Sedang — form heavy |
| 5 | Reports (Attendance, Payroll, PPh21, BPJS) + Dashboard Charts | 5 pages | 🟢 Rendah — tabel/chart |
| 6 | Notifications + Settings polish + Loan/Asset admin | 5 pages | 🟢 Rendah |
| 7 | Polish + Email + Cleanup + Security Headers | 8 email + cleanup | 🟢 Rendah |

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
| L-1 | **Mobile layout: bottom nav** | 5 tab gated per permission, Material Symbols, active state | ✅ |
| L-2 | **Mobile layout: TopAppBar** | Avatar + judul halaman + notif icon. `sticky top-0`. Hidden on desktop. | ✅ |
| L-3 | **Desktop layout: sidebar** | Custom flex sidebar, 12 items gated per permission | ✅ |
| L-4 | **Toast notification service** | Livewire dispatch `toast` event, SweetAlert2, fixed event name | ✅ |
| L-5 | **Responsive shell** | Mobile: bottom nav + header. Desktop: sidebar. Role-aware. | ✅ |
| L-6 | **PWA offline.html** | Halaman offline sederhana | ⏳ |
| L-7 | **PWA theme_color update** | Manifest value | ⏳ |

### ESS Livewire Components

| ID | Task | Sub-tasks | Status |
|----|------|-----------|:------:|
| **FE-1** | **Clock In/Out** ⭐ | — | ✅ |
| | 1a. Install face-api.js + model weights | ✅ model weights di `public/models/av1/` | ✅ |
| | 1b. Camera + face detection | static import face-api.js via app.js, `window.faceapi`, TinyFaceDetector default, SSD fallback | ✅ |
| | 1c. Face enrollment | Head-turn liveness (PasPapan), geometry descriptor, guide overlay, countdown 3-2-1, progress bar 1/4–4/4, arrow indicators | ✅ |
| | 1d. GPS locator + liveness | 3 sampel GPS (variance+speed) + micro-movement variance | ✅ |
| | 1e. Dynamic QR (bonus) | HMAC-SHA256 DynamicBarcodeTokenService | ✅ |
| | 1f. Camera card Blade | Bugs C-1..4 fixed, face_crop→photo_selfie fixed, is_mocked removed, gps_variance added | ✅ |
| | 1g. EAR blink detection | `computeEAR()` via landmarks, open→closed→open state machine, button disabled until liveness confirmed | ✅ |
| | 1h. Face crop | `extractFaces()` → `canvas.toBlob` → audit trail | ✅ |
| | 1i. API integration | ✅ fetch via `window.apiHeaders()` (Sanctum bearer) | ✅ |
| | 1j. Tests | + test fixes for FaceRecognitionService, AttendanceProofTest | ✅ |
| **FE-2** | **Attendance History** | Table + filters (month, status), responsive cards | ✅ |
| **FE-3** | **Leave (Apply + History + Quota)** | Form, quota cards, history table, Indonesian statuses | ✅ |
| **FE-4** | **Overtime (Apply + History)** | Form, history table | ✅ |
| **FE-5** | **Reimbursement (Request + History)** | Form, history table | ✅ |
| **FE-6** | **Payroll Slip** | Period list, PIN, earnings breakdown | ✅ |
| **FE-7** | **Profile & Devices** | Personal info (overhauled), section cards, PII gated, family, documents | ✅ |
| **FE-8** | **Missing Route Views** | — | ✅ |
| | 8a. Payroll landing page | ✅ | ✅ |
| | 8b. Approvals landing page | ✅ pending+history tabs | ✅ |
| | 8c. Knowledge Base landing page | ✅ chat AI + FAQ suggestions | ✅ |
| | 8d. Assets landing page | ✅ list | ✅ |
| | 8e. Loans landing page | ✅ list | ✅ |

### Inbox + RAG (4th Bottom Tab)

| ID | Task | Detail | Status |
|----|------|--------|:------:|
| IN-1 | **Inbox page** | Notifications + Pending Approvals | ✅ |
| IN-2 | **RAG chat** | AI SDK integration, SSE streaming, FAQ suggestions | ✅ |
| IN-3 | **Knowledge Base list** | Documents list + upload (HR only) | ✅ |

---

## 🎯 POST-STUDY PLAN — ~62 Blade Files Baru

| Round | Baru | Pages | Modal | Modul |
|:-----:|:----:|:-----:|:-----:|-------|
| **1** | 11 | 9 WebController + 1 Livewire | — | **Route closures → Controller + Branch Geo UI** — enable `route:cache` |
| **2** | 3 | 1 Livewire + 2 partial | 2 | **Payroll Admin** — generate, workflow, bulk publish/pay |
| **3** | 5 | 3 pages | 2 | **Payroll Settings** — allowances, deductions, PTKP, BPJS |
| **4** | 6 | 5 pages + 1 PDF | 1 | **Reports** — attendance, payroll, PPh21, BPJS, performance |
| **5** | 5 | 4 pages | 1 | **Notifications + Loan/Asset admin** — center, preferences, management |
| **6** | 12 | 8 email + 1 middleware | 3 | **Email + Cleanup** — templates, security headers |
| **Total** | **~42** | **~22 pages** | **~20 modal/partial** | **+~38 existing = ~80 Blade files** |

### Round 1 — Route Closures → Controller + Branch Geofencing UI

**Prioritas 1:** Buat 9 invokable WebController untuk enable `route:cache`. **Prioritas 2:** Branch Livewire component untuk geofencing config.

| Item | Tipe | Pattern |
|------|------|---------|
| `AttendanceWebController` | Invokable controller | `__invoke()` → `view('attendance.index')` |
| `OvertimeWebController` | Invokable controller | Sama |
| `ReimbursementWebController` | Invokable controller | Sama |
| `PayrollWebController` | Invokable controller | Sama |
| `LoanWebController` | Invokable controller | Sama |
| `AssetWebController` | Invokable controller | Sama |
| `ApprovalWebController` | Invokable controller | Sama |
| `EmployeeWebController` | Invokable controller | Sama (2 routes) |
| `KnowledgeBaseWebController` | Invokable controller | Sama (2 routes) |
| `BranchComponent` | Livewire + Blade | Modal CRUD, Leaflet map, lat/lng/radius fields |

### Round 2 — Payroll Admin UI (Finance)

| Item | Tipe | Notes |
|------|------|-------|
| `PayrollManagerComponent` | Livewire (full-page) | Summary cards, status filter, bulk publish/pay, detail modal |
| Payroll generate | Modal/form | Period → preview → generate (batch, via existing services) |
| Payroll detail admin | Modal | Employee breakdown per payroll period |
| Payroll status workflow | Badge/Pill | DRAFT→PUBLISHED→PAID |

### Round 3 — Payroll Settings + BPJS/Tax Config

| Item | Tipe | Notes |
|------|------|-------|
| Allowances CRUD | Page + modal | PasPapan pattern |
| Deductions CRUD | Page + modal | PasPapan pattern |
| PTKP/TER config | Page | Existing services, UI only |
| BPJS config | Page | Existing services, UI only |

### Round 4 — Reports + Dashboard Charts

| Item | Tipe |
|------|------|
| Attendance recap + PDF | Page |
| Payroll financial report | Page |
| PPh21 report | Page |
| BPJS report | Page |
| Performance report | Page |

### Round 5 — Notifications + Polish

| Item | Tipe |
|------|------|
| Notification center | Page |
| Notification preferences | Page |
| Loan admin view | Page |
| Asset admin view | Page |

### Round 6 — Email + Cleanup

| Item | Tipe |
|------|------|
| Email templates (8) | leave approved/rejected, overtime, reimbursement, account created |
| Security headers middleware | CSP, HSTS, X-Frame-Options |

### Day Items Removed — Migrated to Round-Based Plan Above

(Day 2-7 daily breakdowns removed. Lihat Round 1-6 di atas untuk prioritas baru berdasarkan studi repo.)

---

## Project Stats

| Metric | Value |
|--------|-------|
| App PHP files | 210+ |
| App LOC | ~14,500 |
| Models | 34 |
| API endpoints | 80+ at `/api/v1` |
| Tests | 1,173 / 4,128 assertions (SQLite) + ~28 (PG) |
| JS files | 18 (14 extracted Alpine.data + app.js + 3 utilities) |
| Sesi sebelumnya | **A+B+C+D+E **✅** (44 item) |
| **Sesi F** | **22+ items** — Face, Employee, Auth, UX, Route gating |
| **Functional pages ✅** | **~35** (auth 7 + settings 3 + ESS 8 + approvals 1 + KB 2 + attendance 2 + face 1 + payroll 2 + dashboard 1 + employee 2 + overtime 2 + reimbursements 2 + loans 1 + assets 1) |
| **Components/layouts ✅** | **~11** |
| **Total existing Blade** | **~65** (including vendor, partials, modals) |
| **Target pages baru** | **~20** (dari ~55 target - 35 existing) |
| **Target Blade total** | **~100** (55 pages + 15 components + 8 layouts/partials + 8 email + 4 vendor + 10 profile/auth modals) |
| Reference repos studied | 5 (PasPapan, Quanta HRIS, Laravel-Smarthr, HRMS, hris) |

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

## 🧹 PHASE CLEANUP: Dead Code — Deferred (post-skripsi)

| ID | Task | Detail | Priority | Status |
|----|------|--------|:--------:|:------:|
| **DC-1** | **Hapus GeofenceValidation middleware** | 0 caller, Haversine duplikat dari GeofenceService | 🟢 | ⏳ |
| **DC-2** | **Hapus Branch::validateRadius()** | 0 caller, Haversine ke-3 | 🟢 | ⏳ |
| **DC-3** | **Satukan anti-fake-GPS check** | Hanya di GeofenceService, hapus dari AttendanceService | 🟢 | ⏳ |
| **DC-4** | **PHPStan baseline** | Hapus 7 entry deleted notifications | 🟢 | ⏳ |
| **DC-5** | **Merge WfaStatus → ApprovalStatus** | WfaStatus isinya identik (PENDING/APPROVED/REJECTED). Hapus enum, ganti semua import. 9 file. | 🟢 | ✅ |

## 🎯 POST-STUDY STRATEGY (2026-07-01)

### Goal
Selesaikan **~62 Blade files** prioritas berdasarkan studi 5 repo referensi. Backend 100% ready (80+ endpoints, 1,173 tests). Reference patterns sudah dipelajari.

**Key decisions from repo study:**
- **Route→Controller:** 9 invokable WebController (PasPapan pattern) — enable `route:cache`
- **Branch Geo UI:** Livewire `BranchComponent` + Leaflet map — PasPapan master data pattern
- **Payroll Admin:** Livewire `PayrollManagerComponent` — PasPapan UI + Quanta batch generate
- **Manager View:** Integrated into existing approvals (not separate route group) — PasPapan pattern
- **No Filament** — semua Livewire SFC
- **No `manager/` route prefix** — cukup scope filter

### Strategy
1. **Round 1**: Route closures → 9 WebController + Branch Livewire component. Enable `route:cache` first.
2. **Round 2**: Payroll Admin UI — Finance needs generate, review, publish workflow.
3. **Round 3**: Payroll Settings — Allowances, Deductions, PTKP/TER, BPJS configs.
4. **Round 4**: Reports — financial, tax, attendance performance.
5. **Round 5**: Notifications, Loan/Asset admin pages.
6. **Round 6**: Email templates (8) + Security headers middleware.

### Prinsip
| Prinsip | Detail |
|---------|--------|
| **PasPapan patterns** | Semua pola UI dari PasPapan (master data, payroll manager, approval inbox) |
| **Quanta logic** | Payroll batch generation via existing services (BpjsService, Pph21Service, dll) |
| **No closures** | Route files harus zero closure untuk `route:cache` |
| **Modal for CRUD** | Tidak pernah bikin page terpisah untuk create/edit |
| **No custom CSS** | Hanya utility classes + DESIGN.md tokens |
| **Backend first** | Semua data dari API endpoint existing — tidak perlu backend change |

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
| **F (Overhaul)** | **✅** | **22+ items** — Face rewrite (PasPapan pattern), Employee index/show overhaul, Sidebar/BottomNav gating, Route middleware, Dashboard split, API 401 auth fix, UX all fixed, Reference repos studied |
| **Selesai** | **✅ A+B+C+D+E+F** | **66+ items** |
| **Next: P1 route closures + Branch Geo UI + Payroll Admin** | **🚧** | |

---

### Status Perubahan Kode — Sudah Selesai

#### ✅ Sesi F — Face, Employee, Auth, UX Overhaul (22+ items) — COMPLETED (2026-07-01)

| ID | Task | File | Status |
|:--:|------|------|:------:|
| F1/FC13 | Fix face-api.js import — static import via app.js | `app.js`, `face-registration.blade.php`, `clock-in.blade.php` | ✅ |
| F2/FC2 | Fix `face_crop` → `photo_selfie` di clock-in | `clock-in.blade.php` | ✅ |
| F3/FC1 | Tambah `gps_variance` ke ClockInRequest rules | `ClockInRequest.php` | ✅ |
| F4/FC3 | Hapus hardcode `is_mocked: false` | `clock-in.blade.php` | ✅ |
| FC7/FC8 | FaceController `updateOrCreate` + `is_active=true` | `FaceController.php` | ✅ |
| FC4/FC12 | Head-turn liveness challenge (PasPapan yaw score) | `face-registration.blade.php` | ✅ |
| FC5 | EAR blink detection state machine | `clock-in.blade.php` | ✅ |
| H10 | Geometry descriptor (129 float) | `face-registration.blade.php` | ✅ |
| C7 | ApprovalPolicy — view/approve/reject | `app/Policies/ApprovalPolicy.php` | ✅ |
| C1 | AssetController SQL driver-aware | `AssetController.php` | ✅ |
| R1 | Sidebar gating 12 items per permission | `sidebar.blade.php` | ✅ |
| R2 | Bottom nav gating per permission | `bottom-nav.blade.php` | ✅ |
| R6/R7/R8 | Route `can:` middleware di 8 route files | `routes/*.php` | ✅ |
| R9 | Dashboard role-specific (admin vs employee) | `DashboardController.php`, `dashboard.blade.php` | ✅ |
| R10 | Employee index rewrite | `employee/index.blade.php`, `employees-index.js` | ✅ |
| R11 | Employee show rewrite | `employee/show.blade.php` | ✅ |
| UX-1..9 | Semua UX issues fixed | Various | ✅ |
| API-401 | `window.apiHeaders()` — 42 fetch calls | `app.js` + 15 JS files | ✅ |
| JS-toast | 9 silent catch blocks → toast errors | Various | ✅ |
| LAN-ID | Language ID — 80+ keys, Indonesian labels | `lang/id.json`, various blades | ✅ |
| PHOTO | `photo_url` di EmployeeResource | `EmployeeResource.php` | ✅ |
| DB-SSL | DB_SSLMODE revert to `prefer` | `.env` | ✅ |

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

#### ✅ Sesi C — RAG Knowledge Base UI (3 item) — COMPLETED
| ID | Task | Detail | Files | Status |
|:--:|------|--------|-------|:------:|
| **RAG-1** | **Chat AI (halaman utama)** | Livewire minimal + Alpine.js SSE streaming. Pola dari `ship-ai-with-laravel`: `fetch POST /chat-stream` → `ReadableStream.getReader()` → parse `data: {"text":"..."}` events. Auto-resize textarea, Enter/Shift+Enter, typing indicator 3 bouncing dots, suggestion buttons (FAQ cuti/BPJS/jam kerja), `formatMessage()` bold/bullet/newline. | `app/Livewire/KnowledgeBaseChat.php`, `resources/views/livewire/knowledge-base-chat.blade.php`, `resources/views/knowledge-base/index.blade.php`, `app/Http/Controllers/Api/KnowledgeBaseController.php`, `app/Services/KnowledgeBaseService.php`, `routes/api.php` | ✅ |
| **RAG-2** | **Upload PDF (HR only)** | Form upload + document list table. `GET /knowledge-base/manage` — middleware `can:manage_knowledgebase`. | `resources/views/knowledge-base/manage.blade.php` | ✅ |
| **RAG-3** | **Tests** | Streaming endpoint, auth/permission/throttle, service layer. | `tests/Feature/Api/KnowledgeBaseEndpointTest.php`, `tests/Feature/Api/KnowledgeBaseProofTest.php`, `tests/Feature/Services/KnowledgeBaseServiceTest.php` | ✅ |

#### ✅ Sesi D — ESS Pages — COMPLETED
| ID | Task | View Baru | Status |
|:--:|------|-----------|:------:|
| FE-2 | Attendance History | `resources/views/attendance/index.blade.php` (upgrade) | ✅ |
| FE-3a | Leave Apply | `resources/views/leaves/apply.blade.php` | ✅ |
| FE-3b | Leave History | `resources/views/leaves/index.blade.php` | ✅ |
| FE-3c | Leave Quota | `resources/views/leaves/apply.blade.php` (inline) | ✅ |
| FE-4 | Overtime (Apply + History) | `resources/views/overtimes/apply.blade.php` + `index.blade.php` | ✅ |
| FE-5 | Reimbursement (Request + History) | `resources/views/reimbursements/index.blade.php` + `apply.blade.php` | ✅ |
| FE-6 | Payslip (PIN + download) | `resources/views/payroll/payslip.blade.php` (upgrade) | ✅ |
| FE-7 | Profile & Devices | `resources/views/employee/profile/*.blade.php` | ✅ |
| FE-8a | Approvals landing page | `resources/views/approvals/index.blade.php` | ✅ |
| FE-8b | Knowledge Base landing page | `resources/views/knowledge-base/index.blade.php` | ✅ |

#### ✅ Sesi E — Approvals — COMPLETED
| ID | Task | View Baru | Status |
|:--:|------|-----------|:------:|
| AP-1 | Approvals landing + pending tab | `resources/views/approvals/index.blade.php` | ✅ |
| AP-2 | Approvals history tab | `resources/views/approvals/index.blade.php` (same file, tabbed) | ✅ |
| AP-3 | Detail modal | `resources/views/approvals/index.blade.php` (inline modal) | ✅ |

#### Sesi F — Face, Employee, Auth, UX Overhaul — COMPLETED ✅
(Semua item Sesi F sudah selesai di sesi 2026-07-01. Lihat ringkasan di header.)

#### 🟢 Sesi G — Post-Study Cleanup + Security Headers (deferred)
| ID | Task | Detail |
|:--:|------|--------|
| DC-1 | Hapus GeofenceValidation middleware | 0 caller, Haversine duplikat |
| DC-2 | Hapus Branch::validateRadius() | 0 caller, Haversine ke-3 |
| DC-3 | Satukan anti-fake-GPS check | Hanya di GeofenceService |
| DC-4 | PHPStan baseline — hapus 7 entry | `phpstan-baseline.neon` |
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

## 🔴 Reference Repo Study — Key Architectural Decisions (2026-07-01)

Hasil studi mendalam 5 repo referensi untuk pola arsitektur yang mempengaruhi 6 item selanjutnya.

### Route → Controller Pattern Comparison

| Repo | Pattern | Cocok untuk HRConnect? |
|------|---------|:----------------------:|
| **PasPapan** | `__invoke()` controller → `view()` → Livewire component | ✅ **Primary reference** |
| Quanta HRIS | `[Controller::class, 'method']` — thin controllers for PDF | ✅ PDF export pattern |
| HRMS | `Route::get('/x', LivewireComponent::class)` langsung | ❌ Terlalu langsung, skip middleware |
| Laravel-Smarthr | `[Controller::class, 'method']` — thick controllers | ❌ Logic di controller (not service layer) |
| hris | Inertia: `[Controller::class, 'method']` + closure | ❌ Beda stack (Inertia) |

**Keputusan:** Buat **9 invokable WebController** (PasPapan pattern). Setiap controller: `return view('feature.index')`. Minimal, testable, `route:cache` compatible.

### Master Data CRUD Pattern Comparison

| Repo | Approach | Branch Fields |
|------|----------|---------------|
| **PasPapan** | Livewire component modal CRUD: search, pagination, cards+table responsive | Divisions, Job Titles, Shifts |
| **Quanta** | Filament `CabangResource`: nama, alamat, latitude, longitude, radius | `radius_lokasi` in meters |
| hris | Full CRUD + import/export Excel, org tree (Company→Branch→Division→...) | Same + import/export |

**Keputusan:** Livewire `BranchComponent` (PasPapan pattern) + Leaflet map. Fields: `nama`, `alamat`, `latitude`, `longitude`, `radius` (meter).

### Payroll Admin UI Pattern Comparison

| Repo | Approach | Workflow |
|------|----------|----------|
| **PasPapan** | `PayrollManager` Livewire: summary cards, status filter, bulk publish/pay, detail modal | DRAFT→PUBLISH→PAID |
| **Quanta** | Batch generate per period, 4-level approval (Staff HRD→Manager HRD→Manager Finance→Payment), individual salary edit | Draf→Diajukan→Diverifikasi→Disetujui→Ditolak |

**Keputusan:** Gabung PasPapan UI pattern (Livewire summary cards, bulk actions, detail modal) + Quanta generation logic (batch per period via services). Workflow HRConnect existing: DRAFT→PUBLISHED→PAID.

### Manager View Pattern Comparison

| Repo | Approach | Kesimpulan |
|------|----------|------------|
| **PasPapan** | Manager Inbox **integrasi ke admin panel** via tabbed interface | ✅ Best practice — tidak perlu route group terpisah |
| Quanta | Role-based visibility on actions (not separate views) | ✅ Sama dengan PasPapan |
| HRMS | Role middleware `['role:Admin\|HR\|CC']` | ❌ Hardcoded role check |

**Keputusan:** Ikut PasPapan — jangan buat route group `manager/` baru. Cukup tambah scope filter di approval existing + gate `can:approve_*`.

### Design Constraints (Confirmed)

| Aspek | Keputusan |
|:------|:----------|
| **CSS/Warna** | MD3 tokens (`bg-canvas`, `text-ink`, `rounded-xl`) — **bukan** copy PasPapan (hijau/cream) |
| **UX Pattern** | Ambil dari PasPapan (face enrollment guide overlay, liveness, approval cards) |
| **Payroll Indonesia** | Already in services layer (BpjsService, Pph21Service, LemburService, dll) |
| **Layout** | `x-layouts::app.sidebar` — tidak berubah |
| **Route prefix** | Tidak perlu `admin.*` rename — cukup `can:` middleware |
| **Font** | Rubik 500 (display) + Inter (body) — jangan pakai font lain |

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

## Execution Order — Prioritised (Post-Reference-Repo Study)

```
SESI A ✅ — Auth & Permission + Security + Business Logic (14 items)
SESI B ✅ — Face Recognition + GPS Anti-Spoofing + Liveness (16 items)
SESI C ✅ — RAG Knowledge Base UI (3 items)
SESI D ✅ — ESS Pages (8 items)
SESI E ✅ — Approvals (3 items)
SESI F ✅ — Face rewrite, Employee overhaul, Sidebar/BottomNav gating, Route middleware, 
            Dashboard split, API 401 fix, UX-1..9, Language ID, Reference repos studied (22+ items)
  ═══════════════════════════════════════════════════
  NOW: Prioritised Next Steps (Post-Study)
  ═══════════════════════════════════════════════════
ROUND 1 — Route Closures→Controller (9 WebControllers) + Branch Geofencing UI
ROUND 2 — Payroll Admin UI (Finance) + Manager Inbox integration
ROUND 3 — Payroll Settings (Allowances, Deductions, PTKP, BPJS)
ROUND 4 — Reports (Attendance, Payroll, PPh21, BPJS) + Dashboard Charts
ROUND 5 — Notifications + Settings polish + Loan/Asset admin
ROUND 6 — Polish + Email + Cleanup + Security Headers
  ═══════════════════════════════════════════════════
```

### Catatan Kunci Eksekusi
- **Sesi A+B+C+D+E+F ✅** — Semua selesai. Backend 100% ready (80+ endpoints, 1,173 tests, 4,128 assertions).
- **Route closures dulu** — 15 closures di 9 route files blokir `route:cache`. Buat 9 invokable WebController (PasPapan pattern).
- **Branch Geo dulu** — HR-Manager perlu atur lat/lng/radius cabang untuk geofencing. Livewire component + Leaflet.
- **Payroll Admin** — Finance butuh generate payroll, review draft, publish, pay. Pola dari PasPapan `PayrollManager` + Quanta `HitungGajiService`.
- **Manager View** — Integrasi ke approval existing (tab), bukan route group terpisah (PasPapan pattern).
- **Form modal pattern** — Semua create/edit pakai modal, bukan page terpisah.
- **Gmail SMTP (EV-2)** masih deferred — `MAIL_MAILER=log`.
- **Decisions from repo study:**
  - **No Filament** — semua Livewire SFC (HRConnect pattern)
  - **No separate `manager/` route group** — integrasi ke admin panel (PasPapan pattern)
  - **PasPapan = primary reference** untuk UI/UX patterns
  - **Quanta = secondary** untuk payroll Indonesia logic (sudah ada di services layer)
  - **Geometry descriptor (129 float)** untuk face registration (PasPapan), FaceNet (128D) untuk clock-in
- **~100 Blade files target** — ~38 existing + ~62 baru.

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

Temuan: **Semua role mendapatkan UI yang identik.** Single layout untuk HR-Manager, Finance, Manager, dan Employee — tidak ada perbedaan view, sidebar, atau bottom nav antar role.

## Root Cause

| Aspek | Detail |
|-------|--------|
| **Layout** | Semua view pakai `x-layouts::app.sidebar` — tidak ada perbedaan untuk admin vs user |
| **Sidebar** | 8 menu, hanya 1 item (`Karyawan`) digate dengan `@can('viewAny', Employee::class)`. Employee lihat Payroll, Approvals, dll — padahal tidak relevan |
| **Bottom nav** | `components/bottom-nav.blade.php` — **zero role gating**. 5 tab hardcoded. Employee lihat tab Payroll |
| **Halaman** | Satu Blade per modul, tidak ada split admin/employee. Finance lihat halaman payroll yang sama dengan Employee (hanya payslip sendiri) |
| **Routes** | Hanya 3 dari 18 route punya `can:` middleware — sisanya akses terbuka ke semua role |
| **Dashboard** | `DashboardController` partial gate via `hasRole()`, tapi QuickActions dan navigasi tidak dibedakan |

## Permission Matrix — 44 Permissions, 5 Roles

### Roles & Permission Assignments

| Module | super-admin | hr-manager | finance | manager | employee |
|--------|:-----------:|:----------:|:-------:|:-------:|:--------:|
| Dashboard | ALL | view_dashboard | view_dashboard | view_dashboard | view_dashboard |
| Companies | ALL | -- | -- | -- | -- |
| Branches | ALL | view_branches | -- | -- | -- |
| Departments | ALL | view_departments | -- | -- | -- |
| Positions | ALL | view_positions | -- | -- | -- |
| Employees | ALL | view + manage | view | view | -- |
| Attendance | ALL | view + manage | view | view | view |
| Leaves | ALL | view + approve_l2 | -- | view + approve_l1 | view |
| Overtimes | ALL | view + approve_l2 | -- | view + approve_l1 | view |
| Reimbursements | ALL | view | view + manage + approve_l2 | view + approve_l1 | view |
| WFA | ALL | approve + view_pending | -- | approve + view_pending | -- |
| Loans | ALL | view + manage | view + manage | -- | view |
| Assets | ALL | view + manage | -- | -- | view |
| Payroll | ALL | -- | view_payslip, download_payslip, process_payroll, view_payrolls, manage_tax, manage_bpjs | -- | view_payslip, download_payslip, view_payrolls |
| Audit | ALL | view_logs | -- | -- | -- |
| Settings | ALL | -- | -- | -- | -- |
| KnowledgeBase | ALL | view + manage | -- | -- | view |

## Temuan Spesifik

| ID | Issue | Dampak |
|:--:|-------|--------|
| **R1** | Sidebar item sama untuk semua role — hanya Karyawan digate | Staff lihat menu Payroll, Approvals yang tidak relevan |
| **R2** | Bottom nav tanpa gate — 5 tab hardcoded | Mobile navigation expose Payroll ke employee |
| **R3** | QuickActions sudah partial fix (approvals, dokumen) | Tapi 8 action lainnya masih sama untuk semua role |
| **R4** | Tidak ada `admin-ui` vs `user-ui` body class | CSS targeting per role tidak mungkin |
| **R5** | Tidak ada admin route prefix pattern | `admin.*` route names tidak bisa di-detect di layout |
| **R6** | Route-level `can:` middleware hanya di 3/18 route | User bisa akses URL halaman manapun tanpa gate |
| **R7** | Finance tidak punya view payroll management | Payroll process, tax config, BPJS config hanya via API — tidak ada UI web |
| **R8** | Manager tidak punya view team attendance/leaves | Manager lihat data sendiri, bukan timnya — tidak bisa approve |
| **R9** | HR-Manager lihat halaman cuti/lembur yang sama dengan employee | Harusnya ada overview semua karyawan untuk L2 approval |
| **R10** | Employee bisa akses `/approvals` — halaman persetujuan (walau data kosong) | Route tidak digate, Livewire component mungkin handle di level data |

## Per Module — What Each Role Sees vs Should See

### HR-MANAGER

| Module | Should See | Currently Sees |
|--------|-----------|---------------|
| Dashboard | Company-wide stats + attendance charts + payroll summary | Company-wide stats only (no charts/payroll) |
| Employees | Full CRUD, all employees, PII visible | ✅ Correct |
| Attendance | All employees' attendance, exception management | Own attendance only ❌ |
| Leaves | All employees' leaves, L2 approval | Own leaves only ❌ |
| Overtimes | All employees' overtime, L2 approval | Own overtime only ❌ |
| Reimbursements | View all (no manage) | Own reimbursements only ❌ |
| Payroll | No access (no permission `view_payrolls`) | Own payslips ❌ (shouldn't see) |
| Approvals | Pending L2 approvals across all modules | Same page as everyone |
| Loans | View + Manage all loans | Own loans only ❌ |
| Assets | View + Manage all assets | Own assets only ❌ |
| Knowledge Base | View + Manage documents | ✅ Correct (manage gated) |

### FINANCE

| Module | Should See | Currently Sees |
|--------|-----------|---------------|
| Dashboard | Payroll-focused stats + next payroll date | Company-wide stats (no finance focus) |
| Employees | View only (for payroll context) | ✅ Via EmployeePolicy |
| Attendance | View only (for payroll calc) | Own attendance only ❌ |
| Leaves | No access | Own leaves ❌ (shouldn't see) |
| Overtimes | No access | Own overtime ❌ (shouldn't see) |
| Reimbursements | **ALL reimbursements** + manage + L2 approve | Own reimbursements only ❌ |
| Payroll | **Full management**: process, tax configs, BPJS configs, all payslips | Own payslips only ❌ |
| Approvals | Reimbursement L2 approvals | Same page as everyone |
| Loans | View + Manage all loans | Own loans only ❌ |
| Assets | No access | Own assets ❌ (shouldn't see) |

### MANAGER

| Module | Should See | Currently Sees |
|--------|-----------|---------------|
| Dashboard | Team stats + team pending approvals | Company-wide stats (pending shows ALL, not just team) |
| Employees | Team members only (via parent_id) | All employees (via EmployeePolicy — by company) |
| Attendance | Team attendance + monitoring | Own attendance only ❌ |
| Leaves | Team leaves + L1 approve | Own leaves only ❌ |
| Overtimes | Team overtime + L1 approve | Own overtime only ❌ |
| Reimbursements | Team reimbursements + L1 approve | Own reimbursements only ❌ |
| Payroll | No access | Own payslips ❌ (shouldn't see) |
| Approvals | Pending L1 team requests | Same page as everyone |
| Loans | No access | Own loans ❌ (shouldn't see) |
| Assets | No access | Own assets ❌ (shouldn't see) |

### EMPLOYEE

| Module | Should See | Currently Sees |
|--------|-----------|---------------|
| Dashboard | Own stats only | ✅ Correct |
| Employees | Own profile only | ✅ Via EmployeePolicy |
| Attendance | Own attendance + clock-in/out | ✅ Correct |
| Leaves | Own leaves + apply | ✅ Correct |
| Overtimes | Own overtime + apply | ✅ Correct |
| Reimbursements | Own claims + apply | ✅ Correct |
| Payroll | Own payslips (view + download) | ✅ Correct |
| Approvals | No access ❌ | Accesses page (data empty) |
| Loans | View own | ✅ Correct |
| Assets | View assigned | ✅ Correct |

## Web Routes — Gate Status

| Route | Name | `can:` Middleware | Role Access |
|-------|------|:-----------------:|-------------|
| `GET /dashboard` | `dashboard` | ❌ | ALL (fine) |
| `GET /admin/employees` | `admin.employees.index` | ✅ `viewAny,Employee` | HR/Manager/Finance |
| `GET /admin/employees/{id}` | `admin.employees.show` | ✅ `view,employee` | HR/Manager/Finance |
| `GET /attendance` | `attendance.index` | ❌ | ALL |
| `GET /attendance/clock-in` | `attendance.clock-in` | ❌ | ALL |
| `GET /attendance/face-registration` | `attendance.face-registration` | ❌ | ALL |
| `GET /leaves` | `leaves.index` | ❌ | ALL |
| `GET /leaves/apply` | `leaves.apply` | ❌ | ALL |
| `GET /overtimes` | `overtimes.index` | ❌ | ALL |
| `GET /overtimes/apply` | `overtimes.apply` | ❌ | ALL |
| `GET /reimbursements` | `reimbursements.index` | ❌ | ALL |
| `GET /reimbursements/apply` | `reimbursements.apply` | ❌ | ALL |
| `GET /payroll` | `payroll.index` | ❌ | ALL |
| `GET /approvals` | `approvals.index` | ❌ | ALL |
| `GET /knowledge-base` | `knowledge-base.index` | ❌ | ALL |
| `GET /knowledge-base/manage` | `knowledge-base.manage` | ✅ `manage_knowledgebase` | HR/SA only |
| `GET /loans` | `loans.index` | ❌ | ALL |
| `GET /assets` | `assets.index` | ❌ | ALL |
| `GET /settings/profile` | `profile.edit` | ❌ (missing `verified`) | ALL |
| `GET /settings/security` | `security.edit` | ✅ `verified` | ALL |
| `GET /settings/appearance` | `appearance.edit` | ✅ `verified` | ALL |

## Pattern Fix (Dari PasPapan)

```
Detect route name di layout:
- admin.* route prefix → body class `admin-ui` → sidebar penuh
- employee.* atau user.* → body class `user-ui` → sidebar minimal + bottom nav

Route naming:
- admin.employees.index, admin.payroll.index → HR/Finance
- employee.attendance.index, employee.leave.index → Staff

Sidebar gating:
- @can('view_employees') → Karyawan (HR/Manager/Finance)
- @can('view_payrolls') → Payroll Management (Finance)
- @can('approve_*_l1') → Persetujuan (Manager)
- @can('manage_assets') → Aset Management (HR)
- @can('view_knowledgebase') → Knowledge Base (ALL)
- @can('manage_knowledgebase') → Kelola Dokumen (HR)

Bottom nav employee-only:
- Dasbor, Absen, Pengajuan (Cuti/Lembur/Klaim), Profil
- Tanpa Payroll, tanpa tab admin
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

Berdasarkan semua temuan + referensi pola dari semua repo (PasPapan, Quanta HRIS, Laravel-Smarthr, HRMS, hris).

**Arsitektur keputusan setelah studi repo:**
- **Route closures → WebController invokable** (PasPapan pattern) — zero closures, enable `route:cache`
- **Branch Geofencing UI** → Livewire component `BranchComponent` (PasPapan master data pattern) + Leaflet map
- **Payroll Admin** → Livewire `PayrollManagerComponent` (PasPapan UI pattern) + batch generate (Quanta HRIS logic)
- **Manager team view** → Integrated into existing approvals (PasPapan pattern) — NOT separate route group
- **No Filament** — semua Livewire SFC

### ✅ Selesai di Sesi F (22+ items)

| Tier | ID | Task | Status |
|:----:|:--:|------|:------:|
| **P0** | **F1/FC13** | **Fix face-api.js import** — static import via app.js → `window.faceapi` | ✅ |
| **P0** | **F2/FC2** | **Fix `face_crop` → `photo_selfie`** | ✅ |
| **P0** | **F3/FC1** | **Tambah `gps_variance`** ke ClockInRequest rules | ✅ |
| **P0** | **F4/FC3** | **Fix hardcoded `is_mocked: false`** | ✅ |
| **P1** | **FC7/FC8** | **Single storage FaceDescriptor** — `updateOrCreate` + `is_active=true` | ✅ |
| **P1** | **FC4/FC12** | **Head-turn liveness** — PasPapan yaw score, challenge steps | ✅ |
| **P1** | **FC5** | **EAR blink detection** — state machine open→closed→open | ✅ |
| **P1** | **C7** | **Buat ApprovalPolicy** — view/approve/reject with permission mapping | ✅ |
| **P1** | **H10** | **Face descriptor geometry-based** (129 float) | ✅ |
| **P1** | **R1** | **Sidebar gating per permission** — 12 items gated, SDM section | ✅ |
| **P1** | **R2** | **Bottom nav gating** — Payroll hidden from employee, Pengajuan composite gate | ✅ |
| **P1** | **R6/R7/R8** | **Route middleware permission** — `can:` middleware di 8 route files | ✅ |
| **P1** | **R9** | **Dashboard role-specific widgets** — admin company stats vs employee personal stats | ✅ |
| **P1** | **APP_DEBUG** | Set `APP_DEBUG=false` | ✅ |
| **P1** | **SESSION_SECURE** | Set `SESSION_SECURE_COOKIE=true` | ✅ |
| **P2** | **R10** | **Employee index rewrite** — summary bar, avatar, badge tone, ID labels, mobile cards | ✅ |
| **P2** | **R11** | **Employee show rewrite** — section cards, info grid, PII gate, family, documents | ✅ |
| **P2** | **UX-1..9** | **Semua UX issues** — titles, bahasa, countdown, arrows, progress bar, post-success nav | ✅ |
| **P2** | **API 401 fix** | `window.apiHeaders()` global — 42 fetch calls across 15 JS files | ✅ |
| **P2** | **JS catch→toast** | 9 silent catch blocks → toast errors | ✅ |
| **P2** | **AssetController** | SQL `ilike` → driver-aware operator (ILIKE/LIKE) | ✅ |
| **P1** | **DB_SSLMODE** | Revert to `prefer` (production-only) | ✅ |

### ⏳ Belum Dikerjakan (Next)

| Tier | ID | Task | Pola dari Repo | File | Effort |
|:----:|:--:|------|:---------------|------|:------:|
| **P1** | **H1** | **Route closures → 9 WebControllers** — 15 closures di 9 route files blokir `route:cache`. Buat `AttendanceWebController`, `OvertimeWebController`, `ReimbursementWebController`, `PayrollWebController`, `LoanWebController`, `AssetWebController`, `ApprovalWebController`, `EmployeeWebController`, `KnowledgeBaseWebController`. | PasPapan: `__invoke()` → `return view('...')` | `routes/*.php`, `app/Http/Controllers/Web/*.php` | 30 menit |
| **P1** | **Branch Geo** | **Branch geofencing UI** — CRUD branch dengan lat/lng/radius untuk HR-Manager. Livewire `BranchComponent` + Leaflet map picker. API controller sudah ada, UI tidak. | PasPapan: `DivisionComponent` (modal CRUD, search, pagination, responsive). Quanta: `CabangResource` fields. | `resources/views/livewire/branch-component.blade.php`, `app/Livewire/BranchComponent.php` | 2 jam |
| **P2** | **Payroll Admin** | **Finance payroll management UI** — Livewire `PayrollManagerComponent`. Generate per period, status workflow DRAFT→PUBLISH→PAID, summary cards, bulk actions, detail modal. | PasPapan: `PayrollManager` Livewire (summary cards, status filter, bulk publish/pay). Quanta: batch generate via `HitungGajiService`. | `resources/views/payroll/admin-index.blade.php`, `app/Livewire/PayrollManagerComponent.php` | 3 jam |
| **P2** | **Manager View** | **Manager team monitoring** — tab/filter di approval existing untuk L1 approvals by team scope. Bukan route group terpisah. | PasPapan: Manager Inbox integrated into admin panel (tabbed). | `resources/views/approvals/index.blade.php` (upgrade) | 1 jam |
| **P2** | **H2** | **Tambah `verified` middleware** ke `profile.edit` route | Simple add | `routes/settings.php` | 1 menit |
| **P3** | **FC11** | **Hapus `captures` dari payload** — 6 base64 JPEG dikirim percuma | Hapus field dari Request + Controller + Blade | `FaceController.php`, `RegisterFaceRequest.php`, `face-registration.blade.php` | 5 menit |
| **P3** | **FC6** | **Pindah descriptor compute** ke saat clock-in (1x), bukan tiap frame (300ms) | PasPapan: compute only on verify trigger | `clock-in.blade.php` | 10 menit |
| **P3** | **C1** | **SQL LIKE escape** — wildcard `%` + `_` tidak di-escape | Prepared statement | `EmployeeController`, `AssetController` | 10 menit |
| **P3** | **Security headers** | **Buat `EnsureSecurityHeaders` middleware** — CSP, HSTS, X-Frame-Options | PasPapan: dedicated middleware | Middleware baru + `bootstrap/app.php` | 30 menit |
| **P3** | **H3-H9, M1-M10, L1-L8** | Perbaikan kualitas kode lainnya | Various | Variatif | Variatif |

# 🔴 Employee UI Audit — Perbandingan dengan Referensi (1 Juli 2026)

Hasil studi 3 repo referensi (PasPapan, Laravel-Smarthr, HRMS) untuk pola UI employee index dan detail.

## Gap Analysis — Employee Index

| Aspek | HRConnect (Sekarang) | PasPapan (Target) | Priority |
|-------|---------------------|-------------------|:--------:|
| **Avatar/Photo** | Inisial saja di `rounded-full bg-surface-dim` | Foto `object-cover h-12 w-12 rounded-full` + ring emerald | **HIGH** |
| **Summary bar** | Tidak ada | Stat cards: Total, Active, Filtered count — `rounded-lg border-emerald-200` | **HIGH** |
| **Mobile cards** | Datar putih polos, info minimal | Gradient `from-emerald-50/80 via-white`, 2-col info grid, 3-col action buttons | **MEDIUM** |
| **Status badge** | Inline ternary `:class` | Enum `employmentStatusTone()` → reusable `<x-admin.status-badge>` dengan 7 tone | **HIGH** |
| **Bahasa** | Campur Inggris (Employees, Add Employee, Export, Search...) | Indonesia semua | **HIGH** |
| **Filter dropdown** | Plain `<select>` tanpa styling | TomSelect `wire:model.live` dengan placeholder | **LOW** |
| **Empty state** | Text biasa "No employees found" | Centered card `rounded-xl border p-3 text-center` + icon + actions | **MEDIUM** |
| **Desktop table** | Basic table, hanya 1 baris action | 4-column table: Employee (avatar+name+email+status), Role & Unit, Contact, Actions | **MEDIUM** |
| **Page title** | "Employees" + "Manage employee master data" (EN) | "Manajemen Karyawan" + deskripsi Indonesia | **HIGH** |
| **Pagination** | Basic prev/next buttons | `border-t bg-gray-50 px-4 py-2.5` + `$users->links()` | **LOW** |
| **Card grid view** | Grid view ada tapi polos, action buttons di luar | Card dengan avatar, nama, posisi, department, status badge, action inline | **MEDIUM** |

## Gap Analysis — Employee Detail (Show)

| Aspek | HRConnect (Sekarang) | PasPapan (Target) | Priority |
|-------|---------------------|-------------------|:--------:|
| **Header** | Inisial, nama, status badge polos | Avatar `rounded-xl h-16 w-16`, nama + email, badge strip (status + jabatan + divisi) | **HIGH** |
| **Quick info** | Tabel grid sederhana | 3-col grid `rounded-xl border px-4 py-3` cards: NIP / Phone / Education | **MEDIUM** |
| **Tabs** | Text-only, border-bottom tipis | Tab dengan pill style atau section cards, bukan tabs | **LOW** |
| **Section cards** | Langsung label-value pair di grid | `rounded-xl border border-slate-200 bg-white p-4` per section dengan judul | **HIGH** |
| **Professional card** | Tersebar di personal tab | Card terpisah: jabatan, divisi, atasan, pendidikan, rate | **MEDIUM** |
| **Personal card** | Tersebar | Card: gender, birth place, birth date, phone, address | **MEDIUM** |
| **Address** | Tidak ada | Card dengan alamat lengkap + grid provinsi/kota/kecamatan/desa | **LOW** |
| **Account lifecycle** | Tidak ada | Card: status, join date, termination info | **LOW** |
| **Family** | List sederhana | Card per anggota keluarga dengan name, relationship, birth date | **LOW** |
| **Documents** | Placeholder text | Empty state dengan icon + upload button | **LOW** |
| **Actions** | Back + Edit button | Icon buttons + dropdown actions | **LOW** |

## PasPapan UI Pattern — Key Takeaways

### Status Badge System

```
Tone: success (green), warning (amber), danger (red), info (blue), accent (purple), neutral (gray)
Style: pill (rounded-full) vs default (rounded-md)
Semua pakai: ring-1 ring-inset untuk border subtle
Background: {tone}-50, Text: {tone}-700, Ring: {tone}-600/20
```

HRConnect sudah punya `color()` method di 16 Status/Indicator enum — mapping ke MD3 semantic colors. Tapi belum ada reusable Badge component yang pakai tone system ini.

### Avatar Pattern

```blade
{{-- Desktop table --}}
<div class="h-12 w-12 overflow-hidden rounded-full bg-emerald-100 ring-2 ring-emerald-100">
    <img src="{{ $user->profile_photo_url }}" alt="" class="h-full w-full object-cover">
</div>

{{-- Mobile card --}}
<img class="h-14 w-14 rounded-xl border-2 border-emerald-100 object-cover shadow-sm" 
     src="{{ $user->profile_photo_url }}" alt="" />

{{-- Detail modal --}}
<img class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 bg-slate-50 object-cover" 
     src="{{ $form->user->profile_photo_url }}" alt="">
```

HRConnect: foto employee tersimpan di `$employee->photo` (path string). API endpoint `GET /employees/{id}/pii` tidak include photo. Perlu tambah `photo_url` di Employee API response.

### Mobile Card Pattern (PasPapan)

```blade
<div class="space-y-4 bg-gradient-to-br from-emerald-50/80 via-white to-slate-50 p-4">
    {{-- Avatar + Name + Badges --}}
    <div class="flex items-start gap-3">...</div>
    {{-- Quick info grid --}}
    <div class="grid grid-cols-2 gap-2">
        <div class="rounded-xl border border-white/80 bg-white/80 px-3 py-2.5 shadow-sm">
            <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Phone</span>
            <div class="mt-1 text-sm font-medium text-slate-900">{{ $phone }}</div>
        </div>
        {{-- ... --}}
    </div>
    {{-- Action buttons --}}
    <div class="grid grid-cols-3 gap-2 pt-1">...</div>
</div>
```

### Summary Bar Pattern

```blade
<dl class="grid grid-cols-2 gap-2 sm:grid-cols-5 xl:min-w-[40rem]">
    <div class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-2">
        <dt class="text-[0.68rem] font-semibold uppercase text-emerald-700">Total</dt>
        <dd class="text-base font-semibold text-slate-950">{{ $total }}</dd>
    </div>
    {{-- Active, Filtered count, etc --}}
</dl>
```

### Empty State Pattern

```blade
<div class="mx-auto max-w-xl rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm sm:p-4">
    <span class="material-symbols-outlined text-4xl text-on-surface-variant">group</span>
    <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
    <p class="mt-1.5 text-sm text-gray-500">{{ $description }}</p>
    @isset($actions)<div class="mt-3">...</div>@endisset
</div>
```

### Detail Section Card Pattern

```blade
<div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-ink">Personal Information</h4>
        <span class="text-xs text-on-surface-variant">Category</span>
    </div>
    <dl class="mt-3 grid gap-3 sm:grid-cols-[9rem_minmax(0,1fr)]">
        <dt class="text-xs font-medium text-on-surface-variant">Gender</dt>
        <dd class="text-sm text-ink">Male</dd>
        {{-- ... --}}
    </dl>
</div>
```

## 🟢 Employee UI — Semua Selesai (✅ Sesi F)

| Tier | Task | Status |
|:----:|------|:------:|
| **P1** | **Summary bar + avatar + status badge + bahasa** di index | ✅ |
| **P1** | **Photo API** — tambah `photo_url` di EmployeeResource | ✅ |
| **P2** | **Mobile cards** — responsive, avatar, info grid, actions | ✅ |
| **P2** | **Detail page overhaul** — section cards, info grid, address, family, documents, PII gate | ✅ |
| **P2** | **Reusable status badge component** — `x-status-badge` dengan tone system | ✅ |
| **P3** | **Empty state** — ada di index & detail | ✅ |
| **P3** | **Filter dengan TomSelect** — branch filter sudah ada | ✅ |
| **P3** | **Pagination styling** — styling sudah responsive | ✅ |

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



# 🟢 UX Issues — Semua Selesai (9 item ✅)

Hasil audit UX E2E (1 Juli 2026). **Semua 9 item sudah diperbaiki di Sesi F.**

| # | Issue | Severity | Status |
|:-:|-------|:--------:|:------:|
| UX-1 | **Knowledge Base, Loans, Assets tidak punya link navigasi** — tambah di sidebar SDM section | HIGH | ✅ |
| UX-2 | **Approvals & clock-in page title kosong** — tambah `@section('title')` | HIGH | ✅ |
| UX-3 | **Mobile leave list pakai Inggris** — ganti ke "Menunggu"/"Disetujui"/"Ditolak" | MEDIUM | ✅ |
| UX-4 | **Auto-capture tanpa countdown** — tambah countdown 3-2-1 overlay | HIGH | ✅ |
| UX-5 | **Tidak ada panah arah liveness** — tambah arrow indicator SVG (kiri/kanan/tengah) | HIGH | ✅ |
| UX-6 | **Tidak ada progress bar liveness** — 1/4–4/4 step indicator | HIGH | ✅ |
| UX-7 | **Tidak ada tombol navigasi setelah sukses** — "Absen Sekarang" button | HIGH | ✅ |
| UX-8 | **Bottom nav "Pengajuan" href** — active pattern cover leaves/overtimes/reimbursements/approvals | MEDIUM | ✅ |
| UX-9 | **Bottom nav zero authorization** — gate per permission, Payroll hidden from employee | MEDIUM | ✅ |

---

# 🔴 Audit Multi-Repo: Role-Based UI + CSS Component Layer (2 Juli 2026)

Hasil audit mendalam 5 repo referensi: **PasPapan**, **Quanta HRIS**, **laravel-smarthr**, **HRMS**, **ship-ai-with-laravel**. Fokus: role-based UI inconsistencies + CSS component layer gap.

## Sintesis Pola Terbaik per Domain

| Domain | Repo Sumber | Pola Terbaik | Untuk Problem HRConnect |
|--------|------------|-------------|------------------------|
| **Role authorization di view** | PasPapan | `@can` eksklusif, 0 `hasRole()`, `Gate::authorize()` di Livewire | Konsistensi authorization |
| **Sidebar navigation** | laravel-smarthr | `config/menu.php` + `MenuService` pipeline + `View::composer` | Solusi sidebar terpusat |
| **Dashboard per-role** | HRMS | Single component + role-branched `mount()` + `render()` | Hapus 4 blok `@if` terpisah |
| **Status badge color map** | Quanta HRIS | `match()` pattern + semantic Tailwind colors | Reusable status chip system |
| **Indonesian helpers** | Quanta HRIS | `MonthHelper::formatPeriod()` + `getMonthOptions()` | Nama bulan + format ID |
| **Notification batch** | Quanta HRIS | Bulk insert ke semua user per role | Hindari N queries untuk N recipients |
| **Gate::before Admin bypass** | laravel-smarthr, HRMS | `Gate::before(fn($u) => $u->hasRole('Admin') ? true : null)` | Super-admin lewati semua cek |
| **Admin vs User route split** | PasPapan | `admin` middleware vs `user` middleware — dua route group terpisah | Clean route separation |
| **CSS @layer components** | PasPapan | 6623-line `app.css` dengan `@layer components` per domain | Attendance, payslip, profile styling |
| **Glass-morphism surfaces** | PasPapan | `.user-page-surface` dengan `bg-white/72 backdrop-blur-sm` | Kedalaman visual untuk mobile |
| **SSE streaming chat** | ship-ai | `fetch()` + `ReadableStream.getReader()` + `currentStream` accumulator | RAG chat real-time |
| **Typing indicator** | ship-ai | 3 bouncing dots staggered `animation-delay` + `x-show="isStreaming && !currentStream"` | UX saat menunggu AI |

---

## 🔴 CRITICAL: Bug Role-Based UI (2 item) ✅ Selesai Sesi G

### BUG-1: Approvals — 3 Role Tidak Bisa Approve ✅ Fixed

**Lokasi:** `resources/js/approvals-index.js:65` + `resources/views/approvals/index.blade.php:2`

**Akar masalah:** Blade kirim raw Spatie role name (`'super-admin'`, `'hr-manager'`, `'finance'`), tapi JS mengharapkan nama yang di-normalisasi (`'hr'`).

```js
// Line 65 — HANYA mengecek 2 nilai:
canApprove() {
    return this.role === 'manager' || this.role === 'hr';  
    // ✗ 'super-admin', 'hr-manager', 'finance' → FALSE
}

// Line 40 — fetchApprovals juga salah:
else if (this.role === 'hr') { ... }  // ✗ 'hr-manager', 'super-admin' fall ke generic
```

| Role | `this.role` | `canApprove()` | Bisa Approve? | Endpoint API |
|------|------------|----------------|:---:|--------------|
| Super Admin | `super-admin` | false | **TIDAK** | Salah |
| HR Manager | `hr-manager` | false | **TIDAK** | Salah |
| Finance | `finance` | false | **TIDAK** | Salah |
| Manager | `manager` | true | Ya | Salah (generic) |
| Employee | `employee` | false | N/A | Benar |

**Fix (sumber: HRMS pola normalisasi role):**
```js
// Di init() — normalisasi role name
this.normalizedRole = {
    'super-admin': 'hr',
    'hr-manager': 'hr',
}[this.role] ?? this.role;

// Perbaiki canApprove()
canApprove() {
    return ['manager', 'hr', 'finance'].includes(this.normalizedRole);
}

// Perbaiki fetchApprovals()
if (this.normalizedRole === 'hr') { ... }
else if (this.normalizedRole === 'finance') { ... }
```

**Task file:**
- `resources/js/approvals-index.js` — normalisasi role + perbaiki `canApprove()` + `fetchApprovals()`
- `resources/views/approvals/index.blade.php:25` — `$canApprove` di Blade sudah benar, tapi perlu sinkronisasi dengan JS

---

### BUG-2: Finance Dashboard — `pending_payrolls` Hardcoded 0 ✅ Fixed

**Lokasi:** `app/Http/Controllers/DashboardController.php:73`

```php
// Sekarang
$pending_payrolls = 0;

// Seharusnya (sumber: Quanta HRIS status-based filter)
$pending_payrolls = Payroll::whereIn('status', ['draft', 'submitted'])->count();
```

**Task file:**
- `app/Http/Controllers/DashboardController.php` — implementasi query `$pending_payrolls`

---

## 🔴 HIGH: Inkosistensi Role-Based UI (5 item)

### H-1: Dead Nav Items di Sidebar

**Lokasi:** `resources/views/layouts/app/sidebar.blade.php`

| Baris | Item | Masalah |
|-------|------|---------|
| 63-69 | "Admin Absensi" | `href="#"`, tooltip "segera hadir" — dead link |
| 190-199 | "Perusahaan & Struktur" | Teks mati tanpa `href` — tidak bisa diklik |

**Fix (sumber: laravel-smarthr `config/menu.php`):**
- Hapus dead stubs atau tambah `route` + permission check
- Jika belum diimplementasi: sembunyikan dengan `@can` guard

**Task file:**
- `resources/views/layouts/app/sidebar.blade.php` — hapus atau implementasikan 2 dead nav items

---

### H-2: Dua Sistem Authorization Paralel (Role vs Permission)

**Masalah:** Dashboard pakai **role-based** (`$role === 'hr'`), sidebar pakai **permission-based** (`@can(...)`), approvals JS pakai **string matching** — tiga strategi berbeda dalam satu aplikasi.

**Pola dari referensi:**
- **PasPapan:** `@can` eksklusif di semua view — tidak ada `hasRole()` di Blade
- **HRMS:** `Gate::before` Admin bypass — sisanya `@can`/`hasAnyRole()`
- **laravel-smarthr:** Custom Blade directives (`@superadmin`, `@employee`) + `@can` untuk fine-grained
- **Quanta HRIS:** Filament `shouldRegisterNavigation()` + `canViewAny()` — konsep sama dengan `@can`

**Rekomendasi (adopsi PasPapan + laravel-smarthr):**
1. Buat View Composer `currentRole()` — satu sumber kebenaran
2. Ganti semua `$role === '...'` di Blade dengan `@can` / `@canany`
3. Tambah `Gate::before` untuk super-admin bypass
4. Hapus `roles->first()?->name` dari semua view

**Task file:**
- `app/Providers/AppServiceProvider.php` — tambah View Composer `currentRole()`
- `resources/views/dashboard.blade.php` — refactor `@if($role === '...')` → `@can`
- `resources/views/employee/index.blade.php:3` — refactor `$role` → `@can`
- `resources/views/approvals/index.blade.php:7` — refactor `$role` → `@can`
- `resources/views/layouts/app/sidebar.blade.php:52` — refactor `hasRole()` → `@can`

---

### H-3: Sidebar Nav — Tidak Ada Konfigurasi Terpusat

**Masalah:** Setiap item sidebar di-hardcode dengan `@can` inline. Pattern rapuh — tidak ada single source of truth untuk struktur navigasi.

**Pola dari laravel-smarthr (PALING BERSIH):**
```php
// config/navigation.php — satu file untuk SEMUA nav item
return [
    ['title' => 'Utama'],
    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'can' => 'view_dashboard'],
    
    ['title' => 'SDM', 'visible' => fn($u) => $u->canAny(['view_employees', 'view_attendances', ...])],
    ['label' => 'Karyawan', 'route' => 'admin.employees.index', 'icon' => 'group', 'can' => 'viewAny:App\\Models\\Employee', 'roles' => ['super-admin', 'hr-manager', 'manager', 'finance']],
    // ... dst
];
```

Kemudian `MenuService::build()` → filter by permission + role → hasil dikirim ke sidebar via `View::composer`.

**Pola dari HRMS (JSON-based):**
```json
// resources/menu/verticalMenu.json
[
  {"label": "Dashboard", "url": "/dashboard", "icon": "home", "role": ["Admin", "HR", "CC"]},
  {"label": "Employees", "url": "/structure/employees", "icon": "users", "role": ["Admin", "HR"]}
]
```

**Rekomendasi:** Buat `config/navigation.php` dengan struktur array, filter by `Gate::allows()`, inject ke sidebar via View Composer. Ini menggantikan ~100 baris inline `@can` di sidebar.

**Task file:**
- `config/navigation.php` — file baru: definisi nav terpusat
- `app/Services/NavigationService.php` — file baru: filter + build menu
- `app/Providers/AppServiceProvider.php` — tambah View Composer
- `resources/views/layouts/app/sidebar.blade.php` — refactor: render dari array, bukan inline

---

### H-4: Variabel `$isSelf` Tidak Dipakai

**Lokasi:** `resources/views/employee/show.blade.php:3`

```php
$isSelf = auth()->user()->employee?->id === $employee->id;
// ... tidak pernah dipakai di template manapun
```

**Fix:** Hapus baris 3.

---

### H-5: Tidak Ada CSS Component Layer per Domain

**Masalah:** `resources/css/app.css` — 106 baris (hanya `@theme` tokens). PasPapan: **6.623 baris** dengan 30+ section `@layer components` per domain.

| Domain CSS PasPapan | Baris | HRConnect? |
|---------------------|-------|:----------:|
| flatpickr theming | 632 | ❌ |
| card utilities | 27 | ❌ |
| attendance panels | 243 | ❌ |
| user page shell | 135 | ❌ |
| profile page | 258 | ❌ |
| payslip panels | 93 | ❌ |
| auth pages | 493 | ❌ |
| bottom nav dock | 77 | ❌ |
| face enrollment | 228 | ❌ |
| glass-morphism | 1948 | ❌ |

**Dampak:** Setiap elemen di-style inline dengan utility class. Tidak ada abstraksi — duplikasi kode di setiap halaman.

**Fix (sumber: PasPapan):** Tambah `@layer components` minimal untuk: attendance-card, payslip-card, profile-section, auth-card, glass-surface.

**Task file:**
- `resources/css/app.css` — tambah `@layer components` untuk domain utama

---

## 🟡 Perbandingan Arsitektur: Sebelum vs Sesudah

| Area | Sebelum | Sesudah |
|------|---------|---------|
| Role detection | `roles->first()?->name` diulang 4x | `$currentRole` via View Composer |
| Sidebar | Inline `@can` + 2 dead stubs | `config/navigation.php` terpusat |
| Dashboard | 4 blok `@if` terpisah per role | Single view + permission-filtered sections |
| Approval JS | 3 role tidak bisa approve | Normalized role + semua role bisa approve |
| CSS | 106 baris, no component layers | `@layer components` per domain |
| UI surfaces | Flat MD3 (`bg-canvas`) | Flat MD3 + optional glass-morphism |
| Authorization | 3 sistem berbeda parallel | `@can` + `Gate::before` seragam |

---

## 📋 Task List: Perbaikan Role-Based UI

### Fase 1 — Bug Kritis (estimasi 2 jam)

| ID | Task | File | Estimasi | Sumber Pola |
|:--:|------|------|:--------:|-------------|
| **B1** | Fix approval JS — normalisasi role + `canApprove()` + `fetchApprovals()` | `resources/js/approvals-index.js`, `resources/views/approvals/index.blade.php` | 30 menit | HRMS normalisasi, PasPapan `can()` | ✅ |
| **B2** | Implement `pending_payrolls` query di DashboardController | `app/Http/Controllers/DashboardController.php` | 10 menit | Quanta HRIS `whereIn('status', [])` | ✅ |
| **B3** | Hapus dead nav items (Admin Absensi, Perusahaan & Struktur) | `resources/views/layouts/app/sidebar.blade.php` | 5 menit | laravel-smarthr `visible` key | ✅ |

### Fase 2 — Konsolidasi Authorization (estimasi 4 jam)

| ID | Task | File | Estimasi | Sumber Pola |
|:--:|------|------|:--------:|-------------|
| **C1** | Buat View Composer `currentRole()` di AppServiceProvider | `app/Providers/AppServiceProvider.php` | 15 menit | laravel-smarthr `View::composer` |
| **C2** | Refactor dashboard — ganti `@if($role === '...')` → `@can` | `resources/views/dashboard.blade.php` | 30 menit | PasPapan `@can` exclusive |
| **C3** | Refactor employee/index role-based titles → `@can` | `resources/views/employee/index.blade.php` | 15 menit | PasPapan pattern |
| **C4** | Refactor approvals/index → ganti `@php $role` dengan `@can` | `resources/views/approvals/index.blade.php` | 15 menit | PasPapan pattern |
| **C5** | Hapus `$isSelf` unused variable | `resources/views/employee/show.blade.php:3` | 1 menit | — |
| **C6** | Konversi sidebar inline `@can` → render dari array config | `config/navigation.php` (baru), `resources/views/layouts/app/sidebar.blade.php` | 2 jam | laravel-smarthr `config/menu.php` |

### Fase 3 — CSS Component Layer (estimasi 4 jam)

| ID | Task | File | Estimasi | Sumber Pola |
|:--:|------|------|:--------:|-------------|
| **D1** | Tambah `@layer components` attendance (card, status, timeline) | `resources/css/app.css` | 45 menit | PasPapan lines 1025-1267 |
| **D2** | Tambah `@layer components` payslip (card, earnings, deductions) | `resources/css/app.css` | 30 menit | PasPapan lines 1794-1886 |
| **D3** | Tambah `@layer components` profile (section card, info grid, family) | `resources/css/app.css` | 30 menit | PasPapan lines 1405-1662 |
| **D4** | Tambah `@layer components` glass-morphism surfaces | `resources/css/app.css` | 15 menit | PasPapan lines 4676-6623 |
| **D5** | Refactor halaman attendance pakai CSS component classes | `resources/views/attendance/*.blade.php` | 1 jam | PasPapan |
| **D6** | Refactor halaman payslip pakai CSS component classes | `resources/views/payroll/*.blade.php` | 30 menit | PasPapan |

### Fase 4 — Polish & Utility (estimasi 2 jam)

| ID | Task | File | Estimasi | Sumber Pola |
|:--:|------|------|:--------:|-------------|
| **E1** | Tambah `StatusColor::register()` — centralized status→tone mapping | `app/Providers/AppServiceProvider.php` | 30 menit | Quanta HRIS |
| **E2** | Tambah `MonthHelper` utility (Indonesia month names) | `app/Utils/MonthHelper.php` (baru) | 30 menit | Quanta HRIS |
| **E3** | Upgrade `x-bottom-nav` ke floating glass dock | `resources/views/components/bottom-nav.blade.php` | 45 menit | PasPapan |
| **E4** | Tambah `@layer components` untuk `.wcag-touch-target` | `resources/css/app.css` | 5 menit | PasPapan |

---

## 📊 Prioritas Eksekusi (Update 2 Juli 2026)

| Prioritas | Task | Role Terdampak | Dampak |
|:---------:|------|:--------------:|--------|
| **P0** | B1 — Fix approval JS (3 role tidak bisa approve) | Super Admin, HR Manager, Finance | ✅ Fixed Sesi G |
| **P0** | B2 — Implement `pending_payrolls` di dashboard | Finance | ✅ Fixed Sesi G |
| **P0** | B3 — Hapus dead nav items | Semua role | ✅ Fixed Sesi G |
| **P1** | C1-C6 — Konsolidasi authorization system | Semua role | Bersihkan 3 sistem parallel |
| **P2** | D1-D6 — CSS component layer + refactor | Semua role | Kurangi duplikasi CSS inline |
| **P3** | E1-E4 — Polish utilities | Semua role | Reusable helpers |
