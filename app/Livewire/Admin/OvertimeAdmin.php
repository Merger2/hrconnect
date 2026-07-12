<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\RequestStatus;
use App\Models\Overtime;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OvertimeAdmin extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $overtimes = Overtime::query()
            ->with(['employee.position', 'employee.department', 'approver'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search));
            })
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);

        $stats = [
            'pending' => Overtime::where('status', RequestStatus::PENDING)->count(),
            'approved' => Overtime::where('status', RequestStatus::APPROVED)->count(),
            'rejected' => Overtime::where('status', RequestStatus::REJECTED)->count(),
        ];

        return view('livewire.admin.overtime-admin', [
            'overtimes' => $overtimes,
            'stats' => $stats,
        ]);
    }
}
