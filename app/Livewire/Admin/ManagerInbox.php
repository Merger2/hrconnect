<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\CustomFormSubmission;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistTask;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\AttendanceCorrectionService;
use App\Support\CashAdvanceApprovalService;
use App\Support\CustomFormBuilderService;
use App\Support\HrChecklistService;
use App\Support\LeaveApprovalService;
use App\Support\ManagerInboxService;
use App\Support\OvertimeApprovalService;
use App\Support\ReimbursementApprovalService;
use App\Support\ShiftSwapRequestService;
use App\Support\WorkFromHomeRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ManagerInbox extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $activeTab = 'leaves';

    #[Url(history: true)]
    public string $statusFilter = 'pending';

    public string $search = '';

    public ?int $selectedId = null;

    public bool $confirmingRejection = false;

    public string $rejectionReason = '';

    protected ManagerInboxService $inboxService;

    protected LeaveApprovalService $leaveService;

    protected OvertimeApprovalService $overtimeService;

    protected AttendanceCorrectionService $correctionService;

    protected ReimbursementApprovalService $reimbursementService;

    protected CashAdvanceApprovalService $cashAdvanceService;

    protected ShiftSwapRequestService $shiftSwapService;

    protected HrChecklistService $hrChecklistService;

    protected WorkFromHomeRequestService $wfhService;

    protected CustomFormBuilderService $customFormService;

    public function boot(
        ManagerInboxService $inboxService,
        LeaveApprovalService $leaveService,
        OvertimeApprovalService $overtimeService,
        AttendanceCorrectionService $correctionService,
        ReimbursementApprovalService $reimbursementService,
        CashAdvanceApprovalService $cashAdvanceService,
        ShiftSwapRequestService $shiftSwapService,
        HrChecklistService $hrChecklistService,
        WorkFromHomeRequestService $wfhService,
        CustomFormBuilderService $customFormService,
    ): void {
        $this->inboxService = $inboxService;
        $this->leaveService = $leaveService;
        $this->overtimeService = $overtimeService;
        $this->correctionService = $correctionService;
        $this->reimbursementService = $reimbursementService;
        $this->cashAdvanceService = $cashAdvanceService;
        $this->shiftSwapService = $shiftSwapService;
        $this->hrChecklistService = $hrChecklistService;
        $this->wfhService = $wfhService;
        $this->customFormService = $customFormService;
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->canAccessAdminPanel()) {
            abort(403);
        }

        $accessibleTabs = $this->inboxService->accessibleTabs($user);

        if ($accessibleTabs === []) {
            abort(403);
        }

        if (! in_array($this->activeTab, $accessibleTabs, true)) {
            $this->activeTab = $accessibleTabs[0];
        }

        if ($this->activeTab !== 'hr_tasks' && $this->statusFilter === 'blocked') {
            $this->statusFilter = 'pending';
        }
    }

    public function switchTab(string $tab): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! in_array($tab, $this->inboxService->accessibleTabs($user), true)) {
            return;
        }

        $this->activeTab = $tab;
        if ($tab !== 'hr_tasks' && $this->statusFilter === 'blocked') {
            $this->statusFilter = 'pending';
        }
        $this->resetPage();
        $this->selectedId = null;
        $this->confirmingRejection = false;
        $this->rejectionReason = '';
    }

    public function setStatusFilter(string $filter): void
    {
        if (! in_array($filter, ['pending', 'overdue', 'blocked'], true)) {
            return;
        }

        $this->statusFilter = $filter;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getCountsProperty(): array
    {
        $user = Auth::user();

        return $user instanceof User ? $this->inboxService->getPendingCounts($user) : [];
    }

    public function getTabsProperty(): array
    {
        $user = Auth::user();

        return $user instanceof User ? $this->inboxService->accessibleTabs($user) : [];
    }

    public function getOverdueCountsProperty(): array
    {
        $user = Auth::user();

        return $user instanceof User ? $this->inboxService->getOverdueCounts($user) : [];
    }

    public function getSummaryProperty(): array
    {
        $user = Auth::user();

        return $user instanceof User
            ? $this->inboxService->getSummary($user)
            : ['pending' => 0, 'overdue' => 0, 'workflows' => 0];
    }

    // --- Approvals ---

    public function approve($id): void
    {
        $user = Auth::user();

        if (! $user instanceof User || $this->activeTab === 'document_requests') {
            abort(403);
        }

        $item = $this->scopedItem((int) $id);

        switch ($this->activeTab) {
            case 'leaves':
                $this->leaveService->approve([$item->id], $user);
                $this->dispatch('toast', type: 'success', message: __('Leave approved.'));
                break;
            case 'overtime':
                $this->overtimeService->approve($item, $user);
                $this->dispatch('toast', type: 'success', message: __('Overtime approved.'));
                break;
            case 'attendance_corrections':
                $message = $this->correctionService->approve($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'reimbursements':
                $message = $this->reimbursementService->approve($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'cash_advances':
                $message = $this->cashAdvanceService->approve($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'shift_swaps':
                $message = $this->shiftSwapService->approve($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'wfh_requests':
                $message = $this->wfhService->approve($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'hr_tasks':
                $this->hrChecklistService->updateTaskStatus($item, $user, HrChecklistTask::STATUS_DONE, $this->taskCompletionNote($item));
                $this->dispatch('toast', type: 'success', message: __('HR task marked as done.'));
                break;
            case 'custom_forms':
                $message = $this->customFormService->markReviewed($user, $item);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
        }
    }

    public function confirmReject($id): void
    {
        if (in_array($this->activeTab, ['document_requests', 'custom_forms'], true)) {
            return;
        }

        $this->scopedItem((int) $id);
        $this->selectedId = $id;
        $this->confirmingRejection = true;
    }

    public function cancelReject(): void
    {
        $this->selectedId = null;
        $this->confirmingRejection = false;
        $this->rejectionReason = '';
    }

    public function reject(): void
    {
        if (! $this->selectedId) {
            return;
        }

        $user = Auth::user();
        $item = $this->scopedItem((int) $this->selectedId);

        switch ($this->activeTab) {
            case 'leaves':
                $this->leaveService->reject([$item->id], $user, $this->rejectionReason);
                $this->dispatch('toast', type: 'success', message: __('Leave rejected.'));
                break;
            case 'overtime':
                $this->overtimeService->reject($item, $user, $this->rejectionReason);
                $this->dispatch('toast', type: 'success', message: __('Overtime rejected.'));
                break;
            case 'attendance_corrections':
                $message = $this->correctionService->reject($item, $user, $this->rejectionReason);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'reimbursements':
                $message = $this->reimbursementService->reject($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'cash_advances':
                $message = $this->cashAdvanceService->reject($item, $user);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'shift_swaps':
                $message = $this->shiftSwapService->reject($item, $user, $this->rejectionReason);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'wfh_requests':
                $message = $this->wfhService->reject($item, $user, $this->rejectionReason);
                $this->dispatch('toast', type: 'success', message: $message);
                break;
            case 'hr_tasks':
                $this->hrChecklistService->updateTaskStatus($item, $user, HrChecklistTask::STATUS_BLOCKED, $this->rejectionReason);
                $this->dispatch('toast', type: 'success', message: __('HR task marked as blocked.'));
                break;
        }

        $this->cancelReject();
    }

    private function scopedItem(int $id)
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403, 'Unauthorized action.');
        }

        $item = $this->itemsQuery($user, false)
            ->whereKey($id)
            ->first();

        if (! $item) {
            abort(403, 'Unauthorized action.');
        }

        return $item;
    }

    private function itemsQuery(User $admin, bool $withSearch = true): Builder
    {
        if (! in_array($this->activeTab, $this->inboxService->accessibleTabs($admin), true)) {
            abort(403, 'Unauthorized action.');
        }

        $search = $withSearch ? trim($this->search) : '';
        $searchClosure = fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
        $overdueAt = now()->subDays(ManagerInboxService::OVERDUE_AFTER_DAYS);
        $overdueClosure = fn (Builder $query) => $query->where('created_at', '<=', $overdueAt);
        $applyInboxFilters = function (Builder $query) use ($search, $searchClosure, $overdueClosure): Builder {
            return $query
                ->when($search !== '', $searchClosure)
                ->when($this->statusFilter === 'overdue', $overdueClosure);
        };

        return match ($this->activeTab) {
            'overtime' => Overtime::query()
                ->with('user')
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->where('status', 'pending')
                ->tap($applyInboxFilters)
                ->latest(),
            'attendance_corrections' => AttendanceCorrection::query()
                ->with(['user', 'requestedShift'])
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->whereIn('status', [AttendanceCorrection::STATUS_PENDING, AttendanceCorrection::STATUS_PENDING_ADMIN])
                ->tap($applyInboxFilters)
                ->latest(),
            'reimbursements' => Reimbursement::query()
                ->with('user')
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->where('status', 'pending')
                ->tap($applyInboxFilters)
                ->latest(),
            'cash_advances' => CashAdvance::query()
                ->with('user')
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->where('status', 'pending')
                ->tap($applyInboxFilters)
                ->latest(),
            'shift_swaps' => ShiftSwapRequest::query()
                ->with(['user', 'schedule.shift', 'requestedShift'])
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->where('status', ShiftSwapRequest::STATUS_PENDING)
                ->tap($applyInboxFilters)
                ->latest(),
            'wfh_requests' => WorkFromHomeRequest::query()
                ->with('user')
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->where('status', WorkFromHomeRequest::STATUS_PENDING)
                ->tap($applyInboxFilters)
                ->latest(),
            'document_requests' => EmployeeDocumentRequest::query()
                ->with('user')
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->whereIn('status', [EmployeeDocumentRequest::STATUS_PENDING, EmployeeDocumentRequest::STATUS_REQUESTED])
                ->tap($applyInboxFilters)
                ->latest(),
            'hr_tasks' => HrChecklistTask::query()
                ->with(['case.user', 'assignee', 'dependency'])
                ->whereHas('case.user', fn (Builder $query) => $query->managedBy($admin))
                ->where(function (Builder $query): void {
                    if ($this->statusFilter === 'blocked') {
                        $query->where('status', HrChecklistTask::STATUS_BLOCKED);

                        return;
                    }

                    $query->whereIn('status', [HrChecklistTask::STATUS_PENDING, HrChecklistTask::STATUS_BLOCKED]);
                })
                ->when($search !== '', fn (Builder $query) => $query->whereHas('case.user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')))
                ->when($this->statusFilter === 'overdue', fn (Builder $query) => $query->where(function (Builder $overdueQuery): void {
                    $overdueQuery->reminderReady()
                        ->orWhere('status', HrChecklistTask::STATUS_BLOCKED);
                }))
                ->latest(),
            'leaves' => Attendance::query()
                ->with(['user', 'leaveType'])
                ->whereHas('user', fn (Builder $query) => $query->managedBy($admin))
                ->where('approval_status', 'pending')
                ->whereNotNull('leave_type_id')
                ->tap($applyInboxFilters)
                ->latest(),
            'custom_forms' => CustomFormSubmission::query()
                ->with(['template:id,title,category', 'submitter:id,name,email,profile_photo_path,job_title_id,company_id'])
                ->whereHas('submitter', fn (Builder $query) => $query->managedBy($admin))
                ->where('status', CustomFormSubmission::STATUS_SUBMITTED)
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->whereHas('submitter', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('template', fn (Builder $templateQuery) => $templateQuery->where('title', 'like', '%'.$search.'%'));
                }))
                ->when($this->statusFilter === 'overdue', $overdueClosure)
                ->latest(),
            default => abort(403, 'Unauthorized action.'),
        };
    }

    private function taskCompletionNote(HrChecklistTask $task): ?string
    {
        return $task->notes ?: __('Marked done from Manager Inbox.');
    }

    public function render()
    {
        $admin = Auth::user();

        if (! $admin instanceof User) {
            abort(403);
        }

        return view('livewire.admin.manager-inbox', [
            'availableTabs' => $this->tabs,
            'items' => $this->itemsQuery($admin)->paginate(15),
            'overdueAfterDays' => ManagerInboxService::OVERDUE_AFTER_DAYS,
        ]);
    }
}
