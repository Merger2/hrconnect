# Task Tracker — HRConnect Backend

> **Source of truth** untuk progress backend.
> Last updated: 2026-06-06

## Remaining Work

| # | Task | Est. | Status |
|---|------|------|--------|
| 1 | **Swap PDF engine** — `composer remove spatie/laravel-pdf && composer require barryvdh/laravel-dompdf` | ~1h | ✅ |
| 2 | **Blade view** — `resources/views/payroll/payslip.blade.php` (HTML tabel + inline CSS, no Tailwind) | ~1h | ✅ |
| 3 | **Archival pattern** — `PayslipPdfService::generateAndSave()` + `getPayslipPath()`, update `pdf_path` di DB, cek cache sebelum regenerate | ~1h | ✅ |
| 4 | **Queue integration** — `GeneratePayslipPdfJob` + `PayrollObserver` trigger saat status→published | ~0.5h | ✅ |
| 5 | **Download endpoint** — otorisasi (policy), cek cache dulu, `response()->download()` dari local disk | ~1h | ✅ |
| 6 | **Controller HTTP tests** — EmployeeController CRUD, ApprovalController flow, ReimbursementController, EmployeeTerminationController | ~3h | ✅ |
| 7 | **PHPStan level upgrade** — level 2→5, fix 7 class.notFound (Carbon/Request imports), hapus 6 unmatched baseline patterns, baseline 341 suppressed | ~5h | ✅ |
| 8 | **Face Recognition frontend** — Livewire ClockIn component + face-api.js + camera UI (after backend) | TBD | ⏳ |
| 14 | **Security hardening** — CORS `*`→`FRONTEND_URL`, session encryption enable, hapus `AlreadyClockedOutException` dead class + dead catch `AttendanceService.php:99`, `withExceptions()` render 404/429/403 ke JSON konsisten | ~40m | ⏳ |
| 15 | **Notification tests** — 8 notifikasi: `toMail()` + `toArray()` assertion (subject, konten, array keys) | ~2h | ⏳ |
| 16 | **Observer tests** — PayrollObserver (status→PUBLISHED→dispatch), TaxConfigObserver + BpjsConfigObserver (cache flush), HolidayObserver (cache + cross-year) | ~1.5h | ⏳ |
| 17 | **AttendanceController tests** — clockIn, clockOut, today, index, approveWfa (face + GPS + WFA approval flow) | ~2h | ⏳ |
| 18 | **LeaveController + OvertimeController tests** — store, index, show, destroy, quota | ~1.5h | ⏳ |
| 19 | **AuthController 2FA + FaceController + ProfileController tests** | ~1h | ⏳ |
| 20 | **Service + Job tests** — PayslipPdfService (generateAndStore, getPayslipPath), GeneratePayslipPdfJob, EmbeddingService (processKnowledgeBase, searchSimilar, searchByKeyword), ProcessKnowledgeBaseEmbedding job | ~2h | ⏳ |
| 9 | **Regenerate api.json** — `php artisan scramble:export` (fix 2 missing termination routes) | ~1m | ✅ |
| 10 | **Add flow context** — update `#[Endpoint]` description di semua controller: Clock-in, Cuti, Overtime, Payroll, Termination, Knowledge Base | ~30m | ✅ |
| 11 | **Study cases doc** — `docs/api/study-cases.md`: scenario real dari awal sampai akhir tiap flow | ~1h | ✅ |
| 12 | **CipherSweet AGENTS.md** — perbaiki query rule, tambah env/commands/blind index storage | ~15m | ✅ |
| 13 | **Fix form request vs DB schema** — StoreEmployeeRequest (5 field nullable→required + Rule::enum), UpdateEmployeeRequest (phone hapus nullable, status Rule::enum), StoreReimbursementRequest (hapus title ghost column, amount numeric) | ~30m | ✅ |

## Done

All Phase A–E (35 tasks) completed. See git history for details.
