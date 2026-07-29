<?php

namespace App\Livewire\Admin;

use App\Actions\Reimbursement\ReviewReimbursement;
use App\Models\Reimbursement;
use App\Support\ReimbursementApprovalService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ReimbursementManager extends Component
{
    use WithPagination;

    protected ReimbursementApprovalService $reimbursementApprovals;

    protected ReviewReimbursement $reviewReimbursement;

    public $statusFilter = 'pending';

    public $search = '';

    public function boot(ReimbursementApprovalService $reimbursementApprovals, ReviewReimbursement $reviewReimbursement): void
    {
        $this->reimbursementApprovals = $reimbursementApprovals;
        $this->reviewReimbursement = $reviewReimbursement;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function approve($id)
    {
        Gate::authorize('manageReimbursements');
        $message = $this->reviewReimbursement->approve($id, auth()->user());
        session()->flash('success', $message);
        $this->dispatch('saved');
    }

    public function reject($id)
    {
        Gate::authorize('manageReimbursements');
        $message = $this->reviewReimbursement->reject($id, auth()->user());
        session()->flash('success', $message);
        $this->dispatch('saved');
    }

    public function render()
    {
        Gate::authorize('viewAdminAny', Reimbursement::class);
        $reimbursements = $this->reimbursementApprovals
            ->managementQuery(auth()->user(), (string) $this->statusFilter, (string) $this->search)
            ->paginate(10);

        return view('livewire.admin.reimbursement-manager', [
            'reimbursements' => $reimbursements,
        ]);
    }
}
