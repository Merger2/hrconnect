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
