<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ReimbursementStatus;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ReimbursementAdmin extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public ?int $categoryId = null;

    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $reimbursements = Reimbursement::query()
            ->with(['employee.position', 'employee.department', 'category', 'approver'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->categoryId), fn ($q) => $q->where('reimbursement_category_id', $this->categoryId))
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search));
            })
            ->orderBy('expense_date', 'desc')
            ->paginate($this->perPage);

        $categories = ReimbursementCategory::orderBy('name')->get();

        $stats = [
            'pending' => Reimbursement::where('status', ReimbursementStatus::PENDING)->count(),
            'approved' => Reimbursement::where('status', ReimbursementStatus::APPROVED)->count(),
            'rejected' => Reimbursement::where('status', ReimbursementStatus::REJECTED)->count(),
            'paid' => Reimbursement::where('status', ReimbursementStatus::PAID)->count(),
        ];

        return view('livewire.admin.reimbursement-admin', [
            'reimbursements' => $reimbursements,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }
}
