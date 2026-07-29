<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Support\ImportExportRunViewService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ActivityLogs extends Component
{
    use WithPagination;

    private const ACTOR_GROUPS = ['all', 'user', 'admin', 'superadmin'];

    protected ImportExportRunViewService $importExportRuns;

    public $search = '';

    public $dateStart = '';

    public $dateEnd = '';

    public string $actorGroup = 'all';

    public function boot(ImportExportRunViewService $importExportRuns): void
    {
        $this->importExportRuns = $importExportRuns;
    }

    public function mount()
    {
        Gate::authorize('viewActivityLogs');

        // Default to this month
        $this->dateStart = now()->startOfMonth()->format('Y-m-d');
        $this->dateEnd = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDateStart(): void
    {
        $this->resetPage();
    }

    public function updatingDateEnd(): void
    {
        $this->resetPage();
    }

    public function updatingActorGroup(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (! in_array($this->actorGroup, self::ACTOR_GROUPS, true)) {
            $this->actorGroup = 'all';
        }

        $logs = ActivityLog::with('user')
            ->when(
                $this->actorGroup !== 'all',
                fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('group', $this->actorGroup))
            )
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($u) {
                            $u->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->dateStart, function ($query) {
                $query->where('created_at', '>=', Carbon::parse($this->dateStart)->startOfDay());
            })
            ->when($this->dateEnd, function ($query) {
                $query->where('created_at', '<=', Carbon::parse($this->dateEnd)->endOfDay());
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.activity-logs', [
            'logs' => $logs,
            'recentExportRuns' => $this->importExportRuns
                ->recentForResources(['activity_logs'], auth()->user(), 6),
        ]);
    }

    public function export()
    {
        Gate::authorize('exportActivityLogs');

        return redirect()->route('admin.activity-logs.export', [
            'search' => $this->search,
            'start_date' => $this->dateStart,
            'end_date' => $this->dateEnd,
            'actor_group' => $this->actorGroup,
        ]);
    }
}
