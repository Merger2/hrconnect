# Task Tracker — HRConnect Backend + Scramble

> **Source of truth** untuk progress backend fixes & API documentation.
> Last updated: 2026-06-06 — **ALL PHASES COMPLETE**

---

## Phase A: Backend Gaps ✅

| # | Task | File | Status |
|---|------|------|--------|
| A1 | Fix `'time'` cast di Shift.php | `app/Models/Shift.php` | ✅ Remove `'time'` casts (L13 no `time` type) |
| A2 | Rename CompanySeeder + seed department/position | `database/seeders/CompanyAndDepartmentSeeder.php` | ✅ 4 departments, 6 positions |
| A3 | Bersihkan EmployeeSeeder | `database/seeders/EmployeeSeeder.php` | ✅ Replace raw DB::table with model factories |
| A4 | Tambah Cuti Menstruasi | `database/seeders/LeaveTypeSeeder.php` | ✅ 7 leave types total |
| A5 | Align ShiftSeeder names ke PRD | `database/seeders/ShiftSeeder.php` | ✅ 4 shifts: Office Hour, Flexible, Morning, Night 14-22 |

## Phase B: Scramble API Docs ✅

| # | Task | File | Status |
|---|------|------|--------|
| B1 | Configure scramble.php | `config/scramble.php` | ✅ Info, security_strategy (bearer), UI title |
| B2 | AuthController | `app/Http/Controllers/Api/AuthController.php` | ✅ Tag: Auth, 6 endpoints |
| B3 | AttendanceController | `app/Http/Controllers/Api/AttendanceController.php` | ✅ Tag: Attendance, 5 endpoints |
| B4 | LeaveController | `app/Http/Controllers/Api/LeaveController.php` | ✅ Tag: Leave, 5 endpoints |
| B5 | OvertimeController | `app/Http/Controllers/Api/OvertimeController.php` | ✅ Tag: Overtime, 4 endpoints |
| B6 | ReimbursementController | `app/Http/Controllers/Api/ReimbursementController.php` | ✅ Tag: Reimbursement, 4 endpoints |
| B7 | ApprovalController | `app/Http/Controllers/Api/ApprovalController.php` | ✅ Tag: Approvals, 3 endpoints |
| B8 | PayrollController | `app/Http/Controllers/Api/PayrollController.php` | ✅ Tag: Payroll, 7 endpoints |
| B9 | EmployeeController | `app/Http/Controllers/Api/EmployeeController.php` | ✅ Tag: Employees, 5 endpoints |
| B10 | ProfileController | `app/Http/Controllers/Api/ProfileController.php` | ✅ Tag: Profile, 3 endpoints |
| B11 | FaceController | `app/Http/Controllers/Api/FaceController.php` | ✅ Tag: Face Recognition, 2 endpoints |
| B12 | KnowledgeBaseController | `app/Http/Controllers/Api/KnowledgeBaseController.php` | ✅ Tag: Knowledge Base, 3 endpoints |
| B13 | HealthController | `app/Http/Controllers/Api/HealthController.php` | ✅ Tag: Health, 1 endpoint |
| B14 | Form Requests | `app/Http/Requests/Api/*.php` | ✅ ClockInRequest, StoreOvertimeRequest |
| B15 | Generate docs | `php artisan scramble:export` | ✅ 48 paths, 12 tags, bearer auth |

## Phase C: Config Files ✅

| # | Task | Status |
|---|------|--------|
| C1 | Create `config/hrconnect.php` | ✅ Face threshold, RAG mock mode, PTKP, etc. |
| C2 | Publish `config/ciphersweet.php` | ✅ Published via package vendor:publish |

## Phase D: Verification ✅

| # | Task | Status |
|---|------|--------|
| D1 | `php artisan migrate:fresh --seed` | ✅ All 9 seeders pass |
| D2 | `php artisan scramble:export` | ✅ `api.json` generated |
| D3 | `vendor/bin/pint --format agent` | ✅ Formatting clean |
| D4 | `php artisan test --compact` | ✅ 213 passed (2792 assertions) |

## Phase E: Termination Financials ✅

| # | Task | File | Status |
|---|------|------|--------|
| E1 | Add `phk_variant` column migration | `database/migrations/2026_06_06_005048_add_phk_variant_to_employees_table.php` | ✅ nullable string(20): dismissed, dismissed_severe, mutual |
| E2 | TerminationType helper methods | `app/Enums/TerminationType.php` | ✅ requiresPesangon(), requiresPenghargaan(), requiresLeaveCashOut(), requiresUangKompensasi() |
| E3 | calculatePesangon() | `app/Services/PayrollCalculatorService.php` | ✅ PRD Appendix C + §26.3 variant multiplier |
| E4 | calculateLeaveCashOut() | `app/Services/PayrollCalculatorService.php` | ✅ Sisa kuota × daily_rate |
| E5 | calculateUangKompensasi() | `app/Services/PayrollCalculatorService.php` | ✅ PKWT: (masa_kerja_bulan / 12) × monthly_salary |
| E6 | calculateUangPenghargaanMasaKerja() | `app/Services/PayrollCalculatorService.php` | ✅ Tabel multiplier §21.1 (3-6 thn=2, dst) |
| E7 | Integrasi di EmployeeTerminationService | `app/Services/EmployeeTerminationService.php` | ✅ Hitung financials saat terminate, simpan di aktivitas + response |
| E8 | API response financial_summary | `app/Http/Controllers/Api/EmployeeTerminationController.php` | ✅ Return breakdown financials di response JSON |
| E9 | Unit tests | `tests/Unit/TerminationCalculationTest.php` | ✅ 19 test cases (pesangon, penghargaan, kompensasi, enum helpers) |

---

## Progress Summary

| Phase | Total | Done | Pending |
|-------|-------|------|---------|
| A | 5 | 5 | 0 |
| B | 15 | 15 | 0 |
| C | 2 | 2 | 0 |
| D | 4 | 4 | 0 |
| E | 9 | 9 | 0 |
| **Total** | **35** | **35** | **0** |

## Generated Docs

- OpenAPI spec: `/home/merger/hrconnect/api.json`
- UI: `GET /docs/api` (via Scramble route)
- Endpoints: 48 paths across 12 tag groups
- Auth: Bearer token (Sanctum)
- Auto-detected schemas: `ClockInRequest`, `ApprovalLevel`, `DayType`, `VerificationMethod`
