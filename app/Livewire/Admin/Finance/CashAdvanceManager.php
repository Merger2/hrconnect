<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Models\CashAdvance;
use App\Support\CashAdvanceApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class CashAdvanceManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $activeTab = 'requests';

    protected $queryString = ['search', 'statusFilter', 'activeTab'];

    public function mount(): void
    {
        Gate::authorize('manageCashAdvances');
        $this->activeTab = in_array($this->activeTab, ['requests', 'users'], true) ? $this->activeTab : 'requests';
    }

    public function render(): View
    {
        $service = app(CashAdvanceApprovalService::class);
        $data = $service->managementViewData(
            auth()->user(),
            $this->activeTab,
            $this->statusFilter,
            $this->search
        );

        return view('livewire.admin.finance.cash-advance-manager', [
            'advances' => $data['advances'],
            'userGrouped' => $data['userGrouped'],
        ]);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['requests', 'users'], true) ? $tab : 'requests';
        $this->resetPage();
    }

    public function approve(string $id): void
    {
        $advance = CashAdvance::findOrFail($id);
        $this->authorize('approve', $advance);

        $message = app(CashAdvanceApprovalService::class)->approve($advance, auth()->user());

        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function reject(string $id): void
    {
        $advance = CashAdvance::findOrFail($id);
        $this->authorize('reject', $advance);

        $message = app(CashAdvanceApprovalService::class)->reject($advance, auth()->user());

        $this->dispatch('notify', type: 'warning', message: $message);
    }

    public function delete(string $id): void
    {
        $advance = CashAdvance::findOrFail($id);
        $this->authorize('delete', $advance);

        $advance->delete();

        $this->dispatch('notify', type: 'success', message: __('Cash advance request deleted.'));
    }
}
