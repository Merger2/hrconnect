<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExportMonthlyRequest;
use App\Http\Requests\Api\ExportPeriodRequest;
use App\Http\Requests\Api\GeneratePayrollRequest;
use App\Http\Requests\Api\ListPayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Jobs\GenerateEmployeePayrollJob;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\Payroll\PayrollExportService;
use App\Services\Payroll\PayslipPdfService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('Payroll')]
class PayrollController extends Controller
{
    #[Endpoint(title: 'List Payrolls', description: 'Paginated payroll list with year filter. Flow: Payroll (Step 2/4) — Generate → List → Download → Export.')]
    #[QueryParameter(name: 'year', description: 'Filter by year (YYYY)', type: 'integer')]
    #[QueryParameter(name: 'employee_id', description: 'Filter by employee (Finance only)', type: 'integer')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function index(ListPayrollRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Payroll::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        // A-6: Eager load employee to prevent N+1
        $query = Payroll::with('employee:id,employee_number,full_name')
            ->orderBy('period', 'desc');

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

    #[Endpoint(title: 'Get Payroll', description: 'Get payroll detail with all salary components. Flow: Payroll (detail).')]
    public function show(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('view', $payroll);

        return response()->json([
            'status' => 'success',
            'data' => PayrollResource::make($payroll->load('employee', 'items'))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Download Payslip', description: 'Download payslip PDF for approved/paid payroll. Flow: Payroll (Step 3/4) — Generate → List → Download → Export.')]
    public function payslip(Request $request, Payroll $payroll): BinaryFileResponse
    {
        // P1 fix 2026-08-11: policy 'download' (kepemilikan + status) —
        // menggantikan gate 'downloadPayslip' yang permission-only dan
        // membiarkan employee mengambil payslip karyawan lain via ID (IDOR).
        $this->authorize('download', $payroll);

        $service = app(PayslipPdfService::class);
        $cachedPath = $service->getPayslipPath($payroll);
        $pdfPath = $cachedPath ?? $service->generateAndStore($payroll);

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $payroll->period,
            $payroll->employee?->employee_number ?? 'unknown'
        );

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    #[Endpoint(title: 'Export Monthly', description: 'Export monthly payroll recap as Excel. Flow: Payroll (Step 4/4) — Generate → List → Download → Export.')]
    #[BodyParameter(name: 'period', description: 'Payroll period (YYYY-MM)', required: true, type: 'string')]
    #[BodyParameter(name: 'branch_id', description: 'Filter by branch', required: false, type: 'integer')]
    public function exportMonthly(ExportMonthlyRequest $request): BinaryFileResponse
    {
        $this->authorize('create', Payroll::class);

        $path = app(PayrollExportService::class)->exportMonthly(
            $request->validated('period'),
            $request->validated('branch_id'),
        );

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    #[Endpoint(title: 'Export 1721-A1', description: 'Export PPh 21 tax certificate as Excel (DJP format). Flow: Payroll (tax export).')]
    #[BodyParameter(name: 'period', description: 'Tax period (YYYY-MM)', required: true, type: 'string')]
    public function export1721A1(ExportPeriodRequest $request): BinaryFileResponse
    {
        $this->authorize('create', Payroll::class);

        $path = app(PayrollExportService::class)->export1721A1($request->validated('period'));

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    #[Endpoint(title: 'Export BPJS', description: 'Export BPJS health and employment insurance report as Excel. Flow: Payroll (BPJS export).')]
    #[BodyParameter(name: 'period', description: 'BPJS period (YYYY-MM)', required: true, type: 'string')]
    public function exportBpjs(ExportPeriodRequest $request): BinaryFileResponse
    {
        $this->authorize('create', Payroll::class);

        $path = app(PayrollExportService::class)->exportBpjsReport($request->validated('period'));

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    #[Endpoint(title: 'Generate Payroll', description: 'Queue payroll generation jobs for active employees. Flow: Payroll (Step 1/4) — Generate → List → Download → Export.')]
    #[BodyParameter(name: 'period', description: 'Payroll period (YYYY-MM)', required: true, type: 'string')]
    #[BodyParameter(name: 'employee_ids', description: 'Specific employees to process (null = all active)', required: false, type: 'array')]
    public function generate(GeneratePayrollRequest $request): JsonResponse
    {
        $this->authorize('create', Payroll::class);

        $data = $request->validated();

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
            GenerateEmployeePayrollJob::dispatch($employee, $data['period']);
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
