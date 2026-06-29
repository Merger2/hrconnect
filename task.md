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

## 🔴 E1 — API Leaves & Reimbursements endpoint TIDAK ADA

**Severity:** Critical
**Issue:** Tidak ada route `/api/v1/leaves` atau `/api/v1/reimbursements`. Semua URL ini return 404 "Resource tidak ditemukan".
**CURL Test:**
```
GET /api/v1/leaves        → 404 Resource tidak ditemukan
GET /api/v1/reimbursements → 404 Resource tidak ditemukan
```
**Akar masalah:** `routes/api.php` tidak mendaftarkan API resource untuk Leaves dan Reimbursements. Web routes (`/leaves`, `/reimbursements`) via Blade/Livewire berfungsi normal (200).
**Dampak:** Mobile/third-party integrations tidak bisa akses data leave dan reimbursement.
**Fix:** Tambah route group di `routes/api.php`:
```php
Route::apiResource('leaves', LeaveController::class)->middleware('auth:sanctum');
Route::apiResource('reimbursements', ReimbursementController::class)->middleware('auth:sanctum');
```

## 🟠 E2 — Demo users tidak punya employee record

**Severity:** High
**Issue:** 4 user demo tidak punya baris di tabel `employees`:
| User | ID | Role | Employee ID |
|------|:--:|------|:-----------:|
| Super Admin | 1 | super-admin + hr-manager | `null` |
| Andi Manager | 50 | manager | `null` |
| Budi Staff | 51 | employee | `null` |
| Citra Finance | 52 | finance | `null` |

**Akar masalah:** Seed data `DatabaseSeeder.php` bikin user tapi `EmployeeSeeder.php` bikin user terpisah (2-47). Demo user di `UserFactory` tidak punya relasi Employee.
**Dampak:**
- `GET /api/v1/attendance/today` → `"Akun Anda belum terhubung dengan data karyawan."`
- `GET /api/v1/approvals/pending` → `"Akun Anda belum terhubung dengan data karyawan."`
- Semua endpoint employee-specific tidak bisa diakses oleh akun demo
- Manager tidak bisa lihat team approvals

**Fix:** Tambah seeder untuk bikin Employee record untuk user id 1, 50, 51, 52.

## 🟠 E3 — password_changed_at null → redirect loop

**Severity:** High
**Issue:** Semua seeded employee (`password_changed_at` = null) redirect ke `/settings/security` setelah login.
**Yang tidak kena redirect:**
- Super Admin (id=1): `password_changed_at = 2026-06-27 22:53:16`
- Demo users (50, 51, 52): tidak redirect (password factory set `password_changed_at`)
**Yang kena redirect (tidak bisa akses fitur):**
- Semua employee real (id 2-47) via `EmployeeSeeder` — tidak set `password_changed_at`

**Akar masalah:** Middleware ForceChangePassword mengecek `password_changed_at === null`. EmployeeSeeder panggil `UserFactory` tanpa set `password_changed_at`.
**Fix:** Tambah `'password_changed_at' => now()` di `EmployeeSeeder` atau middleware skip untuk user dengan role employee (opsional).

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

## 🟡 E7 — Database cache lock error

**Severity:** Medium
**Log:** `SQLSTATE[42601]: Syntax error: 7 ERROR: zero-length delimited identifier at or near """" LINE 1: update "" set ...`
**Akar masalah:** `CACHE_STORE=database` — error pada cache lock query dengan table name kosong. Terjadi saat multiple request mengakses cache lock bersamaan.
**Fix:** Override lock driver atau tambah table name prefix di config cache.

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
| **P1** | **HR-Manager** | E2 (employee records), E6 (seed data), J1 ✅, J2 ✅ |
| **P2** | **Finance** | E1 (leaves/reimbursements API), T1, T2, A1, A2 |
| **P3** | **Manager** | E3 (password redirect), T6, S1, B1-B3 |
| **P4** | **Employee** | E4 (route naming), T4, C1, S2 |
