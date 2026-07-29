# CONVENTIONS.md — HRConnect Coding Conventions

## Wajib diikuti, jangan improvisasi. Contoh dari kode aktual.

### ATURAN EMAS #1: Cari pola existing SEBELUM nulis kode baru

Sebelum nulis kode baru, cari 1-2 contoh existing yang polanya mirip di
codebase (misal kalau bikin service baru, cari service lain yang struktur
tugasnya mirip), dan IKUTI POLA YANG SAMA PERSIS — penamaan method,
error handling, struktur constructor. Sebutkan file referensi yang kamu
pakai sebagai acuan pola sebelum kasih kode.

### ATURAN EMAS #2: Kalau gak ada pola existing yang cocok, JANGAN improvisasi sendiri

Kalau situasinya beneran baru dan gak ada contoh serupa di codebase,
STOP — tanya saya dulu sebelum bikin pola baru. Jangan menebak "kira-kira
gaya project ini gimana". Kalau terpaksa harus jalan tanpa konfirmasi,
tandai eksplisit di laporan: *"⚠️ Tidak ada pola existing, saya membuat
pendekatan baru — mohon di-review lebih ketat."*

### ATURAN EMAS #3: Definition of Done

Sebuah task dianggap SELESAI hanya kalau semua ini terpenuhi:
1. Perubahan sudah diterapkan di semua caller lama (grep dulu, jangan
   sisain reference ke field/method yang sudah diganti)
2. Test terkait (kalau ada) pass
3. `vendor/bin/pint --dirty --format agent` pass tanpa error
4. Kasih diff summary: file apa aja yang berubah dan kenapa (bukan
   cuma "sudah selesai")

Jangan laporkan "done" kalau salah satu poin di atas belum tercek.

---

## 1. Model

### PII Encryption (CipherSweet)
Model dengan field PII (nik, npwp, phone, bank_account) WAJIB:
- Implement `CipherSweetEncrypted`
- Gunakan `UsesCipherSweet` trait
- Return encrypted fields dari `allowedFields()`

✅ **Referensi:** `app/Models/Employee.php:54-56,108-121`
```php
use App\Concerns\CipherSweetEncrypted;
use ParagonIE\CipherSweet\Backend\Key\ManagedKey;
// ...
class Employee extends Model implements CipherSweetEncrypted
{
    use HasFactory, SoftDeletes, UsesCipherSweet;

    public static function allowedFields(): array
    {
        return ['phone', 'nik', 'npwp', 'bank_account_number'];
    }
```

✅ **Contoh kedua:** `app/Models/FamilyDetail.php:22-24`
❌ Model tanpa PII (`Division.php:16-18`) — cukup `HasFactory, SoftDeletes`.

### Foreign Key Penamaan
- WAJIB `{relasi}_id` — `user_id`, `employee_id`, `approver_id`
- Jangan `id_user`, `idKaryawan`, atau `userId`
- FK role-based pakai nama peran: `approver_id`, `reviewer_id`, `verified_by`, `created_by`, `approved_by`

✅ **Contoh:** `approvals.php:16` → `foreignId('approver_id')`
✅ **Contoh:** `migrations/employees.php:15` → `foreignId('user_id')`

### $fillable — urutan
- Kolom identitas dulu → data utama → status/timestamps
- Urut sesuai migration column order

✅ **Referensi:** `app/Models/Division.php:20-25`
```php
protected $fillable = [
    'company_id', 'name', 'code', 'head_id', // identitas & data
    'is_active',                              // status
];
```

### HasFactory
- Semua model WAJIB `use HasFactory`
- Kalau perlu factory class eksplisit, pakai `/** @use HasFactory<ModelFactory> */`
- Status progres migrasi model lama ke HasFactory dilacak terpisah di
  `PROGRESS.md`, BUKAN di file ini — file ini soal aturan, bukan status.

✅ **Referensi:** `app/Models/User.php:30`

### SoftDeletes & Timestamps
- `timestamps()` + `softDeletes()` di akhir (bukan di tengah)
- Cuma tambah soft delete kalau PRD butuh — jangan asal pasang
- `softDeletes()` di `$casts` → `'deleted_at' => 'datetime'`

✅ **Referensi:** `app/Models/Employee.php:87-88`

### Relasi
- `belongsTo` pakai foreign key eksplisit (parameter ke-2)
- `hasMany` pakai local key eksplisit kalau beda dari default

✅ **Referensi:** `app/Models/Attendance.php:63-65`

### HasUlids
- HANYA untuk model yang datanya dikonsumsi/diproses sistem eksternal
  (`IntegrationClient`, `IntegrationDelivery`, `IntegrationEndpoint`).
  Alasan: ID sequential (auto-increment) bisa ditebak/di-enumerate pihak
  luar (security risk) dan berpotensi collision kalau ada multiple
  sumber data yang generate ID secara independen. ULID aman dipakai
  di konteks ini karena sortable by time tapi tidak predictable.
- Model regular (internal-facing, gak exposed ke API luar) WAJIB
  auto-increment `id` — JANGAN pakai HasUlids kecuali ada alasan
  serupa di atas.

---

## 2. Migration

### Column Ordering
- `id()` dulu → foreign keys → data columns → audit FKs → `timestamps()` + `softDeletes()`
- Pakai `// --- SECTION ---` divider buat grup logis
- `$table->...after('kolom')` hanya untuk alteration migration

✅ **Referensi:** `database/migrations/2026_04_16_192201_create_employees_table.php`
```
id()
// --- DATA PRIBADI ---
nama, nik, ...
// --- ALAMAT ---
alamat, provinsi, ...
// --- AUDIT TRAIL ---
timestamps()
softDeletes()
```

### Foreign Key Constraints
- WAJIB `->constrained('table')` eksplisit kalau nama gak cocok
- WAJIB `->cascadeOnDelete()` / `->nullOnDelete()` / `->restrictOnDelete()` — jangan dikosongin

✅ **Contoh:**
```php
$table->foreignId('approver_id')->nullable()->constrained('employees')->nullOnDelete();
$table->foreignId('user_id')->constrained()->cascadeOnDelete(); // auto-resolve ke users
```

### Timestamps & SoftDeletes
- Selalu `$table->timestamps();` lalu `$table->softDeletes();` (kalau perlu)
- Jangan pisah-pisah

---

## 3. Service

### Error Handling
Service method yang bisa gagal WAJIB throw custom exception — JANGAN return null/false/array kosong.

✅ **BENAR:** `app/Services/Attendance/AttendanceService.php:53`
```php
throw new BusinessRuleException('Catatan WFA wajib diisi minimal 20 karakter');
```

✅ **Exception classes available:**
| Exception | Code | Use Case |
|-----------|------|----------|
| `BusinessRuleException` | 422 | Domain rule violations (default) |
| `FaceNotRecognizedException` | 422 | Face match failure |
| `FaceNotRegisteredException` | 422 | No face enrolled |
| `GeoFenceViolationException` | 403 | Outside geofence |
| `AntiFakeGPSException` | 422 | Mock GPS detected |
| `AlreadyClockedInException` | 409 | Duplicate clock-in |
| `NotClockedInException` | 409 | Clock-out without clock-in |

Semua exception punya method `render()` → json response `{status: 'error', message: ...}`.

Kalau butuh exception baru di luar daftar ini, cek ATURAN EMAS #2 —
jangan asal `throw new Exception(...)` generik.

### Constructor Injection
- WAJIB constructor injection — JANGAN `new Class()` manual
- Service dependencies (GeofenceService, FaceRecognitionService, ApprovalService, dll) lewat constructor

✅ **Referensi:** `app/Services/Attendance/AttendanceService.php:32-36`
✅ **Referensi:** `app/Services/Payroll/PayrollCalculatorService.php:37-39`

❌ **Anti-pattern (technical debt, BUKAN contoh untuk ditiru):**
`app/Services/Payroll/HitungGajiService.php:30` — empty constructor +
manual `new`. File ini masih ada di codebase sebagai warisan lama dan
belum di-refactor. Kalau kamu sedang menyentuh file ini untuk alasan
lain, perbaiki sekalian ke pola constructor injection. Kalau tidak
sedang menyentuhnya, jangan jadikan contoh pola untuk kode baru.

### Naming
- Method names: verbs in camelCase — `calculatePayroll()`, `approve()`, `ingest()`
- Jangan `process_data()` atau `ProcessData()` — konsisten camelCase

---

## 4. Livewire

### Authorization
- `mount()` atau `boot()` WAJIB panggil `$this->authorize()` atau `Gate::authorize()` untuk action write
- Komponen admin gunakan gate governance (`manageLeaveTypes`, `accessAdminPanel`, dll)
- Komponen user gunakan policy (`viewAny`, `create`, `approve`)

✅ **Referensi:** `app/Livewire/Admin/MasterData/LeaveTypeManager.php:53`
```php
public function boot(): void
{
    Gate::authorize('manageLeaveTypes');
}
```

✅ **Referensi:** `app/Livewire/User/MyPayslips.php:25`
```php
public function mount(): void
{
    $this->authorize('viewAny', Payroll::class);
}
```

### Validation
- Semua public property yang nerima input user WAJIB ada `rules()` method
- `rules()` bisa instance method atau property `$rules`
- Pakai `Rule::unique`, `Rule::in` untuk validasi kompleks

✅ **Referensi:** `app/Livewire/Admin/MasterData/LeaveTypeManager.php:56-72`
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'code' => ['required', Rule::unique('leave_types', 'code')->ignore($this->leaveType?->id)],
        'max_days' => ['required', 'integer', 'min:0'],
    ];
}
```

### Eager Loading
- Query di loop WAJIB eager load — jangan N+1
- Gunakan `->with(['relation1', 'relation2'])` sebelum `->get()`

✅ **Referensi:** `app/Livewire/Admin/EmployeeComponent.php:245`
```php
->with(['division', 'jobTitle', 'education', 'directManager'])
```

✅ Daftar properti [Computed] untuk cache:
```php
#[Computed]
public function payslips(): Collection
{
    return $this->employee->payslips()->with('payroll')->get();
}
```

### Layout
- Admin component: `#[Layout('layouts.admin')]`
- User component: `#[Layout('layouts.app')]`

✅ **Referensi:** `app/Livewire/User/MyPayslips.php:12`

---

## 5. Routes

### Middleware
| Grup | Middleware | File |
|------|-----------|------|
| Admin UI | `['admin', 'can:accessAdminPanel']` | `routes/web/admin.php` |
| API auth | `auth:sanctum` | `routes/api.php` |
| User UI | `['web', 'auth', 'verified', 'user']` | `routes/web/user.php` |

✅ **Referensi:** `routes/web/payroll.php:18`
```php
Route::middleware(['admin', 'can:accessAdminPanel'])->group(function () { ...
```

### Route Registration
- `routes/web.php`: only loads includes, no inline routes
- `routes/web/admin.php`: includes admin/*.php files
- Setiap sub-module punya file sendiri (`payroll.php`, `files.php`, `system.php`, dll)
- Jangan daftarin route di file yang salah grup

---

## 6. Enums

### Kapan pakai native PHP enum vs string constant
- Untuk field dengan set nilai tetap dan terbatas (status, tipe, level) —
  WAJIB native PHP backed enum (`enum X: string`), BUKAN string constant
  atau magic string langsung di kode.
- Simpan di `app/Enums/`, satu file per enum.

### Penamaan
- Nama class: PascalCase, singular — `ApprovalStatus`, bukan
  `ApprovalStatuses` atau `approval_status`
- Enum case: PascalCase — `case Pending`, `case Approved`
- Value (backing string): snake_case lowercase — `'pending'`, `'approved'`

✅ **Contoh:**
```php
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

### Pemakaian di Model
- WAJIB didaftarkan di `$casts`, jangan biarkan kolom enum diperlakukan
  sebagai string biasa di model:
```php
protected $casts = [
    'status' => ApprovalStatus::class,
];
```

### Referensi silang
- Kalau nambah enum baru, cek dulu apakah sudah ada enum serupa di
  `app/Enums/` (ada 28 enum saat ini) sebelum bikin baru — hindari
  duplikasi konsep (misal jangan bikin `RequestStatus` baru kalau
  `ApprovalStatus` sudah mencakup kasus yang sama).

---

## 7. Blade Views

### Struktur Folder
- View untuk Livewire component: `resources/views/livewire/{admin|user}/{module}/{nama-component}.blade.php`
  — struktur folder mengikuti namespace komponen PHP-nya persis.
- Partial yang dipakai berulang lintas module: `resources/views/components/`
  sebagai Blade component (`<x-...>`), BUKAN `@include` biasa.
- `@include` hanya untuk partial yang sifatnya lokal ke satu view saja
  dan tidak dipakai di tempat lain.

### Blade Component vs @include
- Kalau partial butuh terima data terstruktur (props) dan dipakai di
  lebih dari satu tempat → Blade component (`<x-card-stat :value="..." />`)
- Kalau partial cuma motong panjang satu file tanpa reuse → `@include`

---

## 8. Testing

### Structure
- Feature tests: `tests/Feature/{Module}Test.php`
- Unit tests: `tests/Unit/{Service}Test.php`
- Livewire tests: `tests/Feature/Livewire/{Component}Test.php`

### Factories
- Semua model WAJIB punya factory (`HasFactory` + `database/factories/ModelFactory.php`)
- Factory definition WAJIB match migration columns

### Authorization tests
- Tiap endpoint write WAJIB test: unauthorized (403), validation (422), success (200/201)

### Query count
- Test halaman list WAJIB assert query count via `HttpTest::withoutExceptionHandling()` atau `DB::enableQueryLog()`

---

## 9. Laravel Pint (Wajib Running)
Jalankan `vendor/bin/pint --dirty` SETELAH tiap perubahan dan SEBELUM lapor selesai.

```bash
vendor/bin/pint --dirty --format agent
```

Pastikan output `{"result":"pass"}` atau `{"result":"fixed"}` — jangan lanjut kalau masih error.

---

## Catatan Perawatan Dokumen Ini

- File ini berisi ATURAN, bukan STATUS. Progress migrasi/refactor
  (misal "berapa model sudah pakai HasFactory") dilacak di file terpisah
  (`PROGRESS.md`) supaya CONVENTIONS.md tidak cepat basi.
- Kalau ada aturan baru yang disepakati saat audit/fix (bukan cuma
  sekali pakai), tambahkan ke sini supaya konsisten dipakai agent
  berikutnya — jangan biarkan aturan cuma hidup di riwayat chat.