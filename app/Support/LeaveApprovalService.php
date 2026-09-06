<?php

namespace App\Support;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\Leave;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LeaveApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
        protected ApprovalService $approvals,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Collection<int, Leave>>
     */
    public function groupedRequests(
        User $actor,
        string $statusFilter = 'all',
        string $requestTypeFilter = 'all',
        string $search = '',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $groups = $this->baseQuery($actor, $statusFilter, $requestTypeFilter, $search)
            ->orderByDesc('end_date')
            ->paginate($perPage);

        $groups->setCollection($groups->getCollection()->map(fn (Leave $leave) => collect([$leave])));

        return $groups;
    }

    private function baseQuery(User $actor, string $statusFilter, string $requestTypeFilter, string $search): Builder
    {
        return Leave::query()
            ->with(['employee.user', 'employee.division', 'employee.position', 'leaveType', 'approvals'])
            ->when($statusFilter === RequestStatus::PENDING->value, fn (Builder $query) => $query->whereIn('status', [
                RequestStatus::PENDING->value,
                RequestStatus::APPROVED_L1->value,
            ]))
            ->when(in_array($statusFilter, [RequestStatus::APPROVED->value, RequestStatus::REJECTED->value], true), fn (Builder $query) => $query->where('status', $statusFilter))
            ->when(! $actor->can('manageLeaveApprovals'), fn (Builder $query) => $query->whereHas('employee', fn (Builder $q) => $q->whereIn('user_id', $this->approvalActors->subordinateIds($actor))))
            ->when($requestTypeFilter !== 'all', function (Builder $query) use ($requestTypeFilter): void {
                if (ctype_digit($requestTypeFilter)) {
                    $query->where('leave_type_id', (int) $requestTypeFilter);

                    return;
                }

                $query->where('day_type', $requestTypeFilter);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('reason', 'like', '%'.$search.'%')
                        ->orWhere('rejection_reason', 'like', '%'.$search.'%')
                        ->orWhereHas('employee.user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('employee', function (Builder $employeeQuery) use ($search): void {
                            $employeeQuery->where('employee_number', 'like', '%'.$search.'%');
                        });
                });
            });
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function approve(array $ids, User $actor): void
    {
        $approvals = $this->authorizedPendingApprovals($ids, $actor);

        if ($approvals->count() !== count($ids)) {
            abort(403, 'Unauthorized action.');
        }

        $approvals->each(fn (Approval $approval) => $this->approvals->approve($approval));
        $this->notifyUpdated($approvals);
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function reject(array $ids, User $actor, ?string $rejectionNote = null): void
    {
        $approvals = $this->authorizedPendingApprovals($ids, $actor);

        if ($approvals->count() !== count($ids)) {
            abort(403, 'Unauthorized action.');
        }

        $approvals->each(fn (Approval $approval) => $this->approvals->reject(
            $approval,
            $rejectionNote ?: __('Rejected by HR.')
        ));
        $this->notifyUpdated($approvals);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    public function authorizedRequestIds(array $ids, User $actor): array
    {
        return Leave::query()
            ->whereIn('id', $ids)
            ->whereIn('status', [RequestStatus::PENDING->value, RequestStatus::APPROVED_L1->value])
            ->whereHas('approvals', fn (Builder $query) => $this->pendingApprovalScope($query, $actor))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Approval>
     */
    protected function authorizedPendingApprovals(array $ids, User $actor): Collection
    {
        return Approval::query()
            ->with('approvable')
            ->where('approvable_type', Leave::class)
            ->whereIn('approvable_id', $ids)
            ->where('status', ApprovalStatus::PENDING)
            ->where(fn (Builder $query) => $this->pendingApprovalScope($query, $actor))
            ->get()
            ->unique('approvable_id')
            ->values();
    }

    protected function pendingApprovalScope(Builder $query, User $actor): void
    {
        if ($actor->can('manageLeaveApprovals')) {
            $query->where('level', ApprovalLevel::L2_MANAGER);

            return;
        }

        $employee = $actor->employee;

        if (! $employee) {
            throw new HttpException(403, 'Unauthorized action.');
        }

        $query
            ->where('level', ApprovalLevel::L1_SUPERVISOR)
            ->where('approver_id', $employee->id);
    }

    /**
     * @param  Collection<int, Approval>  $approvals
     */
    protected function notifyUpdated(Collection $approvals): void
    {
        $approvals
            ->pluck('approvable')
            ->filter(fn ($approvable) => $approvable instanceof Leave)
            ->unique('id')
            ->each(fn (Leave $leave) => $leave->employee?->user?->notify(new LeaveStatusUpdated($leave)));
    }
}
