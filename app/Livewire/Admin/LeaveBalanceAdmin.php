<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LeaveBalanceAdmin extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $leaveTypeId = null;

    #[Url]
    public ?int $year = null;

    public int $perPage = 15;

    public function mount(): void
    {
        $this->year ??= (int) now()->year;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLeaveTypeId(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $balances = LeaveBalance::query()
            ->with(['employee.position', 'leaveType'])
            ->when(filled($this->year), fn ($q) => $q->where('year', $this->year))
            ->when(filled($this->leaveTypeId), fn ($q) => $q->where('leave_type_id', $this->leaveTypeId))
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search)
                    ->orWhere('employee_number', 'ilike', $search));
            })
            ->orderByDesc('year')
            ->paginate($this->perPage);

        $leaveTypes = LeaveType::orderBy('name')->get();
        $years = LeaveBalance::selectRaw('DISTINCT year')->orderBy('year', 'desc')->pluck('year')->toArray();

        return view('livewire.admin.leave-balance-admin', [
            'balances' => $balances,
            'leaveTypes' => $leaveTypes,
            'years' => $years,
        ]);
    }
}
