<?php

declare(strict_types=1);

namespace App\Livewire\User\Finance;

use App\Models\CashAdvance;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

final class MyCashAdvances extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showCreateModal = false;

    public ?float $amount = null;

    public string $purpose = '';

    public ?int $payment_month = null;

    public ?int $payment_year = null;

    public function mount(): void
    {
        $this->authorize('viewAny', CashAdvance::class);

        $this->payment_month = (int) now()->month;
        $this->payment_year = (int) now()->year;
    }

    public function render(): View
    {
        $user = auth()->user();
        $employee = $user->employee;

        $advances = CashAdvance::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.user.finance.my-cash-advances', [
            'advances' => $advances,
            'canRequestCashAdvance' => $this->canRequestCashAdvance($employee),
            'totalUnpaid' => $advances->whereIn('status', ['pending', 'pending_finance', 'approved'])->sum('amount'),
            'totalPaid' => $advances->where('status', 'paid')->sum('amount'),
            'basicSalary' => $employee->basic_salary ?? 0,
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['amount', 'purpose']);
        $this->payment_month = (int) now()->month;
        $this->payment_year = (int) now()->year;
        $this->showCreateModal = true;
    }

    public function submit(): void
    {
        $this->authorize('create', CashAdvance::class);

        $validated = $this->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'purpose' => ['required', 'string', 'max:1000'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
            'payment_year' => ['required', 'integer', 'min:'.now()->year],
        ]);

        CashAdvance::create([
            'user_id' => auth()->id(),
            'amount' => $validated['amount'],
            'purpose' => $validated['purpose'],
            'payment_month' => $validated['payment_month'],
            'payment_year' => $validated['payment_year'],
            'status' => 'pending',
        ]);

        $this->showCreateModal = false;
        $this->reset(['amount', 'purpose']);

        $this->dispatch('notify', type: 'success', message: __('Cash advance request submitted.'));
    }

    public function delete(int $id): void
    {
        $advance = CashAdvance::where('user_id', auth()->id())->findOrFail($id);

        if ($advance->status !== 'pending') {
            $this->dispatch('notify', type: 'error', message: __('Only pending requests can be cancelled.'));

            return;
        }

        $advance->delete();

        $this->dispatch('notify', type: 'success', message: __('Cash advance request cancelled.'));
    }

    private function canRequestCashAdvance(?Employee $employee): bool
    {
        return $employee !== null && $employee->basic_salary > 0;
    }
}
