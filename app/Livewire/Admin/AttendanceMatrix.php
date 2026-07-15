<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AttendanceMatrix extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $month = null;

    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $branchId = null;

    public int $perPage = 15;

    protected $queryString = ['search' => ['except' => '']];

    public function Mount(): void
    {
        $this->month ??= (int) now()->month;
        $this->year ??= (int) now()->year;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingBranchId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $attendances = Attendance::query()
            ->with(['employee.position', 'employee.branch', 'employee.department'])
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->when(filled($this->branchId), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', $this->branchId)))
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search)
                    ->orWhere('employee_number', 'ilike', $search));
            })
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);

        $branches = Branch::orderBy('name')->get();

        $stats = [
            'present' => Attendance::whereYear('date', $this->year)->whereMonth('date', $this->month)->whereNotIn('status', [AttendanceStatus::ABSENT, AttendanceStatus::HOLIDAY, AttendanceStatus::MISSED_CLOCK_IN])->count(),
            'late' => Attendance::whereYear('date', $this->year)->whereMonth('date', $this->month)->where('late_minutes', '>', 0)->count(),
            'absent' => Attendance::whereYear('date', $this->year)->whereMonth('date', $this->month)->where('status', AttendanceStatus::ABSENT)->count(),
            'wfa' => Attendance::whereYear('date', $this->year)->whereMonth('date', $this->month)->where('is_wfa', true)->count(),
        ];

        return view('livewire.admin.attendance-matrix', [
            'attendances' => $attendances,
            'branches' => $branches,
            'stats' => $stats,
        ]);
    }
}
