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
| 42 | **Seeder demo konsolidasi PT DCMS** (M12) — company utama → `DKMS-2025` PT Daya Cipta Mandiri Solusi di 4 seeder (CompanyAndDivision, CompanySetting, CompanyEmployees, E2eTest, Branch, IntegrationSample); 4 seeder `PTDayaciptaMandiri*` duplikat dihapus; **terverifikasi `migrate:fresh --seed`**: 1 company PT DCMS, 56 employees, 1050 attendance, 31 KB + embeddings | ✅ |
| 43 | ~~UI overlap bottom-nav~~ **KOREKSI (2026-08-05)**: analisis awal salah — clearance 6.5rem SUDAH ada di `layouts/app.blade.php:92` (wrapper semua non-admin); fix `63c8350` membuat double clearance (~13.25rem) → **di-revert**. Akar "UI menimpa" yang sebenarnya: modal lock DEAD (feature-lock/addon-lock tidak pernah di-include → klik fitur terkunci silent-fail), payroll-manager modal inline z-50, dark-mode key mismatch (`isDark` vs `dark`), pwa-install-prompt overlap nav | ✅ di-revert |
| 44 | **Audit UI menyeluruh 2026-08-05** (4 lane: admin/user blades + components/layouts + Livewire PHP — detail di `docs/UI-AUDIT-2026-08-05.md`): **4 P1 wire-call mismatch** (DocumentTemplateManager 3 method hilang · SystemMaintenance saveBackupAutomationSettings · OperationalWorkspace createClient · MasterData/Admin `form.job_title_id` vs `position_id` — UI error "Method not found"); **3 modal lock mati** (feature-lock/addon-lock/high-priority-announcement tidak pernah di-include → silent-fail); 5+ modal inline tanpa teleport; dark-mode key mismatch; 15+ dead/stub; duplikasi komponen root vs namespaced. Terverifikasi OK: 0 double header, semua 83 render() view ada, `$watch('$wire.` bersih, clearance bottom-nav benar | ✅ **FIXED (2026-08-05)** — semua 4 wire-call diimplementasi (DocumentTemplateManager: editSelectedDocumentType/useTemplatePreset/setTemplateEditorMode + save flow benar; SystemMaintenance: saveBackupAutomationSettings + mount + backupScheduleSummary; OperationalWorkspace: tab clients + createClient + uniqueClientCode + createProject persist client_id; admin.blade: position_id); feature-lock-modal di-include di layouts/app; verified php -l + pint + baseline compare (10→9 failed, 1 test baru hijau, 0 regresi). Sisa 9 failure = pre-existing test-side (ledger) |
| 45 | **P0 compliance payroll — KEPUTUSAN FINAL (2026-08-05)**: (a) pesangon → **PP 35/2021 Pasal 40(2)** (<1=1 … ≥8=9, hapus tier 0) ✅ diadopsi; (b) **biaya jabatan → REVERTED** (keputusan M2 "biarkan dulu" — deviasi terdokumentasi, revisit later); (c) cap JP 9.559.600 → **11.086.300** (Maret 2026) ✅ diadopsi. **Golden test 27/27 PASS** — fixture di-update: CP-03/CP-04=48jt (PP35), PR-03/11/14/15 JP baru, PR-14 pph21 kembali 2.415.000 (tanpa biaya jabatan) | ✅ |
| 46 | **Absensi face-only ditegakkan (2026-08-05)** — keputusan Fikih align PRD §1/§4 ("face-only; PIN fallback tidak diizinkan"): hapus PIN fallback dari `AttendanceService` (resolveVerification/resolveClockOutVerification, verifyPin/logBypass/pinBypassReason, PIN streak cache), `ClockInAction` (showPinModal/pin/pinAction/wfaPin/wfaFaceMode/doClockInWithPin/doClockOutWithPin), blade PIN modal + WFA PIN fallback + `fallbackToPin()`, API ClockInRequest/ClockOutRequest rule `pin`. Face gagal/belum terdaftar/timeout = **ditolak** + pesan arahkan koreksi HR. Enum PIN_VERIFIED dipertahankan (data historis). Verified: `AttendanceServiceTest` 23 passed (63 assertions) + `ApprovalWorkflowTest` 10 passed; pint + php -l clean | ✅ |
| 47 | **UI/UX gelombang 2026-08-05 (keputusan Fikih)** — (a) **CommandCenter DIHAPUS TOTAL** (fitur tidak digunakan/redundan, commit `bc5d259` — detail di K5); (b) **dark mode sweep total** (commit `3822c60`): 3.243 token `dark:` + 152 aturan `.dark` + toggle `theme-toggle` + logic isDark/darkMode (guest-layout, layouts/app, errors) + `@custom-variant dark` dihapus — light-only sesuai `design.md:14`, CSS bundle 1.569→1.363 KiB, `npm run build` SUCCESS; (c) **4 paket UI konsisten** (commit `3c24514`): modal inline → pola x-overlays (teleport z-[90] x-trap role/aria; 4 modal direplikasi inline krn Alpine-only/?int property — entangle TypeError), root Jetstream → namespaced di teams/api/auth, dead/stub dihapus (FaceRegistration, pages/auth/login, partials/head, payroll-adjustment-manager, 2FA form orphan, addon-lock-modal, 3 @stack mati, title PasPapan→app.name), pwa-install-prompt bottom-nav overlap + admin top-nav safe-area. **Keputusan kecil:** 7 wrapper admin DI-PERTAHANKAN (controller masih render walau @deprecated); high-priority-announcement-modal tidak dimigrasi (dead); x-form-section/action-section tanpa padanan namespaced | ✅ |
| 48 | **Investigasi UI kalender/padding/box-sizing (2026-08-05)** — akar: (a) `flatpickr-overrides.css` masih **26× palet hijau PasPapan** (`#6ab45b`/`#57944a`/`#2f6f2b`) vs design system **biru primary `#024ad8`** → kalender tidak konsisten; (b) `app.css:4123` `box-sizing: content-box !important` di `body.is-native-scanning nav[data-app-top-nav]` → width 100% + padding overflow viewport; (c) padding input native-field duplikat dua sistem | ✅ **FIXED (2026-08-05)** — kalender flatpickr 26× hijau → primary blue (selected `#024ad8`, hover/today/arrow primary-600 tone, gradient header `rgb(2 74 216 / X)`); focus ring compact-filter ikut di-sweep; `content-box !important` → `border-box !important`; padding `.admin-ui` input `0 1rem` + textarea `0.75rem 1rem` (native-field pl-12 = clearance ikon fungsional, dipertahankan). Verified: grep hijau 0, content-box 0, npm run build SUCCESS |
| 49 | **BUG PRE-EXISTING — `x-forms.label` render label KOSONG (2026-08-05, ditemukan fix-37)** — komponen hanya render `$slot`; pemakaian `value="..."` (tanpa slot) menghasilkan label kosong + atribut `value` stray. Dipakai di: payroll-settings, hr-tasks-page, employee-document-request-page, dll → **label tidak terlihat (bug laten)**. Payroll-manager memakai slot form yang benar | ✅ **FIXED (2026-08-05)** — komponen `forms/label.blade.php` tambah prop `value` + fallback `$value !== '' ? $value : $slot` → **397 callers** `value="..."` langsung tampil tanpa ubah tiap file (24 callers slot tetap jalan, 0 kasus campuran). Verified: view:cache clean, pint pass |
| 50 | **Loop audit 2026-08-05 (M6/M7/M13/M14/M15/M18-M21/M25/M26/M28/M29/M31/M32/U8/U11/Q3/Q4/Q6)** — 18 item fixable dikerjakan: 14 FIXED penuh (M6, M7, M13-FK, M14, M15, M18, M19, M20, M21, M25, M28, M29, M31, U8, U11, Q3 = 16) + 3 SEBAGIAN (M26 3-job hapus/RunSystemBackup dipertahankan, M32 3-JS hapus/CapacitorDeviceManager dipertahankan, Q4 tests.yml DB fix) + 4 VERIFIED-FIXED tanpa kode (M8, M10, M12; M11 by-design terdokumentasi). Q6: backup tests 4/4 PASS + DocumentWorkflowUnlockedTest stale key di-fix. Semua file PHP: php -l + pint pass; view:cache SUCCESS; migrate FK sukses; batch test 38/38 PASS; full suite 659 pass/30 fail = 0 regresi lane ini (banding clean-tree stash). Q1: PHPStan tetap 1000+ (noise dynamic-proxy, tanpa bulk-fix aman). Detil per item di ledger M*/U*/Q* bawah | ✅ |

---

## 🔴 Kritis (belum di-fix)

| # | Temuan | Verifikasi | Sumber |
|---|--------|:----------:|--------|
| K1 | `ApprovalController::index` dengan `?all=true` — pending approval lintas org tanpa gate tambahan (hanya auth:sanctum) | ⚠️ | ✅ **FIXED** (abort_unless isSuperadmin/isAdmin di branch `all`) |
| K2 | Route `admin.reports.payrolls.export` pakai gate **`viewAdminPayroll` yang TIDAK ada** di enum → **403 untuk semua user** (export payroll report mati) | ✅ | ✅ **FIXED** (route gate → `view_payrolls` — konsisten dgn authorize controller) |
| K3 | **`feature.lock` middleware NO-OP** — `RedirectLockedEnterpriseFeature::handle()` = `return $next($request)`, dipakai 29× di routes, boundary enterprise tidak di-enforce | ✅ (2 sumber) | ✅ **FIXED** (implementasi: Setting `feature.<name>` truthy → redirect redirectRoute; default unlock = aman) |
| K4 | `EmployeePiiResource` expose NIK/NPWP/bank mentah (admin-only path, kandidat data leak) | ⚠️ | ✅ **FIXED** (masking phone/nik/npwp/bank — pola ProfileResource) |
| K5 | ~~`CommercialWorkspaceService::salesSummaryForCompanies()` hardcoded return 0~~ — kartu pipeline CommandCenter = data mati (silent degradation) | ⚠️ | ✅ **RESOLVED (2026-08-05)** — **CommandCenter DIHAPUS TOTAL** (keputusan Fikih, fitur tidak digunakan/redundan): route `admin.command-center`, nav menu, `CommandCenter.php`, `CommandCenterService.php`, `command-center.blade.php`, `VIEW_COMMAND_CENTER` enum + grouped, 3 baris seeder role, blok `command_center` di `config/rbac.php`, dan `CommercialWorkspaceService.php` (shell kosong setelah method-nya dihapus — 0 referensi) |
| K6 | `ReimbursementController::categories` tanpa gate sama sekali | ⚠️ | ✅ **FIXED** (authorize viewAny; catatan: method TIDAK punya route — unreachable, aman berlapis) |
| K7 | `navigation.php` — 22 dari ~24 route name tidak ada → link sidebar 404 (B20 konfirmasi) | ✅ | ✅ **FIXED** (fix-26: 22/22 route valid via route:list; item Pinjaman dihapus — fitur hanya API; 'Slip Gaji' → `my-payslips` — route `payroll` memang tidak ada) |

---

## 🟡 Medium (dikelompokkan per modul)

### Payroll & Pajak
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M1 | True-up PPh21: lembur dobel hitung (`gross_salary + overtime_pay` padahal gross sudah termasuk lembur) + reimburse ikut annual tapi tidak di basis TER bulanan | ✅ | ✅ **FIXED (2026-08-05)** — `getYtdGrossIncome()` kini SUM(gross_salary) − reimburse ter-link (payroll_id); verifikasi PayrollGoldenTest 27/27 + regression test M1 |
| M2 | `calculateAnnualPPh21Progressive` tanpa biaya jabatan (deviasi PMK 168/2023) | ✅ | ⚠️ **OPEN** — butuh keputusan Fikih (mengubah angka pajak semua karyawan) |
| M3 | Tabel pesangon = UU 13/2003 (bukan PP 35/2021: <1thn=0, 1-6, ≥6=6) + variant `dismissed_severe` 2× ≠ standar | ✅ | ⚠️ **OPEN** — variant multiplier kebijakan internal, butuh keputusan Fikih |
| M4 | Cap JP seeder 9.559.600 (regulasi Maret 2026 ≈ 11jt) | ✅ |
| M5 | `app.timezone` = UTC (HRIS Indonesia tanpa Asia/Jakarta) | ⚠️ fix-22 | ✅ **FIXED (2026-08-05)** — `config/app.php` → `Asia/Jakarta`; PayrollGoldenTest 27/27 PASS (freeze 2026-08-04 tetap konsisten) |
| M6 | `config/payroll.php` mati total (0 pembaca) | ⚠️ fix-22 | ✅ **FIXED (2026-08-05)** — 0 referensi `config('payroll…')` di app/routes/tests (hanya match palsu `admin.payroll.process` di rbac.php) → file dihapus |
| M7 | Mismatch embedding model: config default `gemini-embedding-001` vs `text-embedding-004` | ⚠️ fix-22 | ✅ **FIXED (2026-08-05)** — default di `config/ai.php` + `config/services.php` → `text-embedding-004` (selaras .env.example + 768D; EmbeddingService tetap baca `ai.providers.gemini.embedding_model` — tidak disentuh) |

### Schema & Data
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M8 | Dual tabel PTKP: `golongan_ptkp` vs `golongan_ptkps` (FK/model tidak nyambung) | ⚠️ fix-21 | ✅ **VERIFIED-FIXED (2026-08-05)** — sudah terkonsolidasi: model `GolonganPtkp` (tanpa `$table`) → `golongan_ptkps` (dengan `kategori_ter_id` FK), `Employee.golongan_ptkp_id` → `golongan_ptkps.id`; migration `2026_07_26_000002_cleanup_golongan_ptkp_duplicate` drop tabel lama `golongan_ptkp` (tanpa kategori_ter_id). 0 pemakaian tabel lama |
| M9 | Dual implementasi absensi: `CommunityService` vs `AttendanceService` — interface hanya di-binding ke yang lama | ⚠️ fix-15 | ✅ **BUKAN DUP (2026-08-05, diverifikasi)** — dua service peran BERBEDA: `CommunityService` = contract attachment/photo/face-enrollment (`AttendanceServiceInterface`, dipakai LeaveRequestService/FaceEnrollment/HomeAttendanceStatus); `AttendanceService` = core clock-in/out + geofence + face verification (dipakai ClockInAction). 0 method overlap. Interface utk skripsi/community contract — by design; tidak perlu konsolidasi |
| M10 | lat/long dobel di attendances (`lat_in/long_in` vs `latitude_in/longitude_in`) | ⚠️ fix-21 | ✅ **VERIFIED-FIXED (2026-08-05)** — bukan kolom dobel: DB hanya punya `lat_in/long_in/lat_out/long_out` (migration create_attendances); `latitude_in/longitude_in/latitude/longitude` adalah ACCESSOR (`Attribute::get(fn () => $this->lat_in)` dst) di `Attendance` — alias legacy by design, 1 sumber kebenaran |
| M11 | Dual scheduling (`Schedule` user_id vs `ShiftSchedule` employee_id) + dual leave quota (`LeaveBalance` vs `LeaveEntitlement`) | ⚠️ fix-8 | ⚠️ **BY-DESIGN (2026-08-05, diverifikasi)** — keduanya DIPAKAI aktif dengan peran berbeda: `schedules` (user_id, 2026-07-21) = jadwal user-facing + integrasi shift swap (`shift_swap_requests.schedule_id` FK, ClockInAction/ShiftSchedulePage/ShiftSwapRequestPage); `shift_schedules` (employee_id, 2026-05-08) = assignment roster admin (ShiftForm/ShiftSchedulePage/ScheduleRosterExport; ShiftForm hapus DI KEDUA tabel). Konsolidasi = refactor schema + fitur → butuh keputusan. Leave quota: `LeaveEntitlementService` DUAL-WRITE ke `LeaveBalance` (konsumsi LeaveService/PayrollCalculator/ResetLeaveQuota) + `LeaveEntitlement` (konsumsi LeaveEntitlementManager/LeaveRequestService) — sinkron by service; konsolidasi butuh keputusan |
| M12 | **Seeder `PTDayaciptaMandiri*` tidak ter-wire ke DatabaseSeeder** (6 seeder orphan — perusahaan utama tidak ter-seed otomatis) | ⚠️ fix-21 | ✅ **VERIFIED-FIXED (2026-08-05)** — 0 file/0 referensi `PTDayaciptaMandiri*` tersisa di repo (konsolidasi DKMS-2025 sesi 2026-08-04, entri fix-42); DatabaseSeeder ter-wire via CompanyAndDivision/CompanySetting/CompanyEmployees/E2eTest/Branch/IntegrationSample ke `DKMS-2025` |
| M13 | `projects.client_id` tanpa FK; `KnowledgeBase.embedding` array JSON vs `FaceDescriptor` pgvector | ⚠️ fix-8/21 | ✅ **SEBAGIAN FIXED (2026-08-05)** — FK ditambahkan (migration `2026_08_05_000001_add_client_fk_to_projects_table`: `client_id → clients` nullOnDelete; 0 orphan id terverifikasi di dev DB, migrate sukses). `KnowledgeBase.embedding` = **pgvector(768) di pgsql** (fallback `text` hanya non-pgsql; cast `array` utk JSON serialize) — konsisten dengan FaceDescriptor; BUKAN mismatch nyata; pipeline EmbeddingService tidak disentuh (lane lain) |
| M14 | `KnowledgeBaseFactory` morph salah ke Attendance; `ApprovalFactory` dangling morph | ⚠️ fix-21 | ✅ **FIXED (2026-08-05)** — KnowledgeBaseFactory: `knowledgeable_type/id` → `Company::factory()` (pemilik KB nyata, pola KnowledgeBaseSeeder/KnowledgeBaseService `$owner`); ApprovalFactory: default `approvable_type=Leave::class` + `Leave::factory()` (id tidak lagi random 1-1000), state `forOvertime()`/`forReimbursement()`; 0 konsumen test lama (verified) |
| M15 | `database/data/indonesian_hilodays.php` orphan + konflik tanggal Idul Fitri + typo "hilodays" | ⚠️ fix-21 | ✅ **FIXED (2026-08-05)** — 0 referensi di app/database/config/routes/tests (hanya codemap/graphify) → file dihapus; `database/data/` bersih (libur nasional via `laravolt/indonesia` + Holiday model) |

### RBAC & Authorization
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M16 | Policies TIDAK ter-register eksplisit (konvensi `Gate::guessPolicyName`) — catatan PROGRESS B10 salah | ✅ |
| M17 | `config/rbac.php` stub kosong → RoleAccessPreview/grouping UI kosong (Tier 3) | ✅ |
| M18 | `UserSessionManager` pakai legacy `allowsAdminPermission()` (bukan enum) | ⚠️ fix-9 | ✅ **FIXED (2026-08-05)** — boot guard → `can('manageUserSessions')` (gate camelCase dari `MANAGE_USER_SESSIONS`, di-seed ke role admin). Dot-key `admin.user_sessions.manage` TIDAK PERNAH match permission_keys enum → non-superadmin selalu 403 (bug nyata). Konsisten dgn gate route `manageUserSessions` |
| M19 | `HrChecklistManager` tanpa authorize di komponen (hanya route can) | ⚠️ fix-9 | ✅ **FIXED (2026-08-05)** — + `AuthorizesRequests` trait; `mount()` → `authorize('viewAny', HrChecklistCase::class)`; mutasi: `startCase` → `create`, `cancelCase` → `cancel`, `updateTask` → `update` (policy HrChecklistCase/Task). HrChecklistFlowTest 7/7 PASS |
| M20 | Route gate vs komponen gate asimetris di master-data (`manageDivisions` route vs `manageMasterData` komponen) | ⚠️ fix-9 | ✅ **FIXED (2026-08-05)** — komponen disamakan ke gate per-modul (pola route): Division→`manageDivisions`, JobTitle→`manageJobTitles`, Education→`manageEducations`, ShiftForm+ShiftComponent→`manageShifts` (ShiftComponent `manage_attendances` salah). `manage_master_data` TIDAK di-seed ke role admin → sebelumnya mutasi master-data 403 utk admin (bug nyata). 0 test referensi gate lama |
| M21 | `EmployeeDocumentRequestPolicy` pakai custom `employeeHasAccess` (inkonsisten 9 policy lain) | ⚠️ fix-18 | ✅ **FIXED (2026-08-05)** — `sameCompany()` → `MultiCompanyService::canAccessUser()` (pola ReimbursementPolicy dkk). Celah tertutup: manager/admin lintas-company tidak lagi otomatis akses (hanya superadmin + same-company); own-profile tetap via cek `user_id` per method. EmployeeDocumentRequestFlowTest/DocumentRequestSchemaTest 13/13 PASS |
| M22 | 8 komponen user tanpa authorize in-component (ClockInAction, FaceEnrollment, dll — risiko laten) | ⚠️ fix-10 | ✅ **FIXED (2026-08-05)** — 7 komponen di-guard: ClockInAction `authorize('create', Attendance)` di 6 method (entry + mutasi wire-callable), HomeAttendanceStatus `viewAny Attendance` + trait, FaceEnrollment/ShiftSchedulePage/MyOperationalTasks/CollaborationInbox/MyCustomForms `abort_unless(employee, 403)` (tidak ada policy pas — guard minimal + komentar). MyPerformance DILEWATI (sudah punya viewAny + selfAssess/acknowledge). Fixture 4 test di-update (user punya Employee, sesuai E2eTestSeeder). 2 failure CustomFormBuilderTest PRE-EXISTING (string notifikasi mismatch commit `9c55d86`) |
| M23 | Route tanpa gate: home, my-schedule, my-tasks, collaboration, my-forms, face.enrollment, notifications | ⚠️ fix-20 | ✅ **FIXED (2026-08-05, via keputusan)** — 0 gate baru (change-control: permission employee tidak tersedia di enum/seeder; `view_collaboration_workspace`/`view_custom_forms` ada di enum tapi TIDAK di-seed ke employee → `->can()` = 403 semua employee = blokir fungsionalitas). Ditutup dengan: komentar keputusan di route + verifikasi middleware chain `web → Authenticate:sanctum → EnsureEmailIsVerified → UserMiddleware` untuk 6 route (route:list). `home` sudah dalam grup `user` — tidak berubah |
| M24 | Duplikasi route `admin.announcements` (operations.php:24 + security.php:7 — salah satu menimpa) | ✅ |

### Infra & Reliabilitas
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| M25 | **8 middleware dead** (0 referensi): CheckMaintenanceMode, DeviceDetection, EnsureActiveAccount, LogUserActivity, SetLocale, SetUserLocale, ThrottleRequestsByIP, TrackRedisSessions + `User::canAuthenticate()` hardcoded true | ⚠️ fix-14 | ✅ **FIXED (2026-08-05)** — 8 file middleware dihapus (0 referensi di bootstrap/app.php (hanya 6 middleware di-register), routes, config, tests). `User::canAuthenticate()` → `return ! $this->trashed()` (users punya softDeletes; EloquentUserProvider sudah exclude trashed — guard manual kini konsisten; dipakai AuthenticateLoginAttempt/E2eLoginController) |
| M26 | **4 jobs dead** (tidak pernah di-dispatch): GeneratePayslipPdfJob, ProcessMonthlyAttendanceReportRun, ProcessEmployeeDocumentUpload, RunSystemBackup | ⚠️ fix-17 | ✅ **SEBAGIAN FIXED (2026-08-05)** — **RunSystemBackup DIPAKAI** (`SystemMaintenance` dispatch ×2 + 2 test) → dipertahankan (klaim "dead" salah). 3 job lain 0 dispatch/0 referensi (app+tests+routes) → dihapus |
| M27 | **Scheduler gap**: detect-alpha, detect-chronic-late, reset-leave-quota, cache:warm klaim schedule tapi tidak di routes/console.php | ⚠️ fix-17 | ✅ **FIXED (2026-08-05)** — 4 command didaftarkan: `attendance:detect-alpha` dailyAt 20:00, `attendance:detect-chronic-late` dailyAt 23:30, `cache:warm` hourly, `leave:reset-quota` yearlyOn 1 Jan (semua `withoutOverlapping`); verified `php artisan schedule:list` 10 jadwal tampil |
| M28 | `hrconnect.face_distance_threshold` dead (hardcode 85.0 di FaceRecognitionService) | ⚠️ fix-22 | ✅ **FIXED (2026-08-05)** — config kini DIPAKAI: `FaceRecognitionService::similarityThreshold()` = `(1 - config('hrconnect.face_distance_threshold')) * 100` (default 0.15 ≈ 85% — perilaku lama dipertahankan); default config 0.4 → 0.15 (selaras komentar + .env.example); `.env` dev 0.4 → 0.15. `SIMILARITY_THRESHOLD` const dipertahankan (FaceController API masih pakai — file tsb di luar scope lane ini). AttendanceServiceTest 23 PASS |
| M29 | `laravolt` cache store default redis ≠ CACHE_STORE=database | ⚠️ fix-22 | ✅ **FIXED (2026-08-05)** — `config/laravolt/indonesia.php` `store` default → `env('INDONESIA_CACHE_STORE', 'database')` |
| M30 | Backup cluster (8 app-bug, P1): job pg_dump tanpa kredensial, SystemBackupRun tanpa security hooks, SystemMaintenance UI rusak (downloadExistingBackup/restore/queueBackup) | ✅ (triage) | ✅ **SEBAGIAN FIXED (2026-08-05)** — signature HMAC kini ditulis saat backup (`signDatabaseBackup`) sehingga restore berfungsi; `auditCompleted` + audit failed di semua jalur (incl. job failure + restore gagal — tak lagi stuck `running`); regression test roundtrip signature. Sisa: drill command `maintenance:backup-restore-drill` belum diimplementasi (test di-skip, Q6) |
| M31 | 6 FormRequest tanpa konsumen (ClockInRequest, ClockOutRequest, ListAttendanceRequest, ForgotPasswordRequest, LoginRequest, TwoFactorChallengeRequest) | ⚠️ fix-14 | ✅ **FIXED (2026-08-05)** — 0 konsumen di app/routes/tests (ClockIn/ClockOut mati sejak fix-46 face-only; Login/2FA pakai Fortify bawaan) → 6 file dihapus |
| M32 | 4 JS dead: pwa-install.js, location.service.js, mock-location.js, CapacitorDeviceManager.js | ⚠️ fix-25 | ✅ **SEBAGIAN FIXED (2026-08-05)** — **CapacitorDeviceManager DIPAKAI** (bootstrap.js: `window.deviceManager = new CapacitorDeviceManager()`) → dipertahankan. pwa-install.js (0 import; blade pwa-install-prompt pakai inline JS), location.service.js (0 import), mock-location.js (0 import) → dihapus. vite.config.js tidak referensikan file tsb |

### UI & Blade (9/12 diverifikasi langsung — SEMUA VALID)
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| U1 | `initUiPickers` dipanggil (modal) tapi tidak didefinisikan — silent no-op | ✅ | ✅ **FIXED** (fix-26: implementasi di app.js — re-init flatpickr/tom-select dalam modal) |
| U2 | `payroll-manager.blade.php` di luar design system (tanpa page-shell) | ✅ | ✅ **FIXED (2026-08-05)** — masuk design system + palet baru: `x-admin.page-shell` + `page-tools`, stat cards aksen `--color-module-payroll` (emerald, strip + ikon + alpha 10%), thead tint module-payroll/5, status → `x-admin.status-badge`, action buttons → `x-actions.button` soft-* (primary/success/danger/secondary). Modal tolak tetap. Wire:call identik |
| U3 | Login pakai token legacy (`bg-canvas`/`text-ink`) | ✅ | ✅ **FIXED (2026-08-05)** — colorful: hero gradient `from-primary-700 via-primary-600 to-primary-500` + glow violet module-leave (Tier-A enrichment, marketing page boleh); panel kanan glow module-hr/leave + accent strip gradient primary→module-leave; token legacy (`bg-canvas`/`text-ink`/`bg-surface`/`border-outline`) → slate valid; `guest-layout` `bg-canvas`→`bg-surface` (token canvas tidak ada di @theme — selama ini body auth putih default). Inputs pola register, CTA primary blue |
| U4 | `face-registration` = stub "coming soon" tidak di-link | ✅ | ✅ **FIXED (fix-37)** — `face-registration.blade.php` + `FaceRegistration.php` (orphan shim) dihapus; route redirect `/face-registration`→`/face-enrollment` dipertahankan; `config/navigation.php` → `face.enrollment` |
| U5 | `approvals/index` placeholder tidak ter-render (route → Livewire TeamApprovals) | ✅ | ✅ **FIXED** (fix-26: blade dihapus) |
| U6 | 11 blade admin orphan (dashboard, import-export wrappers, monthly_pdf, attendances/show kosong, master-data wrappers) | ✅ (sebagian) | ✅ **FIXED** (fix-26: 11 dihapus, 2 dipertahankan — attendances/index dirujuk controller ter-route; employees/index dirujuk deprecated) |
| U7 | `tomSelectInput` mismatch (blade kirim > 4 arg, helper 4 param — silent) | ✅ | ✅ **FIXED** (fix-26: call sites dirapikan ke 4 arg) |
| U8 | Dual component stacks (Jetstream root vs namespaced overlays/actions/forms) + branding duplikat | ⚠️ fix-25 | ✅ **FIXED (2026-08-05)** — sisa 2 komponen root dimigrasi: `components/forms/form-section.blade.php` + `components/actions/action-section.blade.php` dibuat (salinan root), 7 call site (`api/api-token-manager`, `teams/*` 4, `profile/notification-preferences-form`, `profile/activity-log-viewer`) → `<x-forms.form-section>`/`<x-actions.action-section>`; root `form-section/action-section.blade.php` dihapus; 0 pemakaian root tersisa; `view:cache` SUCCESS |
| U9 | 3 implementasi `<head>`; `minimal.blade.php` layout kedua tidak dipakai | ⚠️ fix-25 | ✅ **SEBAGIAN FIXED (2026-08-05, diverifikasi)** — `minimal.blade.php` SUDAH DIHAPUS (layouts/ hanya app.blade.php). Sisa 2 head: `layouts/app.blade.php` (broadcast config + fonts + PWA) vs `components/guest-layout.blade.php` (SW reset native-app + viewport-fit) — perbedaan FUNGSIONAL untuk layout berbeda (guest vs authenticated); konsolidasi penuh = keputusan desain, dipertahankan by-design |
| U10 | 8 halaman user raw-Tailwind vs BEM; 5 widget home tanpa surface; `div` bukan `<section>` di team-approvals | ⚠️ fix-24 | ⚠️ **SEBAGIAN (2026-08-05, diverifikasi)** — team-approvals SUDAH BEM + `<section>` (user-page-shell, `team-approval-overview` dsb); widget home hybrid (`user-content-panel` + inner utility) — sisa restyle halaman user = pekerjaan DESIGN (styling/visual hierarchy) → butuh @designer, di luar lane fix |
| U11 | `PayrollSubmitted` mail hardcode "Total karyawan: 1"; `ReimbursementRequestedMail` URL ke API bukan web | ⚠️ fix-17 | ✅ **FIXED (2026-08-05)** — `Notifications/PayrollSubmitted::toMail` → `Payroll::where(period)->where(status=SUBMITTED)->count()` (1 baris per karyawan, unique employee_id+period); `ReimbursementRequestedMail` action URL → `route('admin.reimbursements')` (web) |

### QA & Proses
| # | Temuan | Verifikasi |
|---|--------|:----------:|
| Q1 | **PHPStan 1000+ errors** (dengan baseline sekalipun) | ✅ (run) | ⚠️ **SEBAGIAN (2026-08-05)** — re-run: masih **1000+ errors**; dominasi noise static-analysis (533 undefined method / 206 undefined property — mayoritas proxy dinamis `$user->employee->xxx`, relasi magic, mixin IdeHelper hilang di fresh checkout). Tidak ada bulk-fix aman tanpa refactor besar (butuh keputusan: turunkan level / tambah docblock massal). File yang diubah lane ini: 0 kategori error BARU (49 error di file tersentuh = pola pre-existing; PHPStan tidak punya baseline utk file baru tsb) |
| Q2 | **±85 test masih gagal** (22 app-bug + 38 test-side + Tier 3 tersisa) | ✅ (triage) | ⚠️ **SEBAGIAN (2026-08-05)** — full suite saat ini 30 fail/659 pass; semua 30 = pre-existing/lane lain (tidak ada regresi dari lane ini; lihat Q6 + catatan loop) |
| Q3 | E2E tidak reproducible (`auth.setup.ts` hilang, states gitignored) | ✅ | ✅ **FIXED (2026-08-05)** — `tests/e2e/auth.setup.ts` dibuat: generate storageState 5 role (employee/hr/manager/finance/admin.json) dari kredensial E2eTestSeeder via `GET /__e2e-login` (token config `services.e2e.login_token`), match project `setup` di playwright.config.js (testMatch `auth.setup.ts`); states tetap gitignored tapi kini regenerable |
| Q4 | CI tidak ada (tests/ + .github/ untracked, DB mismatch) | ✅ | ✅ **SEBAGIAN FIXED (2026-08-05)** — `.github/workflows/` sudah ada lokal (lint.yml + tests.yml); `tests.yml` diperbaiki: job sqlite (tak mungkin jalan — phpunit.xml hardcode pgsql) dihapus; job postgres DB service `hrconnect_testing` → **`hris_testing`** (selaras phpunit.xml/phpunit.pgsql.xml — mismatch laten Q4/AGENTS.md hilang). `.github/` tetap local-only (belum di-commit — keputusan orchestrator) |
| Q5 | KB eval dataset ≥20 Q&A / ≥90% relevan — **belum ada di mana pun** (hard gate) | ✅ (grep) | ✅ **FIXED kerangka + gate dijalankan (2026-08-05)** — `tests/Fixtures/kb-eval-dataset.json` **24 case** (22 positif + 2 negatif, 10 kategori, keyword diselaraskan ke corpus NYATA terverifikasi programmatic); `app/Support/KnowledgeBaseEval.php` (metrik coverage ≥80% + citation + refusal); `tests/Feature/KbEvalDatasetTest.php` **3/3 PASS (409 assertions, 2× run stabil)** — offline fallback pg_trgm kualitas **100% (22/22)**, negatif 2/2 ditolak; online skip tanpa API key (assert struktur + laporan skor informatif); `app/Console/Commands/KbEvalCommand.php` (`kb:eval`). **Gate production dijalankan 2026-08-05**: seed dev 31/31 entries + embeddings asli (GEMINI_API_KEY ada di .env), `php artisan kb:eval` exit 0 — **22/22 positif lulus (100% coverage+citation), 2/2 negatif ditolak, kualitas ≥90% LULUS**. CATATAN: model hasil eval = `pg_trgm` (fallback, quota Gemini limit di env) — bukti semantic via Gemini perlu retry saat quota tersedia; bug test ditemukan: Pest `toContain()` tidak punya param message. **SISA GATE:** `verify_human: false` — wajib review HR final sebelum dijadikan bukti gate produksi |
| Q6 | Test-side: `auth_middleware_stack` tidak ada (B9 stale); `SystemMaintenanceBackupJobsTest`/`SystemBackupRunJobTest` stale | ✅ | ✅ **SEBAGIAN FIXED (2026-08-05)** — `auth_middleware_stack` 0 referensi (sudah dibersihkan); `SystemMaintenanceBackupJobsTest` + `SystemBackupRunJobTest` **4/4 PASS** (tidak stale). Bonus test-side: `DocumentWorkflowUnlockedTest` stale key `admin.document_requests.templates` (tidak resolve ke gate `viewAdminDocumentRequests` — keputusan K3) → `admin.document_requests.view`, PASS. Pre-existing lain (bukan item): `Livewire/EmployeeDocumentRequestPageTest` [close action] test ekspektasi `documentType=null` padahal `close()` hanya tutup modal — gagal juga di clean tree (terverifikasi stash) |

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
