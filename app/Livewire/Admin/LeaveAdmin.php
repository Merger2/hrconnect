<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\RequestStatus;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LeaveAdmin extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public ?int $leaveTypeId = null;

    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLeaveTypeId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $leaves = Leave::query()
            ->with(['employee.position', 'employee.department', 'leaveType', 'approver'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->leaveTypeId), fn ($q) => $q->where('leave_type_id', $this->leaveTypeId))
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $leaveTypes = LeaveType::orderBy('name')->get();

        $stats = [
            'pending' => Leave::where('status', RequestStatus::PENDING)->count(),
            'approved' => Leave::where('status', RequestStatus::APPROVED)->count(),
            'rejected' => Leave::where('status', RequestStatus::REJECTED)->count(),
        ];

        return view('livewire.admin.leave-admin', [
            'leaves' => $leaves,
            'leaveTypes' => $leaveTypes,
            'stats' => $stats,
        ]);
    }
}
