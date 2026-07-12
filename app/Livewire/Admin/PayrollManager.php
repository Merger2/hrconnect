<?php

namespace App\Livewire\Admin;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculatorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PayrollManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public int $month;

    #[Url]
    public int $year;

    #[Url]
    public string $statusFilter = 'all';

    public array $selectedPayrolls = [];

    public bool $selectAll = false;

    public bool $showGenerateModal = false;

    public bool $showDetailModal = false;

    public ?array $detailPayroll = null;

    public function mount(): void
    {
        if (! isset($this->month)) {
            $this->month = (int) now()->month;
        }
        if (! isset($this->year)) {
            $this->year = (int) now()->year;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
        $this->selectedPayrolls = [];
        $this->selectAll = false;
    }

    public function updatingYear(): void
    {
        $this->resetPage();
        $this->selectedPayrolls = [];
        $this->selectAll = false;
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedPayrolls = [];
        $this->selectAll = false;
    }

    #[Computed]
    public function canManage(): bool
    {
        $user = auth()->user();

        return $user?->can('view_payrolls') || $user?->can('view_payslip');
    }

    #[Computed]
    public function canFinanceAction(): bool
    {
        $user = auth()->user();

        return $user?->can('process_payroll') || $user?->can('manage_tax');
    }

    #[Computed]
    public function payrolls()
    {
        $period = sprintf('%d-%02d', $this->year, $this->month);

        $query = Payroll::query()
            ->where('period', $period)
            ->with(['employee.position', 'employee.department', 'employee.branch'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $q->whereHas('employee', fn ($sq) => $sq->where('full_name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('employee_number', 'ilike', '%'.$this->search.'%')
                );
            });

        return $query->orderBy('id', 'desc')->paginate(15);
    }

    #[Computed]
    public function summaryCards(): array
    {
        $period = sprintf('%d-%02d', $this->year, $this->month);

        $cacheKey = "payroll:summary:{$period}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($period) {
            $all = Payroll::where('period', $period)->get();

            return [
                'total_gross' => $all->sum(fn ($p) => (float) ($p->gross_salary ?? 0)),
                'total_net' => $all->sum(fn ($p) => (float) ($p->net_salary ?? 0)),
                'total_deduction' => $all->sum(fn ($p) => (float) ($p->total_deduction ?? 0)),
                'draft_count' => $all->where('status', PayrollStatus::DRAFT)->count(),
                'published_count' => $all->where('status', PayrollStatus::PUBLISHED)->count(),
                'paid_count' => $all->where('status', PayrollStatus::PAID)->count(),
                'employee_count' => $all->count(),
            ];
        });
    }

    #[Computed]
    public function selectedPayrollActionState(): array
    {
        if (empty($this->selectedPayrolls)) {
            return ['has_actions' => false, 'can_publish' => false, 'can_pay' => false];
        }

        $query = Payroll::whereIn('id', $this->selectedPayrolls);

        $hasDraft = (clone $query)->where('status', PayrollStatus::DRAFT)->exists();
        $hasPublished = (clone $query)->where('status', PayrollStatus::PUBLISHED)->exists();

        return [
            'has_actions' => $this->canFinanceAction() && ($hasDraft || $hasPublished),
            'can_publish' => $hasDraft,
            'can_pay' => $hasPublished,
        ];
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedPayrolls = $this->payrolls->getCollection()->pluck('id')->toArray();
        } else {
            $this->selectedPayrolls = [];
        }
    }

    public function openGenerateModal(): void
    {
        $this->showGenerateModal = true;
    }

    public function generate(): void
    {
        if (! $this->canManage) {
            return;
        }

        $period = sprintf('%d-%02d', $this->year, $this->month);
        $service = app(PayrollCalculatorService::class);

        $employees = Employee::query()
            ->whereHas('position')
            ->cursor();

        $count = 0;

        foreach ($employees as $employee) {
            try {
                $service->generatePayroll($employee, $period);
                $count++;
            } catch (\Throwable $e) {
                // Skip individual failures
            }
        }

        Cache::forget("payroll:summary:{$period}");

        $this->showGenerateModal = false;
        $this->dispatch('toast', variant: 'success', text: "{$count} payroll berhasil digenerate untuk periode {$period}.");
        $this->resetPage();
    }

    public function publish(int $payrollId): void
    {
        if (! $this->canManage) {
            return;
        }

        $payroll = Payroll::findOrFail($payrollId);

        if ($payroll->status !== PayrollStatus::DRAFT) {
            return;
        }

        $payroll->updateQuietly(['status' => PayrollStatus::PUBLISHED]);

        $period = sprintf('%d-%02d', $this->year, $this->month);
        Cache::forget("payroll:summary:{$period}");

        $this->dispatch('toast', variant: 'success', text: 'Payroll berhasil diterbitkan.');
    }

    public function pay(int $payrollId): void
    {
        if (! $this->canManage) {
            return;
        }

        $payroll = Payroll::findOrFail($payrollId);

        if ($payroll->status !== PayrollStatus::PUBLISHED) {
            return;
        }

        $payroll->updateQuietly(['status' => PayrollStatus::PAID]);

        $period = sprintf('%d-%02d', $this->year, $this->month);
        Cache::forget("payroll:summary:{$period}");

        $this->dispatch('toast', variant: 'success', text: 'Payroll berhasil ditandai sebagai sudah dibayar.');
    }

    public function bulkPublish(): void
    {
        if (! $this->canManage || empty($this->selectedPayrolls)) {
            return;
        }

        Payroll::whereIn('id', $this->selectedPayrolls)
            ->where('status', PayrollStatus::DRAFT)
            ->update(['status' => PayrollStatus::PUBLISHED]);

        $period = sprintf('%d-%02d', $this->year, $this->month);
        Cache::forget("payroll:summary:{$period}");

        $this->selectedPayrolls = [];
        $this->selectAll = false;

        $this->dispatch('toast', variant: 'success', text: 'Payroll terpilih berhasil diterbitkan.');
    }

    public function bulkPay(): void
    {
        if (! $this->canManage || empty($this->selectedPayrolls)) {
            return;
        }

        Payroll::whereIn('id', $this->selectedPayrolls)
            ->where('status', PayrollStatus::PUBLISHED)
            ->update(['status' => PayrollStatus::PAID]);

        $period = sprintf('%d-%02d', $this->year, $this->month);
        Cache::forget("payroll:summary:{$period}");

        $this->selectedPayrolls = [];
        $this->selectAll = false;

        $this->dispatch('toast', variant: 'success', text: 'Payroll terpilih berhasil ditandai sebagai sudah dibayar.');
    }

    public function showDetail(int $payrollId): void
    {
        $payroll = Payroll::with(['employee', 'employee.position', 'employee.department'])
            ->findOrFail($payrollId);

        $this->detailPayroll = [
            'id' => $payroll->id,
            'name' => $payroll->employee?->full_name ?? '-',
            'employee_number' => $payroll->employee?->employee_number ?? '-',
            'position' => $payroll->employee?->position?->name ?? '-',
            'department' => $payroll->employee?->department?->name ?? '-',
            'period' => $payroll->period,
            'basic_salary' => (float) ($payroll->basic_salary ?? 0),
            'total_allowance' => (float) ($payroll->total_allowance ?? 0),
            'gross_salary' => (float) ($payroll->gross_salary ?? 0),
            'overtime_pay' => (float) ($payroll->overtime_pay ?? 0),
            'pph21' => (float) ($payroll->pph21 ?? 0),
            'bpjs_health' => (float) ($payroll->bpjs_health ?? 0),
            'bpjs_employment' => (float) ($payroll->bpjs_employment ?? 0),
            'loan_deduction' => (float) ($payroll->loan_deduction ?? 0),
            'attendance_penalty' => (float) ($payroll->attendance_penalty ?? 0),
            'total_deduction' => (float) ($payroll->total_deduction ?? 0),
            'net_salary' => (float) ($payroll->net_salary ?? 0),
            'status' => $payroll->status?->value ?? 'draft',
        ];

        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->detailPayroll = null;
    }

    public function render(): View
    {
        return view('livewire.admin.payroll-manager', [
            'payrolls' => $this->payrolls,
            'summaryCards' => $this->summaryCards,
            'selectedActionState' => $this->selectedPayrollActionState,
        ]);
    }
}
