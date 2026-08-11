<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\PayrollStatus;
use App\Events\PayrollApproved;
use App\Events\PayrollPaid;
use App\Events\PayrollRejected;
use App\Events\PayrollSubmitted;
use App\Events\PayrollVerified;
use App\Jobs\SendPayrollPayslipEmail;
use App\Models\Payroll;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class PayrollManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $periodFilter = '';

    public string $statusFilter = '';

    public int $perPage = 20;

    public string $sortField = 'period';

    public string $sortDirection = 'desc';

    public ?int $rejectingPayrollId = null;

    public string $rejectionReason = '';

    protected $queryString = ['search', 'periodFilter', 'statusFilter', 'sortField', 'sortDirection'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->periodFilter = now()->format('Y-m');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Payroll::class);

        $query = Payroll::with('employee:id,employee_number,full_name')
            ->orderBy($this->sortField, $this->sortDirection);

        if ($this->search) {
            $query->whereHas('employee', fn ($q) => $q
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('employee_number', 'like', "%{$this->search}%")
            );
        }

        if ($this->periodFilter) {
            $query->where('period', $this->periodFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $payrolls = $query->paginate($this->perPage);

        return view('livewire.admin.payroll-manager', [
            'payrolls' => $payrolls,
            'statuses' => PayrollStatus::cases(),
        ]);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
            $this->sortField = $field;
        }
    }

    public function submit(Payroll $payroll): void
    {
        $this->authorize('update', $payroll);

        $payroll->update(['status' => PayrollStatus::SUBMITTED]);
        event(new PayrollSubmitted($payroll));

        $this->dispatch('notify', type: 'success', message: 'Payroll diajukan untuk verifikasi.');
    }

    public function verify(Payroll $payroll): void
    {
        $this->authorize('update', $payroll);

        $payroll->update(['status' => PayrollStatus::VERIFIED]);
        event(new PayrollVerified($payroll));

        $this->dispatch('notify', type: 'success', message: 'Payroll diverifikasi.');
    }

    public function approve(Payroll $payroll): void
    {
        $this->authorize('update', $payroll);

        $payroll->update(['status' => PayrollStatus::APPROVED]);
        event(new PayrollApproved($payroll));

        $this->dispatch('notify', type: 'success', message: 'Payroll disetujui.');
    }

    public function confirmReject(Payroll $payroll): void
    {
        $this->authorize('update', $payroll);

        $this->rejectingPayrollId = $payroll->id;
        $this->rejectionReason = '';
    }

    public function reject(): void
    {
        $this->validate(['rejectionReason' => 'required|string|min:3']);

        $payroll = Payroll::findOrFail($this->rejectingPayrollId);

        $this->authorize('update', $payroll);

        $payroll->update([
            'status' => PayrollStatus::DRAFT,
            'rejection_reason' => $this->rejectionReason,
        ]);
        event(new PayrollRejected($payroll, $this->rejectionReason));

        $this->rejectingPayrollId = null;
        $this->rejectionReason = '';

        $this->dispatch('notify', type: 'warning', message: 'Payroll ditolak dan dikembalikan ke Draft.');
    }

    public function cancelReject(): void
    {
        $this->rejectingPayrollId = null;
        $this->rejectionReason = '';
    }

    public function markPaid(Payroll $payroll): void
    {
        $this->authorize('update', $payroll);

        $payroll->update(['status' => PayrollStatus::PAID]);

        event(new PayrollPaid($payroll));
        SendPayrollPayslipEmail::dispatch($payroll->id);

        $this->dispatch('notify', type: 'success', message: 'Payroll ditandai ditransfer. Email payslip terkirim.');
    }

    // Download payslip dipindah ke route web `payslip.download` (P1 fix
    // 2026-08-11): dispatch 'download-file' tidak punya listener di JS/blade.

    #[On('payroll-generated')]
    public function refreshPayrolls(): void
    {
        $this->resetPage();
    }
}
