# Audit HRConnect — SATU-SATUNYA file audit (canonical)

> **Konsolidasi 2026-08-04:** file ini menggantikan `AUDIT-2026-07-30.md` (root + docs/), `docs/audit-checklist.md`, `docs/AUDIT-MAP.md`, `docs/UI-AUDIT.md` — semuanya dihapus. Temuan lama yang masih relevan sudah terserap di ledger di bawah ini. Jangan buat file audit baru — update file ini.

---

# Audit Findings — 2026-08-04 (Full Codebase Read + Test Triage)

**Generated:** 2026-08-04 | **Method:** 18 codemap lanes (1.146 file produksi dibaca) + 4 triage lanes (128 kegagalan test) + verifikasi langsung (grep/read/route:list) | **Status skor readiness: ~50-55%**

---

## Ringkasan Eksekutif

- **±80 temuan** dikumpulkan: 5-7 kritis (sebagian terverifikasi), sisanya medium/minor
- **Verifikasi:** hanya item bertanda ✅ yang dicek langsung orchestrator; ⚠️ = klaim satu-lane (kredibel, belum dicek ulang) — **verifikasi sebelum fix** adalah aturan wajib
- **4+ catatan PROGRESS terbukti stale** (lihat bagian bawah) — dokumen status tidak bisa dipercaya tanpa verifikasi
- **±31 perbaikan sudah diterapkan** sesi ini (lihat "Fix Terapan")
- **Blade/design:** 9 dari 12 klaim diverifikasi langsung — semuanya VALID (payroll-manager luar design system, login legacy, initUiPickers undefined, 11 blade orphan, dll)

---

## ✅ Fix Terapan Sesi Ini (2026-08-04, commit `436634d` + working tree)

| # | Fix | Status |
|---|-----|:------:|
| 1 | Fatal test suite: duplikat `createLeaveTestUser` → `createLeaveValidationUser` (suite jalan lagi: 492 pass / 128 fail) | ✅ |
| 2 | 🔴 P0: `hasLegacyAdminPermission` denylist `view_custom_forms`+`view_operations_workspace` (403→200 bocor) | ✅ |
| 3 | Gate undefined `manageDocumentTemplates` → `viewAdminDocumentRequests` (routes + library) | ✅ |
| 4 | FK `projects.branch_id` → `company_branches` (migration 2026_08_04_000001) + `Project::branch()` → CompanyBranch | ✅ |
| 5 | API payroll `/api/v1/payrolls` gate → policy `viewAny`/`view` (self-service employee) | ✅ |
| 6 | `ReimbursementAttachmentController` `attachment` → `attachment_path` (download 404) | ✅ |
| 7 | `Announcement::scopeVisibleForUser` + filter `dismissedByUsers` | ✅ |
| 8 | `LeaveType` fillable 6 kolom + casts (silent data drop) | ✅ |
| 9 | `ScheduleRosterExport` department → division | ✅ |
| 10 | Observer hash `(string)id` → int (Employee + Payroll — audit trail integrity) | ✅ |
| 11 | Migration `company_asset_histories.created_by` nullable | ✅ |
| 12 | Migration `employees.position_id` nullable | ✅ |
| 13 | Migration `project_visit_evidences` 12 kolom + model (MyOperationalTasks 500) | ✅ |
| 14 | `Education` deleting hook — kolom phantom `users.education_id` dihapus | ✅ |
| 15 | `ShiftForm` delete: detach attendances/employees/corrections + hapus schedules | ✅ |
| 16 | Jobs export `department` → `division` (2 file, RelationNotFoundException) | ✅ |
| 17 | 3 export silent-null department → division | ✅ |
| 18 | `AttendanceImportExport` `education_id` → `education_level` + Rule::in + dropdown enum | ✅ |
| 19 | Hapus `IntegrationWebhookServiceTest` (fitur tidak pernah ada) | ✅ |
| 20 | `FakeDataSeederTest`: hapus 2 test paspapan, rewrite idempotency (assert aktual) | ✅ |
| 21 | `CompanyEmployeesSeeder` idempotent (firstOrCreate + NOT NULL fields) | ✅ |
| 22 | `CompanyAndDivisionSeeder`: JobLevel (5) + JobTitle (5) di-seed | ✅ |
| 23 | `DemoAttendanceSeeder` baru (30 hari kerja, 50 employee, idempotent) | ✅ |
| 24 | `config/navigation.php` phantom "Monitoring Dashboard" dihapus | ✅ |
| 25 | `all_routes.txt` stale dihapus | ✅ |
| 26 | Dead code: 2 controller deprecated + `Shared\NotificationsPage` + blade it-support | ✅ |
| 27 | `app.css` dead CSS −802 baris + fix build break `text-red-955` (des-1 + orchestrator) | ✅ |
| 28 | Layout UI: history + profile/show (heroicon, border-b-0, surface) | ✅ |
| 29 | AGENTS.md rewrite + `.env.testing` di-untrack (kredensial asli keluar dari git) | ✅ |
| 30 | Golden test payroll: harness + fixture 25 case (skip sampai angka referensi) | ✅ |
| 31 | `config/navigation.php` — 22/22 route valid (0 missing via route:list), item "Pinjaman" dihapus (fitur hanya API), 'Slip Gaji' → `my-payslips` | ✅ |
| 32 | 11 blade admin orphan dihapus (2 dipertahankan: attendances/index ter-route, employees/index deprecated) | ✅ |
| 33 | `window.initUiPickers` diimplementasi di app.js (re-init flatpickr/tom-select dalam modal) | ✅ |
| 34 | `tomSelectInput` call sites dirapikan 8/9 → 4 arg | ✅ |
| 35 | K2 — route gate `viewAdminPayroll` → `view_payrolls` (export payroll report 403 → berfungsi) | ✅ |
| 36 | K1 — `ApprovalController::index` branch `all` → `abort_unless(isSuperadmin/isAdmin, 403)` | ✅ |
| 37 | K6 — `ReimbursementController::categories` → `authorize('viewAny')` | ✅ |
| 38 | K4 — `EmployeePiiResource` masking phone/nik/npwp/bank (pola ProfileResource) | ✅ |
| 39 | K3 — `RedirectLockedEnterpriseFeature` diimplementasi (Setting `feature.<name>` truthy → redirect; default unlock) | ✅ |
| 40 | **Golden test payroll 26/26 PASS** — harness disesuaikan schema fixture (nested bpjs, travelTo freeze, for-loop fix `range(1,0)`, assertEqualsWithDelta); angka referensi Claude tervalidasi terhadap kode nyata | ✅ |
| 41 | Payroll bug ditemukan golden test: (a) ytd `sum()` pgsql → string vs `: float` = **TypeError Desember/terminasi 500** (cast float); (b) L606 denda kehadiran mengurangi dasar PPh21 — **bukan pengurang pajak** (PMK 168/2023), konsisten dgn annual true-up (hapus `- attendancePenalty`); (c) L441 `diffInMonths` Carbon 3 float → cast `(int)` konsisten calculatePesangon (kompensasi PKWT bulan penuh) | ✅ |

---

## 🔴 Kritis (belum di-fix)

| # | Temuan | Verifikasi | Sumber |
|---|--------|:----------:|--------|
| K1 | `ApprovalController::index` dengan `?all=true` — pending approval lintas org tanpa gate tambahan (hanya auth:sanctum) | ⚠️ | ✅ **FIXED** (abort_unless isSuperadmin/isAdmin di branch `all`) |
| K2 | Route `admin.reports.payrolls.export` pakai gate **`viewAdminPayroll` yang TIDAK ada** di enum → **403 untuk semua user** (export payroll report mati) | ✅ | ✅ **FIXED** (route gate → `view_payrolls` — konsisten dgn authorize controller) |
| K3 | **`feature.lock` middleware NO-OP** — `RedirectLockedEnterpriseFeature::handle()` = `return $next($request)`, dipakai 29× di routes, boundary enterprise tidak di-enforce | ✅ (2 sumber) | ✅ **FIXED** (implementasi: Setting `feature.<name>` truthy → redirect redirectRoute; default unlock = aman) |
| K4 | `EmployeePiiResource` expose NIK/NPWP/bank mentah (admin-only path, kandidat data leak) | ⚠️ | ✅ **FIXED** (masking phone/nik/npwp/bank — pola ProfileResource) |
| K5 | `CommercialWorkspaceService::salesSummaryForCompanies()` hardcoded return 0 — kartu pipeline CommandCenter = data mati (silent degradation) | ⚠️ | ⚠️ **OPEN** (perlu keputusan: implement query nyata vs hapus kartu) |
| K6 | `ReimbursementController::categories` tanpa gate sama sekali | ⚠️ | ✅ **FIXED** (authorize viewAny; catatan: method TIDAK punya route — unreachable, aman berlapis) |
| K7 | `navigation.php` — 22 dari ~24 route name tidak ada → link sidebar 404 (B20 konfirmasi) | ✅ | ✅ **FIXED** (fix-26: 22/22 route valid via route:list; item Pinjaman dihapus — fitur hanya API; 'Slip Gaji' → `my-payslips` — route `payroll` memang tidak ada) |

---

## 🟡 Medium (dikelompokkan per modul)

### Payroll & Pajak
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M1 | True-up PPh21: lembur dobel hitung (`gross_salary + overtime_pay` padahal gross sudah termasuk lembur) + reimburse ikut annual tapi tidak di basis TER bulanan | ✅ (baca kode penuh) |
| M2 | `calculateAnnualPPh21Progressive` tanpa biaya jabatan (deviasi PMK 168/2023) | ✅ |
| M3 | Tabel pesangon = UU 13/2003 (bukan PP 35/2021: <1thn=0, 1-6, ≥6=6) + variant `dismissed_severe` 2× ≠ standar | ✅ |
| M4 | Cap JP seeder 9.559.600 (regulasi Maret 2026 ≈ 11jt) | ✅ |
| M5 | `app.timezone` = UTC (HRIS Indonesia tanpa Asia/Jakarta) | ⚠️ fix-22 |
| M6 | `config/payroll.php` mati total (0 pembaca) | ⚠️ fix-22 |
| M7 | Mismatch embedding model: config default `gemini-embedding-001` vs `text-embedding-004` | ⚠️ fix-22 |

### Schema & Data
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M8 | Dual tabel PTKP: `golongan_ptkp` vs `golongan_ptkps` (FK/model tidak nyambung) | ⚠️ fix-21 |
| M9 | Dual implementasi absensi: `CommunityService` vs `AttendanceService` — interface hanya di-binding ke yang lama | ⚠️ fix-15 |
| M10 | lat/long dobel di attendances (`lat_in/long_in` vs `latitude_in/longitude_in`) | ⚠️ fix-21 |
| M11 | Dual scheduling (`Schedule` user_id vs `ShiftSchedule` employee_id) + dual leave quota (`LeaveBalance` vs `LeaveEntitlement`) | ⚠️ fix-8 |
| M12 | **Seeder `PTDayaciptaMandiri*` tidak ter-wire ke DatabaseSeeder** (6 seeder orphan — perusahaan utama tidak ter-seed otomatis) | ⚠️ fix-21 |
| M13 | `projects.client_id` tanpa FK; `KnowledgeBase.embedding` array JSON vs `FaceDescriptor` pgvector | ⚠️ fix-8/21 |
| M14 | `KnowledgeBaseFactory` morph salah ke Attendance; `ApprovalFactory` dangling morph | ⚠️ fix-21 |
| M15 | `database/data/indonesian_hilodays.php` orphan + konflik tanggal Idul Fitri + typo "hilodays" | ⚠️ fix-21 |

### RBAC & Authorization
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M16 | Policies TIDAK ter-register eksplisit (konvensi `Gate::guessPolicyName`) — catatan PROGRESS B10 salah | ✅ |
| M17 | `config/rbac.php` stub kosong → RoleAccessPreview/grouping UI kosong (Tier 3) | ✅ |
| M18 | `UserSessionManager` pakai legacy `allowsAdminPermission()` (bukan enum) | ⚠️ fix-9 |
| M19 | `HrChecklistManager` tanpa authorize di komponen (hanya route can) | ⚠️ fix-9 |
| M20 | Route gate vs komponen gate asimetris di master-data (`manageDivisions` route vs `manageMasterData` komponen) | ⚠️ fix-9 |
| M21 | `EmployeeDocumentRequestPolicy` pakai custom `employeeHasAccess` (inkonsisten 9 policy lain) | ⚠️ fix-18 |
| M22 | 8 komponen user tanpa authorize in-component (ClockInAction, FaceEnrollment, dll — risiko laten) | ⚠️ fix-10 |
| M23 | Route tanpa gate: home, my-schedule, my-tasks, collaboration, my-forms, face.enrollment, notifications | ⚠️ fix-20 |
| M24 | Duplikasi route `admin.announcements` (operations.php:24 + security.php:7 — salah satu menimpa) | ✅ |

### Infra & Reliabilitas
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M25 | **8 middleware dead** (0 referensi): CheckMaintenanceMode, DeviceDetection, EnsureActiveAccount, LogUserActivity, SetLocale, SetUserLocale, ThrottleRequestsByIP, TrackRedisSessions + `User::canAuthenticate()` hardcoded true | ⚠️ fix-14 |
| M26 | **4 jobs dead** (tidak pernah di-dispatch): GeneratePayslipPdfJob, ProcessMonthlyAttendanceReportRun, ProcessEmployeeDocumentUpload, RunSystemBackup | ⚠️ fix-17 |
| M27 | **Scheduler gap**: detect-alpha, detect-chronic-late, reset-leave-quota, cache:warm klaim schedule tapi tidak di routes/console.php | ⚠️ fix-17 |
| M28 | `hrconnect.face_distance_threshold` dead (hardcode 85.0 di FaceRecognitionService) | ⚠️ fix-22 |
| M29 | `laravolt` cache store default redis ≠ CACHE_STORE=database | ⚠️ fix-22 |
| M30 | Backup cluster (8 app-bug, P1): job pg_dump tanpa kredensial, SystemBackupRun tanpa security hooks, SystemMaintenance UI rusak (downloadExistingBackup/restore/queueBackup) | ✅ (triage) |
| M31 | 6 FormRequest tanpa konsumen (ClockInRequest, ClockOutRequest, ListAttendanceRequest, ForgotPasswordRequest, LoginRequest, TwoFactorChallengeRequest) | ⚠️ fix-14 |
| M32 | 4 JS dead: pwa-install.js, location.service.js, mock-location.js, CapacitorDeviceManager.js | ⚠️ fix-25 |

### UI & Blade (9/12 diverifikasi langsung — SEMUA VALID)
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| U1 | `initUiPickers` dipanggil (modal) tapi tidak didefinisikan — silent no-op | ✅ | ✅ **FIXED** (fix-26: implementasi di app.js — re-init flatpickr/tom-select dalam modal) |
| U2 | `payroll-manager.blade.php` di luar design system (tanpa page-shell) | ✅ | ⚠️ **OPEN** — des-1 round-3 deliverable kosong, 0 perubahan (git diff) |
| U3 | Login pakai token legacy (`bg-canvas`/`text-ink`) | ✅ | ⚠️ **OPEN** — des-1 round-3 0 perubahan |
| U4 | `face-registration` = stub "coming soon" tidak di-link | ✅ | ⚠️ **OPEN** — des-1 round-3 0 perubahan |
| U5 | `approvals/index` placeholder tidak ter-render (route → Livewire TeamApprovals) | ✅ | ✅ **FIXED** (fix-26: blade dihapus) |
| U6 | 11 blade admin orphan (dashboard, import-export wrappers, monthly_pdf, attendances/show kosong, master-data wrappers) | ✅ (sebagian) | ✅ **FIXED** (fix-26: 11 dihapus, 2 dipertahankan — attendances/index dirujuk controller ter-route; employees/index dirujuk deprecated) |
| U7 | `tomSelectInput` mismatch (blade kirim > 4 arg, helper 4 param — silent) | ✅ | ✅ **FIXED** (fix-26: call sites dirapikan ke 4 arg) |
| U8 | Dual component stacks (Jetstream root vs namespaced overlays/actions/forms) + branding duplikat | ⚠️ fix-25 |
| U9 | 3 implementasi `<head>` berbeda; `minimal.blade.php` layout kedua tidak dipakai | ⚠️ fix-25 |
| U10 | 8 halaman user raw-Tailwind vs BEM; 5 widget home tanpa surface; `div` bukan `<section>` di team-approvals | ⚠️ fix-24 |
| U11 | `PayrollSubmitted` mail hardcode "Total karyawan: 1"; `ReimbursementRequestedMail` URL ke API bukan web | ⚠️ fix-17 |

### QA & Proses
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| Q1 | **PHPStan 1000+ errors** (dengan baseline sekalipun) | ✅ (run) |
| Q2 | **±85 test masih gagal** (22 app-bug + 38 test-side + Tier 3 tersisa) | ✅ (triage) |
| Q3 | E2E tidak reproducible (`auth.setup.ts` hilang, states gitignored) | ✅ |
| Q4 | CI tidak ada (tests/ + .github/ untracked, DB mismatch) | ✅ |
| Q5 | KB eval dataset ≥20 Q&A / ≥90% relevan — **belum ada di mana pun** (hard gate) | ✅ (grep) |
| Q6 | Test-side: `auth_middleware_stack` tidak ada (B9 stale); `SystemMaintenanceBackupJobsTest`/`SystemBackupRunJobTest` stale | ✅ |

---

## ⚪ Minor / Dead Code (ringkas)

Stub service (`DeviceAttendanceService` uploadPhoto, `SettingsManagementService` license strlen>=32, `CommunityAuditService`), dormant (`PgVector` cast, `Encryptable` trait, `PayrollServiceInterface`, `AttendanceResource`/`ApprovalResource`, `GeolocationService`), deprecated 3 komponen user, envelope API tidak konsisten (`{status}` vs `{success}`), stub-400 endpoints (Leave/Overtime update, Loan payInstallment), `@if(false)` enterprise remnants, `pulltorefresh.js` non-Vite, `tomSelectInput` call sites, 2 gaya validasi (`rules()` vs `#[Validate]`), 2 gaya layout Livewire (`#[Layout]` vs `->layout()`), `#[Fillable]` vs `$fillable` 41/50.

---

## 📌 Catatan PROGRESS yang Terbukti STALE

| Catatan | Klaim | Fakta |
|---------|-------|-------|
| RT2 | Duplikasi `admin.announcements` = FALSE POSITIVE | ✅ **Benar-benar duplikat** (operations.php + security.php) |
| B9 | `auth_middleware_stack()` helper ada | ✅ **Tidak ada di codebase** — stack inline per file |
| B10 | 20 policies ter-register eksplisit | ✅ **Tidak ada** — konvensi guessPolicyName |
| B15 | GeolocationService dihapus | ✅ **Masih ada** (deprecated, 0 caller) |
| B20 | navigation 24 route missing = minor | ✅ Nyata: 22 route phantom → sidebar 404 |

**Aturan baru:** temuan hanya valid setelah verifikasi langsung (grep/read) — jangan percaya dokumen status.

---

## 📋 Proses & Referensi

- **Verifikasi sebelum fix** — setiap item yang akan diperbaiki, baca sumbernya dulu
- **Detail per folder:** `codemap.md` (151 file, local-only, gitignored) + atlas `codemap.md` root
- **Status sesi:** des-1 (blade visual) & fix-26 (orphan blades + JS) berjalan — hasilnya akan masuk ledger ini
- **Menunggu keputusan user:** Tier 3 (RBAC config, login concurrency, hidden-input markup), backup cluster (P1), test-side batch, infra ops, KB eval framework, sesi payroll jam 1 (true-up + golden test + compliance)
