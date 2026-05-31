<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateEmployeePayrollJob;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollExportService;
use App\Services\PayslipPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PayrollController — list/show payroll + download payslip + generate batch.
 *
 * Authorization via PayrollPolicy:
 * - viewAny / view: Finance / Super Admin all, Employee self only
 * - generate: permission process_payroll
 * - downloadPayslip: status PUBLISHED/PAID, owner atau Finance
 */
class PayrollController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'employee_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->authorize('viewAny', Payroll::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = Payroll::query()->orderBy('period', 'desc');

        // Employee → diri sendiri saja
        if (! $user->hasRole(['super-admin', 'finance']) && $user->employee) {
            $query->where('employee_id', $user->employee->id);
        }

        if ($request->filled('year')) {
            $query->where('period', 'like', $request->input('year').'-%');
        }

        if ($request->filled('employee_id') && $user->hasRole(['super-admin', 'finance'])) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Payroll $p) => [
                'id' => $p->id,
                'employee_id' => $p->employee_id,
                'period' => $p->period,
                'status' => $p->status?->value,
                'gross_salary' => (int) $p->gross_salary,
                'total_deduction' => (int) $p->total_deduction,
                'net_salary' => (int) $p->net_salary,
                'created_at' => $p->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('view', $payroll);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $payroll->id,
                'employee_id' => $payroll->employee_id,
                'period' => $payroll->period,
                'status' => $payroll->status?->value,
                'basic_salary' => (int) $payroll->basic_salary,
                'total_allowance' => (int) $payroll->total_allowance,
                'gross_salary' => (int) $payroll->gross_salary,
                'overtime_pay' => (int) $payroll->overtime_pay,
                'pph21' => (int) $payroll->pph21,
                'bpjs_health' => (int) $payroll->bpjs_health,
                'bpjs_employment' => (int) $payroll->bpjs_employment,
                'loan_deduction' => (int) $payroll->loan_deduction,
                'attendance_penalty' => (int) $payroll->attendance_penalty,
                'total_deduction' => (int) $payroll->total_deduction,
                'net_salary' => (int) $payroll->net_salary,
                'created_at' => $payroll->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function payslip(Request $request, Payroll $payroll): BinaryFileResponse
    {
        $this->authorize('downloadPayslip', $payroll);

        // Defense-in-depth: status check explicit
        if (! in_array($payroll->status, [PayrollStatus::PUBLISHED, PayrollStatus::PAID], true)) {
            throw new BusinessRuleException(
                'Payslip hanya tersedia untuk payroll yang sudah dipublikasi.'
            );
        }

        $pdfPath = app(PayslipPdfService::class)->generateAndStore($payroll);

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $payroll->period,
            $payroll->employee?->employee_number ?? 'unknown'
        );

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * POST /payroll/export/monthly — Excel rekap payroll per periode.
     * Permission: process_payroll (Finance + Super Admin).
     */
    public function exportMonthly(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Payroll::class);

        $data = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $path = app(PayrollExportService::class)->exportMonthly(
            $data['period'],
            $data['branch_id'] ?? null,
        );

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * POST /payroll/export/1721-a1 — Bukti potong PPh 21 bulanan (DJP).
     */
    public function export1721A1(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Payroll::class);

        $data = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $path = app(PayrollExportService::class)->export1721A1($data['period']);

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * POST /payroll/export/bpjs — Laporan iuran BPJS Kesehatan + Ketenagakerjaan.
     */
    public function exportBpjs(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Payroll::class);

        $data = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $path = app(PayrollExportService::class)->exportBpjsReport($data['period']);

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $employeeIds = $data['employee_ids'] ?? null;

        $query = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->whereNull('resign_date');

        if ($employeeIds !== null) {
            $query->whereIn('id', $employeeIds);
        }

        $employees = $query->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada karyawan aktif untuk diproses.',
            ], 422);
        }

        $dispatched = 0;
        foreach ($employees as $employee) {
            GenerateEmployeePayrollJob::dispatch($employee, $data['period'])
                ->onQueue('payroll_high');
            $dispatched++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Job generate payroll telah dijalankan untuk {$dispatched} karyawan",
            'data' => [
                'queued_jobs' => $dispatched,
                'queue' => 'payroll_high',
                'period' => $data['period'],
            ],
        ], 202);
    }
}
