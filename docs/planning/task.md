# Task Tracker — HRConnect Backend

> **Source of truth** untuk progress backend.
> Last updated: 2026-06-06

## Remaining Work

| # | Task | Est. |
|---|------|------|
| 1 | **Swap PDF engine** — `composer remove spatie/laravel-pdf && composer require barryvdh/laravel-dompdf` | ~1h |
| 2 | **Blade view** — `resources/views/pdf/payslip.blade.php` (HTML tabel + inline CSS, no Tailwind) | ~1h |
| 3 | **Archival pattern** — `PayslipPdfService::generateAndSave()`, simpan ke `storage/app/payslips/`, update `pdf_path` | ~1h |
| 4 | **Queue integration** — panggil `generateAndSave()` di `GenerateEmployeePayrollJob` saat `published` | ~0.5h |
| 5 | **Download endpoint** — otorisasi (owner/finance/super-admin), `response()->download()` dari local disk | ~1h |
| 6 | **Controller HTTP tests** — EmployeeController CRUD, ApprovalController flow, ReimbursementController, EmployeeTerminationController | ~3h |
| 7 | **PHPStan level upgrade** — level 2→5+, ~200 baseline suppressions | ~5h |
| 8 | **Face Recognition frontend** — Livewire ClockIn component + face-api.js + camera UI (after backend) | TBD |

## Done

All Phase A–E (35 tasks) completed. See git history for details.
