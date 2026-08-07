# Design — HRConnect

A locked design system for HRConnect HRIS. Every page redesign reads this file first.
Do not regenerate per page — extend or amend this file when the system needs to grow.

## Genre
modern-minimal

## Macrostructure family
- Marketing pages (login, landing): **Marquee Hero** — centered narrow hero, off-axis CTA/eyebrow, single visual anchor
- App pages (dashboard, admin, employee, attendance, payroll, finance): **Workbench** — dense toolbar + content canvas, persistent sidebar/bottom-nav, high information density
- Content pages (settings, profile, help, knowledge-base): **Long Document** — generous measure, clear heading rhythm, minimal chrome

## Theme (OKLCH — Light mode ONLY)
--color-paper:      oklch(100% 0 0);           /* #FFFFFF - pure white canvas */
--color-paper-2:    oklch(98.5% 0.003 240);    /* #F7F9FC - subtle blue-tint surface */
--color-paper-3:    oklch(94% 0.006 240);      /* #EDF2F7 - elevated surface */
--color-ink:        oklch(20% 0.02 255);       /* #1A202C - primary text */
--color-ink-2:      oklch(45% 0.02 255);       /* #718096 - secondary text */
--color-rule:       oklch(88% 0.01 240);       /* #E2E8F0 - borders, dividers */
--color-accent:     oklch(24% 0.03 260);       /* #1E293B - NEUTRAL DARK chrome (2026-08-06) */
--color-accent-ink: oklch(100% 0 0);           /* #FFFFFF - text on accent */
--color-focus:      oklch(34% 0.03 260);       /* slate-700 — focus ring netral gelap */
--color-success:    oklch(60% 0.18 150);       /* #10B981 */
--color-warning:    oklch(75% 0.18 85);        /* #F59E0B */
--color-error:      oklch(58% 0.22 25);        /* #EF4444 */

## Modular Hue System (2026-08-05 — colorful design decision)

Every module carries its own accent hue so users can navigate by color. The
global brand blue stays for chrome/CTAs; module hues appear on icons, badges,
tabs, section headers, and small accent strips (≤8% of viewport per module page).

| Modul | Hue token | OKLCH | Notes |
|-------|-----------|-------|-------|
| HR & Master Data | `--color-module-hr` | oklch(58% 0.22 260) | = brand blue (default) |
| Absensi & Jadwal | `--color-module-attendance` | oklch(70% 0.14 200) | cyan/teal |
| Cuti & Approval | `--color-module-leave` | oklch(60% 0.20 295) | violet |
| Payroll & Payslip | `--color-module-payroll` | oklch(65% 0.17 160) | emerald |
| Dokumen & Checklist | `--color-module-documents` | oklch(70% 0.17 75) | amber |
| Reports & Import/Export | `--color-module-reports` | oklch(60% 0.18 230) | sky blue |
| AI Knowledge Base | `--color-module-kb` | oklch(60% 0.22 330) | fuchsia |
| Finance (reimburse/kasbon) | `--color-module-finance` | oklch(65% 0.20 20) | rose/coral |

Rules:
- **AMENDED 2026-08-06 (multi-warna playful):** CTA, primary buttons, focus
  rings, active nav = **neutral dark ink** (`--color-primary-*` slate scale,
  ~#1E293B). Brand blue TIDAK lagi jadi warna chrome global.
- Module hue appears on: module icons, status badges, tab underline, section accent strip, table header tint, AND (2026-08-06) kartu konten + eyebrow.
- Gradient accents: wash ≤10% alpha berlaku untuk non-banner admin; kartu konten ESS boleh wash lebih berani (lihat "Multi-Color Playful Redesign").
- **AMENDED 2026-08-06:** user-facing (ESS) pages MAY use SOLID gradient banners as an exception to the ≤10% wash rule — see "Solid Gradient Banners" below.
- Success/warning/error semantic colors unchanged; a module page uses at most its own hue + semantic colors.
- Dark mode: not supported (light-only, per user decision 2026-08-05).

## Solid Gradient Banners (2026-08-06 — user-facing pages)

Keputusan Fikih 2026-08-06: halaman user (ESS) boleh memakai banner gradien
warna solid — bukan hanya wash ≤10% alpha — supaya tampilan "colorful solid"
seperti HRIS lain. Ini pengecualian resmi dari aturan gradient wash di atas,
agar halaman-halaman lain konsisten dengan arah warna baru ini.

Berlaku UNTUK (hanya):

- **Hero home / dashboard user** (`.user-home-hero--command .user-home-hero__inner`)
  — banner gradien brand blue → cyan (attendance), teks putih. Dekorasi glow
  lembut wajib ditaruh di wrapper terpisah (`.user-home-hero__glow`), JANGAN
  pakai `overflow: hidden` di kartu — supaya dropdown notifikasi tidak
  ter-clip pada viewport 640–767px.
- **Header halaman modul user** (`.user-page-header--banner`) — gradien per
  module hue (cyan/violet/emerald/amber/sky/fuchsia/rose/blue). Diterapkan
  otomatis oleh komponen `x-user.page-header` saat atribut `module` di-set.

Pemetaan `module=` → hue (terverifikasi grep blade 2026-08-06):

| `module=` | Hue | Pages |
|-----------|-----|-------|
| `attendance` (7) | cyan | scan, face-enrollment, shift-schedule-page, work-from-home-request-page, shift-swap-request-page, attendance-history, attendance-correction-page |
| `leave` (4) | violet | apply-leave, team-approvals, overtime-request, team-approvals-history |
| `hr` (4) | blue | profile/show, collaboration-inbox, hr-tasks-page, notifications-page |
| `finance` (4) | rose | reimbursement-page, team-cash-advance-manager, my-cash-advances, my-assets |
| `reports` (2) | sky | my-performance + my-operational-tasks — keputusan Fikih 2026-08-06 (performance & tasks = laporan, bukan hr) |
| `documents` (2) | amber | employee-document-request-page, my-custom-forms — keputusan Fikih 2026-08-06 (form internal = dokumen) |
| `payroll` (1) | emerald | my-payslips |
| `kb` (2) | fuchsia | knowledge-base-chat, knowledge-base/index |

Aturan tambahan: kalau `module` TIDAK di-set, komponen render header polos
(tanpa banner) — halaman yang belum dipetakan tidak akan mendapat gradien
sampai pemetaannya ditambahkan.

Aturan banner:
- Arah gradien: 135deg, hue GELAP di kiri-atas (tempat teks) → hue terang di
  kanan-bawah. Kontras teks putih minimal ±4.5:1 pada stop gelap.
- Elemen di atas banner (tombol back, chip ikon): **SOLID putih** (`background: var(--color-white)`, ikon `slate-700`) — AMENDED 2026-08-06: transparan+blur DILARANG (lihat ZERO-GLASS FINAL).
- CTA / primary button tetap global accent (biru), bukan module hue.
- **AMENDED 2026-08-06 (lanjutan):** elemen di bawah hero TIDAK boleh menindih
  banner. `home-date-context` di home sebelumnya overlap tepi bawah hero
  (margin-top negatif + z-index), lalu dibatalkan setelah 2× keluhan Fikih
  "komponen saling menindih" — sekarang duduk DI BAWAH hero dengan jarak
  normal (margin-top positif 0.875rem) dan padding-bottom hero dikurangi
  (1.5rem/1.75rem) supaya tidak ada ruang gradien kosong.
- SATU halaman = SATU hue + semantic colors; tidak ada rainbow mixing.

TIDAK berlaku untuk: halaman admin (admin-ui), halaman marketing, dan
panel/konten non-banner (tetap pakai aturan lama: wash ≤10% alpha / tanpa
enrichment).

## User App Shell: Topbar Nav, Footer & Card Accents (2026-08-06)

Keputusan Fikih 2026-08-06 (keluhan: "semua page masih putih, tidak ada
footer, topbar desktop tidak memberi navigasi jelas"): user-facing app shell
diperkaya warna + navigasi, tanpa mengubah aturan wash untuk panel konten.

### 1. Desktop topbar nav pills (md+)
- `.app-topbar__nav` — deretan pill ber-ikon yang **mirror bottom-nav mobile**
  (Beranda, Jadwal, Absen, Tasks; + Team Approvals untuk manager), tampil
  hanya ≥768px (mobile tetap bottom-nav).
- Item pill: `.app-topbar__nav-item`, ikon + label, `wire:navigate`;
  state aktif via `aria-current="page"` + modifier `.is-active` (wash module
  hue / global accent, bukan fill solid).
- Alasan: konsistensi navigasi antar breakpoint + affordance klik lebih jelas
  daripada teks polos.

### 2. Footer — DICABUT (2026-08-06)
- `x-user.app-footer` yang sempat dipasang di `layouts/app.blade.php`
  **dihapus total** (blade, include layout, CSS). Keputusan Fikih: footer
  tidak diperlukan di HRIS — halaman berakhir bersih setelah konten
  (clearance bottom-nav tetap dijamin wrapper `pb-[calc(6.5rem+...)]`).

### 3. Card accents (panas kartu putih)
- Kartu konten utama ESS diberi **strip gradien 4px module-hue di tepi atas**
  + **wash berani (~70-85% alpha di atas, fade ke putih)** — jelas berwarna,
  bukan lagi strip tipis.
- Home = multi-hue playful: `.attendance-panel` cyan, `.quick-wallet-surface`
  biru, `.home-command-panel` (action needed) violet,
  `.home-command-panel--emerald` (recent) emerald, `.user-content-panel`
  (events) amber. Halaman modul lain tetap SATU hue.
- **GENERALIZED 2026-08-06 (halaman modul user):** pola kartu accent di-
  generalisasi ke `.user-accent-card` + varian hue (`.user-accent-card--
  attendance/hr/payroll/finance/kb/leave`) + varian `.user-accent-card--soft`
  untuk baris list (strip 2px + wash ringan). Dipasang di kartu konten utama:
  scan (cyan), jadwal (cyan), profil (hr blue), kasbon (finance rose),
  payslip (payroll emerald), KB chat (kb fuchsia). Header modul tetap banner
  gradien (komponen `x-user.page-header` saat `module` di-set); kartu accent
  melengkapi banner — KB strip dipasang di area chat (bukan section) supaya
  tidak menindih banner.
- **SOLID REDESIGN 2026-08-06 (lanjutan — keluhan Fikih: "glassmorphism,
  bukan solid"):** semua wash/rgb-alpha dan `backdrop-blur` pada kartu
  utama DICABUT. Warna kini 100% token-driven via `color-mix()` dari token
  `@theme --color-module-*` (nol hex hardcode):
  - `.solid-card` + `.solid-head` = band header gradien module (hue gelap →
    hue, teks putih) + body putih SOLID — pola dashboard Odoo/Talenta.
    Dipakai: kartu scan (band attendance), profil identity + section header
    (band hr), kasbon panel (band finance), payslip secure panel + summary
    (band payroll).
  - `.user-accent-card` = latar pastel SOLID opaque (color-mix 10% module +
    white) + strip solid; varian `--soft` utk baris list (6% tint, strip 2px).
  - Rule `.user-ui .attendance-panel, ...` di app.css diubah `bg-white/70
    backdrop-blur-sm` → `bg-white` solid; tint module ditumpuk setelahnya
    (spesifisitas sama, lebih akhir).
  - Modifier module (`.user-accent-card--hr/attendance/leave/payroll/
    documents/reports/kb/finance`) hanya membawa variabel `--mod-base/
    deep/tint/tint-soft/border`; `.solid-head` dan `.user-accent-card`
    memakainya. Satu halaman tetap SATU hue.
  - **TOKENISASI MENYELURUH (audit 2026-08-06 — baca seluruh app.css
    6.115 baris):** semua hex/rgb hardcode di luar `@theme` dicabut →
    `color-mix()` dari token `--color-module-*` / `--color-brand-*`.
    Yang ditoken-kan: module hue header accents (ikon + strip 2px, 8
    varian), banner header 8 varian (gradien `color-mix(module 62%,
    #0f172a) → module → color-mix(module 55%, white)`), home hero command
    (hr → attendance), home-date-context icon + clock pill + time, topbar
    strip multi-hue 4 modul, quick-wallet badge, attendance eyebrow,
    verify-email hover, compact-filter focus. Build LightningCSS
    me-resolve color-mix ke nilai final — source tetap token-driven.
    (catatan 2026-08-06 lanjutan: route scan/native-scanner, pull-to-
    refresh spinner, fallback var(), dan netral slate SEMUANYA sudah
    ditoken-kan juga — lihat aturan TOKEN-ONLY di bawah; yang tersisa
    hanyalah pengecualian teknis yang wajib literal: email, PDF Dompdf,
    standalone print, error page tanpa @vite, meta theme-color, SVG
    data-URI, shadow netral).
  - **CLEANUP GLASS MENYELURUH (lanjutan 2026-08-06):** `bg-white/5x-7x` +
    `backdrop-blur` di SEMUA class kartu/panel konten user dicabut → solid
    (`bg-white`, `bg-slate-50`, `bg-primary-50`) — meliputi: `attendance-
    panel__step` + variants is-current/is-complete, `user-stat-strip/pill`,
    `user-history-hero/filters/calendar/panel/summary`, `home-activity-list`,
    `kasbon-alert`, `team-approval-card`, `team-kasbon-history__item`,
    `asset-mobile-card` + details, `asset-overview-strip`, `asset-pass`,
    `wfh-request-form/summary/item`, `document-request-hero/stats/card`,
    `performance-hero/summary/card/kpi-card/notes`, `face-enrollment-ready`,
    `notification-center-hero/filters/list`, `payslip-summary__metric`,
    dan override `.user-ui` (table, form, dropzone, quick-wallet, article).
    Yang TETAP glass (sengaja): overlay modal backdrop, dropdown notifikasi
    floating, toolbar sticky, bottom-nav dock, chip ikon/back di atas banner
    gradien, input OTP, hover state — lapisan floating yang butuh transparansi.
    Verifikasi: 32 class dicek di bundle build — nol alpha tersisa; 8 halaman
    user (schedule/kasbon/payroll/kb/scan/profile/notifications/leave)
    load tanpa pageerror; smoke test 16/16 PASS.
  - **CLEANUP GLASS EKSTENSI — UTILITY CLASS INLINE (2026-08-06):** sweep
    sebelumnya hanya membersihkan class CSS custom; sesi ini membersihkan
    **utility class inline di blade user pages** yang terlewat (12 file,
    31 baris): `bg-white/55-85` → `bg-white`/`bg-slate-50`,
    `bg-slate-50/70` → `bg-slate-50`, `bg-gray-50/50` → `bg-gray-50`,
    `bg-rose-50/50`/`bg-amber-50/50` → solid,
    `bg-emerald-50/50`/`bg-sky-50/60`/`bg-primary-50/60` → solid,
    `border-white/70` → `border-slate-200/70`, dan `backdrop-blur-sm`
    dicabut dari kartu yang bg-nya sudah solid (desktop panel shift-swap).
    File: collaboration-inbox, shift-schedule-page, hr-tasks-page,
    work-from-home-request-page, my-custom-forms, shift-swap-request-page,
    attendance-correction-page, my-operational-tasks, attendance-history,
    upcoming-events-widget, profile/admin-show, profile/logout-sessions.
    Pengecualian tetap dipertahankan: overlay modal (fixed inset-0),
    sticky footer KB chat, chip ikon/back di atas banner gradien (scan),
    hover state, overlay clock-in. Verifikasi: grep sisa wash = hanya
    pengecualian; backdrop-blur = hanya overlay/sticky/banner; 3 audit
    script PASS; UserMenuSmokeTest 9/9 PASS.
  - **GLASS SWEEP TOTAL — USER + ADMIN (2026-08-06, keluhan Fikih ulang):**
    glassmorphism DILARANG di seluruh halaman (tidak hanya user pages).
    42 blade di-sweep: `bg-*/NN` (white/slate/gray/primary/blue/sky/rose/
    amber/emerald/red-800 dll) → solid; `backdrop-blur` dicabut dari kartu
    yang bg-nya sudah solid (insight-panel, analytics-dashboard, sticky
    header employees, search input, error card, footer KB chat, footer
    modal announcement). Hover wash ikut di-solid-kan (transisi singkat,
    nol glass). Sisa transparansi HANYA fungsional (bukan glassmorphism):
    scrim overlay modal (`fixed inset-0 bg-slate-950/NN`), chip/ikon
    transparan DI ATAS banner gradien (login, scan), overlay fullscreen
    clock-in + ripple `animate-ping`. Verifikasi: grep sisa wash alpha =
    3 baris (banner/overlay clock-in); backdrop-blur = 5 baris (scrim +
    banner); 3 audit script PASS; build sukses; smoke test 9/9 PASS
    (46 assertions); 54 blade lint OK.
  - **ZERO-GLASS FINAL (2026-08-06, keluhan Fikih "masih glassmorphism"):**
    nol blur + nol alpha tersisa di kartu/chip — GLASSMORPHISM HAPUS TOTAL.
    Definisi Fikih: transparansi + blur + border tipis = glass; yang
    sebelumnya dianggap "pengecualian fungsional" (chip back/ikon di atas
    banner, chip login, scrim modal) TIDAK lagi dibenarkan. Yang dilakukan:
    - `backdrop-filter`/`--tw-backdrop-blur` DIHAPUS SEMUA dari aturan
      custom app.css (42 deklarasi: `user-page-header__back`,
      `user-header-icon-action`, `quick-wallet-modal__backdrop`, dll) +
      dari 4 scrim modal blade (doc-request ×2, upcoming-events,
      announcement). Sisa `backdrop-filter` di app.css hanya di daftar
      `transition-property` (inert, bukan deklarasi blur). Grep:
      `backdrop-blur` di blade = 0, di app.css = 0, di JS = 0.
    - Background alpha `color-mix(..., transparent)` di app.css
      di-solid-kan SEMUA (227 deklarasi total lintas 5 pass) untuk
      white/slate/primary-50/red-50/orange-50/on-error/surface — kartu
      quick-wallet, profile stat/icon/meta, compact-filter input, danger
      card, disabled action, scan topnav (96%→solid white), dst. Sisa
      alpha HANYA state transient (`:hover`/`:focus`/`:active` — transisi
      interaksi, bukan desain glass) + scrim overlay modal + kontrol
      kamera (scan/clock-in, fungsional tanpa blur) + blob dekoratif
      `blur-3xl` (ambience, bukan kartu). Utility dead
      `.odd\:bg-white\/2` dihapus.
    - Chip login (4 tile di banner brand): `bg-white/20 backdrop-blur-sm`
      → **solid `bg-white shadow-md`** + ikon `text-white` →
      `text-brand-700` (biru brand di tile putih). Tombol scan "Try
      Again": solid white + `text-slate-700`; status pill scan: blur
      dicabut (bg tetap dari `statusClass` solid).
    - Verifikasi: bundle terbaru (app-*.css, ambil via `ls -t` bukan
      alfabetis) — `backdrop-blur` utility = 0, deklarasi `backdrop-filter`
      = 0 (hanya transition-property), `text-brand-700`/`from-brand-700`
      hadir (login gradient biru tetap jalan); 3 audit PASS; build sukses;
      smoke test 9/9 PASS. Aturan baru: **nol backdrop-blur, nol
      background alpha di kartu/chip — hanya hover state + scrim overlay +
      kontrol kamera + blob dekoratif yang boleh semi-transparan, dan itu
      pun TANPA blur.**
- `background-image` + `background-color` (white/95) dipakai bersamaan:
  gradient di atas warna dasar — aman terhadap rule lama yang sama
  spesifisitasnya karena `background-image` tidak pernah di-set sebelumnya.
- Aturan: SATU kartu = SATU hue; jangan tambahkan ke kartu admin atau
  komponen kecil (badge, avatar) yang sudah berwarna sendiri.

### 4. Home layout grid (2026-08-06)
- `.home-layout` = grid 2 kolom di `lg+`: main 2fr (absensi, action-needed,
  recent full-width) + side 1fr (quick access, events). Penempatan via
  `grid-area` (`.home-grid-att/qa/cmd/ev/rec`) — DOM tetap urut. Mobile
  stack satu kolom.

### 4b. Header gap mobile rapat (2026-08-06 — keluhan Fikih "page header
kaku, ada space kosong di atasnya")
- Mobile tidak ada topbar (`.app-topbar` `hidden md:block` di route user),
  tapi gap header sebelumnya 32px = `main pt-4` (16px) + `.user-page-shell`
  padding-top (16px). Dirapatkan:
  - `main` route user mobile: `pt-4 sm:pt-2` → `pt-2 sm:pt-1` (desktop
    `md:pt-[calc(4rem+env(safe-area-inset-top))]` tetap).
  - `.user-page-shell` padding-top: mobile `calc(env(safe-area-inset-top) +
    0.5rem)` (8px), `@media (min-width:1024px)` kembali `max(1rem,
    env+1rem)`. `body.platform-ios` override 3rem tetap.
  - Home hero: `pt-0` diganti `pt-[calc(env(safe-area-inset-top)+0.5rem)]`
    supaya di device notch hero tidak ketimpa status bar.
- Hasil ukur: mobile headerTop 32 → 16px konsisten semua halaman, home 8px,
  desktop tetap 80px (hero 72px, 7px di bawah topbar 65px).

## Multi-Color Playful Redesign (2026-08-06 — seluruh aplikasi)

Keputusan Fikih 2026-08-06 (keluhan: "masa design putih biru doang"): redesign
warna menyeluruh — chrome berhenti biru, module hue bicara lebih luas. Berlaku
untuk halaman user DAN admin.

### Token baru (app.css `@theme`)
- `--color-primary-*` → skala **neutral dark slate**: 50 `#f8fafc` · 100
  `#f1f5f9` · 200 `#e2e8f0` · 300 `#cbd5e1` · 400 `#94a3b8` · 500 `#64748b` ·
  600 `#475569` · 700 `#334155` · 800 `#1e293b` · 900 `#0f172a` · 950
  `#020617`. Tombol `bg-primary-700`, focus ring, active nav otomatis ikut.
- `--color-module-*` (8 hue) dipertahankan — inilah warna utama tiap halaman.
- `--color-brand-*` = skala BIRU ASLI HRConnect (`#024ad8` family). KHUSUS
  untuk halaman marketing/auth (login, register, forgot-password, dll) +
  momen brand kecil. Halaman app PAKAI `primary` (netral) — jangan pakai
  `brand-*` di halaman app kecuali momen brand yang disengaja.

### Aturan pemakaian warna
- SATU halaman modul = SATU module hue + semantic colors (tetap). Home
  dashboard = momen brand (blue → cyan) + boleh multi-hue tipis.
- Chrome (tombol, focus, active nav, footer) = neutral dark, TIDAK ber-hue.
- Kartu konten utama: strip 4px module hue + wash lebih berani (~60% alpha di
  atas, fade ke putih) — bukan lagi ≤10% tipis.
- Eyebrow section di kartu ber-hue module (contoh: `.attendance-panel__eyebrow`
  cyan, `.home-command-panel__eyebrow` biru).
- Topbar: strip bawah `.app-topbar::after` = **multi-hue signature**
  (blue → cyan → violet → amber) di semua halaman user & admin — pengecualian
  resmi dari aturan "satu halaman satu hue" karena ini chrome, bukan konten.
- Kanvas body = `.app-canvas` (gradien netral halus `#f8fafc → #eef2f7 →
  #f4f7fb`), menggantikan `bg-gray-100` flat.

### TOKEN-ONLY RULE (2026-08-06 — nol hardcode warna/desain)

Keputusan Fikih: **jangan ada design ataupun coloring di hardcode — SEMUA
warna lewat token.** Berlaku untuk seluruh source: `app.css`, blade, JS.

- `app.css`: nol hex/rgb di luar `@theme` (definisi token) dan fallback
  `var(--x, fallback)` (fallback wajib merujuk token, bukan hex). Nuansa
  dibuat via `color-mix(in srgb, var(--color-*), ...)`.
- Blade: warna lewat utility Tailwind token (`bg-primary-700`, `text-module-
  leave`, `bg-surface`, dst) — dilarang arbitrary value hex
  (`bg-[#123456]`, inline `style="color:#..."`).
- JS/DOM runtime (Chart.js, canvas face overlay, Leaflet): warna dibaca dari
  token via `window.cssVar('--color-module-*')` / `window.colorWithAlpha(...)`
  (helper di `resources/js/app.js`) — bukan hex literal di script.
- SVG logo/mark: `fill="var(--color-brand-500)"` / `currentColor` — bukan
  hex literal. Fallback `var(--md-sys-*, var(--color-*))` tetap boleh
  (defensive, merujuk token).
- Alpha putih netral (glass floating layer: dropdown, topbar, chip di atas
  banner) juga token: `color-mix(in srgb, var(--color-surface) 95%, transparent)`.

**Layer PHP untuk renderer NON-CSS (PDF Dompdf & email):**
- `App\Support\DesignTokens` = token PHP tunggal: **CORE mirror @theme**
  (primary/brand/module/success/dst — wajib sinkron, dicek otomatis oleh
  `php scripts/check-token-sync.php`) + **docs family** khusus dokumen/email
  (brand-deep, brand-green-50…950, muted-green-*, status-*, warning-* —
  web UI tidak memakainya, tidak perlu masuk @theme).
- Template `pdf/*.blade.php` & `emails/*.blade.php` memanggil
  `{{ design_token('brand-green-600') }}` / `{{ design_rgba('brand-green-700', 0.8) }}`
  (helper di `app/helpers.php`) → Blade me-render **hex/rgba literal** saat
  output → kompatibel penuh Dompdf & Outlook/Gmail, template bebas hardcode.

**Pengecualian teknis — renderer yang TIDAK punya akses ke sistem token:**
1. **Standalone print page `admin/attendances/report.blade.php`** — dokumen
   HTML mandiri tanpa bundle app (tidak ada `@vite`), tidak punya akses
   token CSS maupun helper PHP (di-render mentah tanpa Laravel view data
   flow — halaman `Route::view` mandiri).
2. **`errors/minimal.blade.php`** — halaman error standalone tanpa `@vite`;
   memakai `:root` token lokal sendiri (var-driven, nilai literal di blok
   definisi — setara `@theme`).
3. **`<meta name="theme-color">`** (guest-layout, layouts/app, errors) —
   browser tidak me-resolve `var()` di meta; harus literal.
4. **SVG data-URI** (chevron tom-select, icon di `url("data:...")`) — string
   statis, tidak bisa merujuk CSS var. Warna ikon netral.
5. Shadow netral hitam (`rgb(0 0 0 / …)`) di box-shadow — universal, bukan
   warna brand/modul.
6. **`resources/views/vendor/**`** — boilerplate framework (Laravel default
   mail theme, Jetstream) — bukan desain kita; tidak ditoken.
   Catatan: `Mail\PayrollPayslipPdfMail` body email pakai default mail theme
   vendor ini (PDF lampirannya yang branded via `pdf/payslip`).

**Drift palet (disengaja):** nilai warna legacy PDF/email dipetakan ke token
terdekat untuk unifikasi — contoh `#111827→primary-900`, `#4b5563→primary-600`,
`#16a34a→success`, `#14532d→brand-green-900`, `#f0f9ee→brand-green-50`.
Drift halus (keluarga warna sama) adalah konsekuensi token-driven, bukan
regresi.

### CI: audit warna otomatis

Rule token-only DIJAGA OTOMATIS oleh tiga script (semua masuk `composer ci:check`):

- **`php scripts/check-color-tokens.php`** (`composer token:check`) — scan
  menyeluruh 2 CSS + 230+ Blade + 5 JS + 580+ PHP:
  - `app.css`: hex/rgb NON-netral di luar blok `@theme` → FAIL
    (`rgb(0 0 0 / …)` shadow netral = pengecualian; `@theme` definitions
    = sumber token, bukan usage).
  - CSS lain (`resources/css/vendor/*.css`): file custom proyek (mis.
    `flatpickr-overrides.css`) WAJIB token — bukan vendor murni.
  - Blade: hex di luar whitelist pengecualian (report print standalone,
    errors/minimal, meta `theme-color`, SVG data-URI, `views/vendor/**`)
    → FAIL.
  - JS: hex + rgb non-netral → FAIL (shadow SweetAlert kini
    `var(--shadow-alert-*)` token di `@theme`).
  - `app/**`: hex hanya di `DesignTokens.php` (definisi token).
- **`php scripts/check-ui-rules.php`** — audit struktur & aksesibilitas UI
  (blocking: `raw_inline_svg`, `table_usage`, `translation_key_missing`;
  warning: hardcoded text, form label, icon-only button, mobile layout red
  flag). Enforce: rule Heroicon-only (`raw_inline_svg`) dan pola table
  mobile-first (`table_usage` — table wajib desktop-only dalam wrapper
  `hidden <bp>:block` + companion kartu `<bp>:hidden`, kalau tidak →
  blocking/warning).
- **`php scripts/check-token-sync.php`** — sinkronisasi dua arah
  `@theme` ↔ `App\Support\DesignTokens` + validasi key blade.
  - **Pengecualian terdokumentasi (2026-08-06):** palet default Tailwind v4
    (red/orange/amber/yellow/lime/green/emerald/teal/cyan/sky/blue/indigo/
    violet/purple/fuchsia/pink/rose/slate/gray/zinc/neutral/stone/white/
    black) di @theme TIDAK wajib di-mirror ke DesignTokens — itu
    framework-provided, bukan desain custom. Token recovery (10 token
    semantik: attendance-live/accent, banner-*-deep/light,
    module-payroll-deep/eyebrow, scan-icon-mint/ink, danger-copy — lihat
    catatan rekonstruksi di bawah) juga dikecualikan — artefak recovery
    satu-off, bukan token desain.

Audit perdana 2026-08-06 menemukan 9 hardcode yang lolos grep manual
(semua shadow berwarna): 4× `rgba(87,148,74)` (hijau brand) →
`color-mix(in srgb, var(--color-success) 50%, transparent)`, 3×
`rgba(15,23,42)` (primary-900) di app.css → color-mix token, 2× shadow
SweetAlert di app.js → `--shadow-alert-popup`/`--shadow-alert-toast` token
baru di `@theme`. Semua sudah ditoken-kan — bukti audit otomatis menutup
celah yang tidak terlihat grep manual.

**GAP FIX 2026-08-06 (lanjutan):** audit kedua menemukan gap di
`check-color-tokens.php` — untuk Blade hanya `scanHex` yang dijalankan,
`scanRgb` TIDAK pernah → `rgba(...)` di blade lolos audit. Gap ditutup
(blade kini discan `scanHex` + `scanRgb`), dan 9 hardcode tersembunyi
langsung ter-flag lalu ditoken-kan:
- `scan.blade.php` canvas face overlay `rgb(52,211,153)`/`rgb(251,191,36)`
  → `window.cssVar('--color-emerald-400'/'--color-amber-400')` (pola
  face-enrollment).
- `verify-email.blade.php` hover shadow `rgba(15,23,42,0.12)` +
  `rgba(2,74,216,0.08)` (brand blue) → token `--shadow-card-hover`
  (`0 4px 24px -8px #0f172a1f, 0 2px 8px -4px #024ad814`), dipakai via
  utility `hover:shadow-card-hover`.
- `errors/layout.blade.php` shadow `rgba(15,23,42,0.72)` → token
  `--shadow-error-card` (`0 30px 80px -52px #0f172ab8`) → `shadow-error-card`.
- `document-template-manager.blade.php` preview shadow
  `rgba(15,23,42,.28)` → token `--shadow-doc-preview` (`0 22px 55px
  #0f172a47`) → `box-shadow: var(--shadow-doc-preview)`.
- `analytics-dashboard.blade.php` Chart.js theme `rgba(226,232,240,0.9)` /
  `rgba(71,85,105,0.82)` / `rgba(51,65,85,0.86)` → token
  `--color-chart-grid/tick/legend` (hex 8-digit, di-`window.cssVar()`).

Juga: `scripts/check-ui-rules.php` crash pre-existing diperbaiki
(`isWhitelisted()` menerima int untuk `?string $key` karena translation key
numerik murni diubah PHP jadi int — di-cast `(string)`).

Verifikasi sesi: grep menyeluruh — `app.css` nol hex di luar `@theme`;
blade screen nol hex; `pdf/**` + `emails/**` nol hex (semua via
`design_token()`/`design_rgba()`); JS nol hex; `app/**` hex hanya di
`DesignTokens.php` (definisi token); `scripts/check-token-sync.php` pass
(43 CORE overlap identik, 43 key @theme ter-mirror, 35 key blade valid —
typo token = FAIL CI, bukan warna salah diam-diam). Build LightningCSS
me-resolve color-mix ke nilai final, source tetap token-driven.

### Rekonstruksi app.css 2026-08-06 (insiden `git checkout`)

Insiden: `git checkout resources/css/app.css` saat uji negatif audit
menimpa file kerja (6.115 baris, semua kerja SOLID redesign + tokenisasi
+ banner + cleanup glass) dengan versi commit lama (5.356 baris). Satu-
SATUNYA snapshot utuh = bundle build `public/build/assets/app-BNuOO-YO.css`
(639KB, build 18:52, di-backup ke `/tmp`).

Recovery: app.css disusun ulang dari bundle (kompilasi = ground truth):
- `@theme` ← seluruh var `:root` bundle (276 token, termasuk palet default
  yang dulu hanya ter-emit via @apply).
- `@layer base` ← blok base lama (commit) — rekompilasi dengan theme baru
  sama persis dengan bundle (terverifikasi).
- `@layer components` ← isi komponen bundle, di-prettify + SEMUA hex di-
  token-kan ulang ke `var(--color-*)` / `color-mix()` (mapping eksak:
  banner 62%/55%, wash 10%/6%, alpha-split, oklch→hex untuk palet default).
  Catatan: bentuknya kompilasi (bukan @apply source) — @apply yang dulu
  ada TIDAK bisa direkonstruksi; editing ke depan dilakukan pada CSS var/
  color-mix (sama aturannya).
- `@layer utilities` ← utilitas custom (pull-to-refresh, scan-native, dll)
  + 64 utilitas yang dulu hanya ter-generate via @apply (komplemen tetap).
- 10 warna satu-off scanner/native yang tidak terpetakan jadi token baru
  di `@theme` dengan NAMA SEMANTIK per peran (nilai hex literal tetap;
  audit + token-sync pass; di-whitelist di check-token-sync sebagai artefak
  recovery).

Verifikasi recovery: build ulang → komponen 0 selector hilang, utilitas 0
hilang, `token:check` PASS, `check-token-sync` OK, smoke test 16/16 PASS,
browser /home + /scan terverifikasi (hero gradien biru→cyan, kartu pastel
module, date-context TIDAK overlap hero, nol console error).

**Limitasi terdokumentasi (hasil recovery):**
- Komponen berbentuk kompilasi — @apply asli hilang. Panduan edit ada di
  header `app.css` (EDITING NOTE).
- LightningCSS me-re-proses komponen yang sudah terkompilasi: sebagian
  pasangan `@supports (color:color-mix(...))` bisa ter-unwrap/ter-urut ulang
  (fallback hex inline tetap ada di rule pertama — browser lama tetap dapat
  warna benar). Diterima; diverifikasi spot-check, bukan per-rule.
- 10 token recovery (warna satu-off scanner/native, fallback literal yang
  punya pasangan `color-mix()` setara di blok `@supports` — nama semantik
  per peran, nilai hex tetap dari bundle):
  `attendance-live` (badge live absensi), `attendance-accent` (teks aksen
  aksi absensi), `banner-attendance-deep` / `banner-payroll-deep` /
  `banner-documents-light` (stop gradien banner modul),
  `module-payroll-deep` (strip/accent deep emerald), `module-payroll-eyebrow`
  (eyebrow emerald), `scan-icon-mint` / `scan-icon-ink` (ikon header scan
  native), `danger-copy` (teks section danger profil).
  **`banner-payroll-deep` (62%) vs `module-payroll-deep` (78%) sengaja BEDA**
  — keduanya = `color-mix(module-payroll, primary-900)` beda persentase:
  62% (stop gelap banner, kontras teks putih 5.22:1 PASS WCAG AA ≥4.5) vs
  78% (strip/accent card tanpa teks putih, boleh lebih terang 3.79:1).
  JANGAN digabung — 78% di banner gagal kontras, 62% di strip ubah visual.

## Icons: Heroicon-only rule (2026-08-06)

Keputusan Fikih 2026-08-06 (migrasi 79 inline SVG → komponen, 34 blade):
SEMUA icon aplikasi memakai komponen **Heroicon** (`<x-heroicon-o-*>`
outline / `<x-heroicon-m-*>` mini / `<x-heroicon-s-*>` solid) — dilarang
`<svg>` inline manual. Rule `raw_inline_svg` di `scripts/check-ui-rules.php`
men-flag setiap `<svg>` mentah di blade normal app UI sebagai blocking
(script sudah masuk `composer ci:check`).

- Komponen tersedia dari `vendor/blade-ui-kit/blade-heroicons` (sudah di
  repo). Atribut `class`, `x-show`, `id`, `title` dll diteruskan normal ke
  komponen.
- Ikon berwarna dinamis: set warna via class utility token (`text-module-*`,
  `text-gray-*`, dst), bukan attribute `stroke`/`fill` hex di inline svg.
- Pengecualian whitelist `raw_inline_svg` (wajib raw, HANYA 5 file): 4 brand
  logo (application-logo, application-mark, app-logo-icon,
  authentication-card-logo — path brand, bukan icon) + 1 gauge chart
  analytics (linearGradient + stroke-dasharray, data-viz bukan icon).
- Kelas icon wajib diberi ukuran (`h-4 w-4` / `h-5 w-5` dst) — komponen
  Heroicon default `h-5 w-5` tapi jangan bergantung pada default.

### Spinner loading: `o-arrow-path`, bukan arc circle+path (2026-08-06)

Pattern spinner DIGANTI dari SVG arc manual:

```html
<!-- ❌ LAMA — arc spinner manual (circle + path) -->
<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>

<!-- ✅ BARU — Heroicon arrow-path + animate-spin -->
<x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
```

Alasan: konsisten dengan pattern existing (settings, face-enrollment),
selaras dengan Heroicon-only rule, dan `animate-spin` di arrow-loop tetap
terbaca jelas sebagai loading. Substitusi visual DISENGAJA (arrow loop,
bukan arc) — jangan dikembalikan ke circle+path manual. Spinner dengan
ukuran lebih besar (mis. `h-10 w-10`) cukup tambah class ukuran.

## Typography
- Display: Rubik, Inter, sans-serif — weight 600, style normal
- Body:    Inter, system-ui, sans-serif — weight 400/500/600
- Mono:    Monaco, Consolas, monospace — for code/technical
- Display tracking: -0.02em (tight)
- Type scale anchor: --text-display = clamp(2.25rem, 4vw + 1rem, 3.5rem)

## Spacing
4-point named scale (Tailwind-compatible):
--space-3xs: 0.25rem (4px)  --space-2xs: 0.5rem (8px)  --space-xs: 0.75rem (12px)
--space-sm:  1rem (16px)     --space-md:  1.5rem (24px) --space-lg: 2rem (32px)
--space-xl:  3rem (48px)     --space-2xl: 4.5rem (72px) --space-3xl: 7rem (112px)

## Motion
- Easings: --ease-out: cubic-bezier(0.16, 1, 0.3, 1); --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1)
- Reveal pattern: fade + subtle slide (translateY 4px → 0) on mount; NO scroll reveals
- Reduced-motion fallback: opacity-only crossfade ≤ 150ms

## Microinteractions stance
- Silent success (no toast) for visible mutations; toast only for failures/invisible effects
- Hover delay: 800ms; Focus delay: 0ms (instant focus ring)
- 8-state discipline on every interactive: default · hover · focus-visible · active · disabled · loading · error · success

## CTA voice
- Primary: fill `--color-accent`, rounded-xl (12px), px-6 py-2.5, font-semibold, no border
- Secondary: outline `--color-rule` border, bg `--color-paper`, text `--color-ink`, rounded-xl, px-6 py-2.5
- Danger: bg `--color-error/10`, text `--color-error`, border `--color-error/30`, rounded-xl

## Per-page allowances
- Marketing pages MAY use Tier-A CSS art enrichment (clipped-edge, geometric accent)
- App pages MUST NOT use enrichment — function carries the page
  (EXCEPTION 2026-08-06: user-facing ESS page headers & home hero MAY use
  Solid Gradient Banners — see section above; admin app pages keep this rule)
- Content pages: typography only

## What pages MUST share
- Wordmark "PasPapan" / HRConnect logotype
- Chrome = neutral dark ink; module hue placement lebih luas di kartu/header
  (AMENDED 2026-08-06 — dulu "accent ≤5% per viewport" untuk brand blue)
- Display + body font pairing (Rubik + Inter)
- CTA voice (button shape, radius, padding rhythm)
- Section heading rhythm: eyebrow (ess-eyebrow) + display heading

## What pages MAY differ on
- Macrostructure within family (Marquee Hero vs Long Document for marketing)
- Hero archetype (within family allowance)
- Enrichment — only marketing, only Tier-A/Tier-B (+ user-facing ESS banner headers per "Solid Gradient Banners" 2026-08-06)

## Exports

### tokens.css (CSS custom properties)
:root {
  --color-paper:      oklch(100% 0 0);
  --color-paper-2:    oklch(98.5% 0.003 240);
  --color-paper-3:    oklch(94% 0.006 240);
  --color-ink:        oklch(20% 0.02 255);
  --color-ink-2:      oklch(45% 0.02 255);
  --color-rule:       oklch(88% 0.01 240);
  --color-accent:     oklch(24% 0.03 260);   /* #1E293B neutral dark (2026-08-06) */
  --color-accent-ink: oklch(100% 0 0);
  --color-focus:      oklch(34% 0.03 260);
  --color-success:    oklch(60% 0.18 150);
  --color-warning:    oklch(75% 0.18 85);
  --color-error:      oklch(58% 0.22 25);

  --color-module-hr:         oklch(58% 0.22 260);
  --color-module-attendance: oklch(70% 0.14 200);
  --color-module-leave:      oklch(60% 0.20 295);
  --color-module-payroll:    oklch(65% 0.17 160);
  --color-module-documents:  oklch(70% 0.17 75);
  --color-module-reports:    oklch(60% 0.18 230);
  --color-module-kb:         oklch(60% 0.22 330);
  --color-module-finance:    oklch(65% 0.20 20);

  --font-display: "Rubik", "Inter", sans-serif;
  --font-body:    "Inter", system-ui, sans-serif;
  --font-mono:    "Monaco", "Consolas", monospace;

  --space-3xs: 0.25rem;  --space-2xs: 0.5rem;  --space-xs: 0.75rem;
  --space-sm:  1rem;     --space-md:  1.5rem;  --space-lg: 2rem;
  --space-xl:  3rem;     --space-2xl: 4.5rem;  --space-3xl: 7rem;

  --text-xs: 0.75rem;   --text-sm: 0.875rem; --text-md: 1rem;
  --text-lg: 1.125rem;  --text-xl: 1.25rem;  --text-2xl: 1.5rem;
  --text-display: clamp(2.25rem, 4vw + 1rem, 3.5rem);

  --ease-out:     cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in-out:  cubic-bezier(0.4, 0, 0.2, 1);
  --dur-short:    220ms;
  --radius-card:  16px;
  --radius-pill:  9999px;
  --radius-input: 12px;
}

### Tailwind v4 @theme (for app.css)
@theme {
  --color-paper:      oklch(100% 0 0);
  --color-paper-2:    oklch(98.5% 0.003 240);
  --color-paper-3:    oklch(94% 0.006 240);
  --color-ink:        oklch(20% 0.02 255);
  --color-ink-2:      oklch(45% 0.02 255);
  --color-rule:       oklch(88% 0.01 240);
  --color-accent:     oklch(24% 0.03 260);   /* #1E293B neutral dark (2026-08-06) */
  --color-accent-ink: oklch(100% 0 0);
  --color-focus:      oklch(34% 0.03 260);
  --color-success:    oklch(60% 0.18 150);
  --color-warning:    oklch(75% 0.18 85);
  --color-error:      oklch(58% 0.22 25);

  --color-module-hr:         oklch(58% 0.22 260);
  --color-module-attendance: oklch(70% 0.14 200);
  --color-module-leave:      oklch(60% 0.20 295);
  --color-module-payroll:    oklch(65% 0.17 160);
  --color-module-documents:  oklch(70% 0.17 75);
  --color-module-reports:    oklch(60% 0.18 230);
  --color-module-kb:         oklch(60% 0.22 330);
  --color-module-finance:    oklch(65% 0.20 20);

  --font-display: "Rubik", "Inter", sans-serif;
  --font-body:    "Inter", system-ui, sans-serif;
  --font-mono:    "Monaco", "Consolas", monospace;

  --spacing-3xs: 0.25rem;  --spacing-2xs: 0.5rem;  --spacing-xs: 0.75rem;
  --spacing-sm:  1rem;     --spacing-md:  1.5rem;  --spacing-lg: 2rem;
  --spacing-xl:  3rem;     --spacing-2xl: 4.5rem;  --spacing-3xl: 7rem;

  --text-xs: 0.75rem;   --text-sm: 0.875rem; --text-md: 1rem;
  --text-lg: 1.125rem;  --text-xl: 1.25rem;  --text-2xl: 1.5rem;
  --text-display: clamp(2.25rem, 4vw + 1rem, 3.5rem);

  --ease-out:     cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in-out:  cubic-bezier(0.4, 0, 0.2, 1);
  --dur-short:    220ms;
  --radius-card:  16px;
  --radius-pill:  9999px;
  --radius-input: 12px;
}