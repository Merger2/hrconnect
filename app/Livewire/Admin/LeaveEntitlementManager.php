<?php

namespace App\Livewire\Admin;

use App\Models\LeaveEntitlement;
use App\Models\User;
use App\Support\LeaveEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LeaveEntitlementManager extends Component
{
    use InteractsWithBanner, WithPagination;

    protected LeaveEntitlementService $leaveEntitlements;

    public string $search = '';

    public string $userId = '';

    public int $year;

    public string $allocatedDays = '12';

    public string $carriedOverDays = '0';

    public string $expiresAt = '';

    public string $notes = '';

    public function boot(LeaveEntitlementService $leaveEntitlements): void
    {
        Gate::authorize('manageLeaveEntitlements');

        $this->leaveEntitlements = $leaveEntitlements;
    }

    public function mount(): void
    {
        $this->year = now()->year;
        $this->expiresAt = now()->endOfYear()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        Gate::authorize('manageLeaveEntitlements');

        $validated = $this->validate([
            'userId' => ['required', 'string', Rule::exists('users', 'id')],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'allocatedDays' => ['required', 'numeric', 'min:0', 'max:366'],
            'carriedOverDays' => ['required', 'numeric', 'min:0', 'max:366'],
            'expiresAt' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = User::query()->where('group', 'user')->findOrFail($validated['userId']);
        $actor = auth()->user();

        abort_unless($actor instanceof User && $this->leaveEntitlements->canAccessUser($actor, $employee), 403);

        $this->leaveEntitlements->createOrUpdateAnnualEntitlement(
            user: $employee,
            year: (int) $validated['year'],
            allocatedDays: (float) $validated['allocatedDays'],
            expiresAt: filled($validated['expiresAt']) ? Carbon::parse($validated['expiresAt']) : null,
            carriedOverDays: (float) $validated['carriedOverDays'],
            notes: filled($validated['notes']) ? trim((string) $validated['notes']) : null,
        );

        $this->reset(['userId', 'notes']);
        $this->allocatedDays = '12';
        $this->carriedOverDays = '0';
        $this->banner(__('Leave entitlement saved.'));
    }

    public function render()
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 401);

        $employees = User::query()
            ->where('group', 'user')
            ->when(! $actor->isSuperadmin && $actor->company_id !== null, fn (Builder $query) => $query->where('company_id', $actor->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company_id']);

        $entitlements = LeaveEntitlement::query()
            ->with(['user.company', 'leaveType'])
            ->when(! $actor->isSuperadmin && $actor->company_id !== null, fn (Builder $query) => $query->where('company_id', $actor->company_id))
            ->when($this->search !== '', function (Builder $query): void {
                $query->whereHas('user', function (Builder $userQuery): void {
                    $userQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('nip', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('year')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.leave-entitlement-manager', [
            'employees' => $employees,
            'entitlements' => $entitlements,
        ]);
    }
}
