<?php

namespace App\Support;

use App\Models\ApprovalMatrixRule;
use App\Models\CashAdvance;
use App\Models\User;
use App\Notifications\CashAdvanceUpdated;
use App\Notifications\CashAdvanceUpdatedEmail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashAdvanceApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
        protected ApprovalMatrixService $approvalMatrix,
    ) {}

    public function approve(CashAdvance $advance, User $actor): string
    {
        return DB::transaction(function () use ($advance, $actor): string {
            $advance = $this->lock($advance);
            $matrixMessage = $this->approveWithMatrix($advance, $actor);

            if ($matrixMessage !== null) {
                return $matrixMessage;
            }

            $canFinalize = $this->approvalActors->canFinalizeCashAdvanceApproval($actor);
            $this->ensureReviewable($advance, $canFinalize ? ['pending', 'pending_finance'] : ['pending']);

            if ($canFinalize) {
                $advance->update([
                    'status' => 'approved',
                    'finance_approved_by' => $actor->id,
                    'finance_approved_at' => now(),
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ]);

                $this->notifyStatusUpdated($advance);

                return __('Kasbon approved.');
            }

            $advance->update([
                'status' => 'pending_finance',
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ]);

            $this->notifyStatusUpdated($advance);

            return __('Kasbon forwarded to Finance for final approval.');
        });
    }

    public function reject(CashAdvance $advance, User $actor): string
    {
        return DB::transaction(function () use ($advance, $actor): string {
            $advance = $this->lock($advance);
            $matrixMessage = $this->rejectWithMatrix($advance, $actor);

            if ($matrixMessage !== null) {
                return $matrixMessage;
            }

            $canFinalize = $this->approvalActors->canFinalizeCashAdvanceApproval($actor);
            $this->ensureReviewable($advance, $canFinalize ? ['pending', 'pending_finance'] : ['pending']);

            $payload = [
                'status' => 'rejected',
            ];

            if ($canFinalize) {
                $payload += [
                    'finance_approved_by' => $actor->id,
                    'finance_approved_at' => now(),
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ];
            } else {
                $payload += [
                    'head_approved_by' => $actor->id,
                    'head_approved_at' => now(),
                ];
            }

            $advance->update($payload);
            $this->notifyStatusUpdated($advance);

            return __('Kasbon rejected.');
        });
    }

    public function canManage(CashAdvance $advance, User $user): bool
    {
        if ($this->approvalMatrix->canActorApprove($user, ApprovalMatrixRule::MODULE_CASH_ADVANCE, $advance)) {
            return true;
        }

        if ($user->can('manageCashAdvances')) {
            return true;
        }

        $myRank = $user->jobTitle?->jobLevel?->rank;
        $myDivisionId = $user->division_id;

        if (! $myRank || $myRank > 2) {
            return false;
        }

        if ($this->approvalActors->isFinanceHead($user)) {
            if ($advance->status === 'pending_finance') {
                return true;
            }

            return $advance->user->division_id === $myDivisionId
                && $advance->user->jobTitle?->jobLevel?->rank > $myRank;
        }

        return $advance->user->division_id === $myDivisionId
            && $advance->user->jobTitle?->jobLevel?->rank > $myRank
            && $advance->status === 'pending';
    }

    /**
     * @return array{advances: LengthAwarePaginator|Collection, userGrouped: LengthAwarePaginator|Collection}
     */
    public function managementViewData(User $user, string $activeTab, string $statusFilter = 'all', string $search = ''): array
    {
        if ($activeTab === 'requests') {
            $query = CashAdvance::query()->with([
                'user.employee.position',
                'user.employee.city',
                'approver',
                'headApprover',
                'financeApprover',
            ]);

            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            if ($search !== '') {
                $query->whereHas('user', fn (Builder $builder) => $builder->where('name', 'like', '%'.$search.'%'));
            }

            if (! $user->can('manageCashAdvances')) {
                $myRank = $user->jobTitle?->jobLevel?->rank;
                $myDivisionId = $user->division_id;

                if ($myRank && $myRank <= 2) {
                    if ($this->approvalActors->isFinanceHead($user)) {
                        $query->where(function (Builder $builder) use ($myDivisionId, $myRank) {
                            $builder->where('status', 'pending_finance')
                                ->orWhere('status', 'pending_matrix')
                                ->orWhereHas('user', function (Builder $userQuery) use ($myDivisionId, $myRank) {
                                    $userQuery->whereHas('employee', fn (Builder $divisionQuery) => $divisionQuery->where('division_id', $myDivisionId))
                                        ->whereHas('employee.position.jobTitle.jobLevel', fn (Builder $levelQuery) => $levelQuery->where('rank', '>', $myRank));
                                });
                        });
                    } else {
                        $query->where(function (Builder $builder) use ($myDivisionId, $myRank) {
                            $builder->where('status', 'pending_matrix')
                                ->orWhereHas('user', function (Builder $userQuery) use ($myDivisionId, $myRank) {
                                    $userQuery->whereHas('employee', fn (Builder $divisionQuery) => $divisionQuery->where('division_id', $myDivisionId))
                                        ->whereHas('employee.position.jobTitle.jobLevel', fn (Builder $levelQuery) => $levelQuery->where('rank', '>', $myRank));
                                });
                        });
                    }
                } else {
                    $query->where('id', 0);
                }
            }

            return [
                'advances' => $query->orderBy('created_at', 'desc')->paginate(10),
                'userGrouped' => collect(),
            ];
        }

        $query = User::query()->with([
            'employee.position',
            'employee.city',
            'cashAdvances' => fn ($query) => $query->whereIn('status', ['approved', 'paid', 'pending', 'pending_finance', 'rejected']),
        ])->whereHas('cashAdvances');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if (! $user->can('manageCashAdvances')) {
            $myRank = $user->jobTitle?->jobLevel?->rank;

            if ($myRank && $myRank <= 2) {
                $query->whereHas('employee.position.jobTitle.jobLevel', fn (Builder $builder) => $builder->where('rank', '>', $myRank));
            } else {
                $query->where('id', 0);
            }
        }

        return [
            'advances' => collect(),
            'userGrouped' => $query->paginate(10),
        ];
    }

    protected function notifyStatusUpdated(CashAdvance $advance): void
    {
        $advance->loadMissing('user');

        if (! $advance->user) {
            return;
        }

        $advance->user->notify(new CashAdvanceUpdated($advance));
        $advance->user->notify(new CashAdvanceUpdatedEmail($advance));
    }

    private function approveWithMatrix(CashAdvance $advance, User $actor): ?string
    {
        $advance->loadMissing('user');
        $steps = $this->approvalMatrix->storedOrResolvedSteps(ApprovalMatrixRule::MODULE_CASH_ADVANCE, $advance);

        if ($steps === []) {
            return null;
        }

        $this->ensureReviewable($advance, ['pending', 'pending_finance', 'pending_matrix']);

        $completed = $this->approvalMatrix->completedSteps($advance);
        $currentStep = $this->approvalMatrix->currentStep($steps, $completed);

        if (! $this->approvalMatrix->canActorApproveStep($actor, $advance, $currentStep)) {
            throw new AuthorizationException;
        }

        $completed[] = [
            'key' => (string) ($currentStep['key'] ?? ''),
            'label' => (string) ($currentStep['label'] ?? $currentStep['key'] ?? ''),
            'actor_id' => $actor->id,
            'approved_at' => now()->toIso8601String(),
        ];

        $nextStep = $this->approvalMatrix->currentStep($steps, $completed);
        $payload = $this->matrixPayload($advance, $steps, $completed, $nextStep);

        // approval_level-based matrix steps carry numeric keys ('1', '2'), so a
        // literal 'finance'/'finance_head' key check never fires for them. Mirror
        // rejectWithMatrix()'s convention: any non-direct-manager step is the
        // finance approval, so record the finance approver alongside the step.
        if (($currentStep['key'] ?? '') === 'direct_manager') {
            $payload += [
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ];
        } else {
            $payload += [
                'finance_approved_by' => $actor->id,
                'finance_approved_at' => now(),
            ];
        }

        if ($nextStep === null) {
            $payload += [
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ];
        }

        $advance->update($payload);
        $this->notifyStatusUpdated($advance);

        return $nextStep === null
            ? __('Kasbon approved.')
            : __('Kasbon forwarded to the next approval step.');
    }

    private function rejectWithMatrix(CashAdvance $advance, User $actor): ?string
    {
        $advance->loadMissing('user');
        $steps = $this->approvalMatrix->storedOrResolvedSteps(ApprovalMatrixRule::MODULE_CASH_ADVANCE, $advance);

        if ($steps === []) {
            return null;
        }

        $this->ensureReviewable($advance, ['pending', 'pending_finance', 'pending_matrix']);

        $currentStep = $this->approvalMatrix->currentStep($steps, $this->approvalMatrix->completedSteps($advance));

        if (! $this->approvalMatrix->canActorApproveStep($actor, $advance, $currentStep)) {
            throw new AuthorizationException;
        }

        $payload = [
            'status' => 'rejected',
            'approval_steps' => $steps,
            'approval_matrix_rule_id' => $advance->approval_matrix_rule_id
                ?: $this->approvalMatrix->ruleId(ApprovalMatrixRule::MODULE_CASH_ADVANCE, $advance),
            'approval_current_step' => (string) ($currentStep['key'] ?? ''),
        ];

        if (($currentStep['key'] ?? '') === 'direct_manager') {
            $payload += [
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ];
        } else {
            $payload += [
                'finance_approved_by' => $actor->id,
                'finance_approved_at' => now(),
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ];
        }

        $advance->update($payload);
        $this->notifyStatusUpdated($advance);

        return __('Kasbon rejected.');
    }

    private function matrixPayload(CashAdvance $advance, array $steps, array $completed, ?array $nextStep): array
    {
        return [
            'status' => $nextStep === null ? 'approved' : $this->approvalMatrix->statusForStep($nextStep),
            'approval_matrix_rule_id' => $advance->approval_matrix_rule_id
                ?: $this->approvalMatrix->ruleId(ApprovalMatrixRule::MODULE_CASH_ADVANCE, $advance),
            'approval_steps' => $steps,
            'approval_current_step' => $nextStep['key'] ?? null,
            'approval_completed_steps' => $completed,
        ];
    }

    private function lock(CashAdvance $advance): CashAdvance
    {
        return CashAdvance::query()
            ->whereKey($advance->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  list<string>  $allowedStatuses
     */
    private function ensureReviewable(CashAdvance $advance, array $allowedStatuses): void
    {
        if (! in_array((string) $advance->status, $allowedStatuses, true)) {
            throw new AuthorizationException(__('This kasbon has already been reviewed.'));
        }
    }
}
