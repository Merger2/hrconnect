<?php

namespace App\Support;

use App\Models\ApprovalMatrixRule;
use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\ReimbursementStatusUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReimbursementApprovalService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
        protected ApprovalMatrixService $approvalMatrix,
    ) {}

    public function approve(Reimbursement $reimbursement, User $actor): string
    {
        return DB::transaction(function () use ($reimbursement, $actor): string {
            $reimbursement = $this->lock($reimbursement);
            $matrixMessage = $this->approveWithMatrix($reimbursement, $actor);

            if ($matrixMessage !== null) {
                return $matrixMessage;
            }

            $canFinalize = $this->approvalActors->canFinalizeReimbursementApproval($actor);
            $this->ensureReviewable($reimbursement, $canFinalize ? ['pending', 'pending_finance'] : ['pending']);

            if ($canFinalize) {
                $reimbursement->update([
                    'status' => 'approved',
                    'finance_approved_by' => $actor->id,
                    'finance_approved_at' => now(),
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ]);

                $this->notifyStatusUpdated($reimbursement);

                return __('Reimbursement approved.');
            }

            $reimbursement->update([
                'status' => 'pending_finance',
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ]);

            $this->notifyStatusUpdated($reimbursement);

            return __('Reimbursement forwarded to Finance for final approval.');
        });
    }

    public function reject(Reimbursement $reimbursement, User $actor): string
    {
        return DB::transaction(function () use ($reimbursement, $actor): string {
            $reimbursement = $this->lock($reimbursement);
            $matrixMessage = $this->rejectWithMatrix($reimbursement, $actor);

            if ($matrixMessage !== null) {
                return $matrixMessage;
            }

            $canFinalize = $this->approvalActors->canFinalizeReimbursementApproval($actor);
            $this->ensureReviewable($reimbursement, $canFinalize ? ['pending', 'pending_finance'] : ['pending']);

            $payload = [
                'status' => 'rejected',
            ];

            if ($canFinalize) {
                $payload += [
                    'finance_approved_by' => $actor->id,
                    'finance_approved_at' => now(),
                    'approved_by' => $actor->id,
                ];
            } else {
                $payload += [
                    'head_approved_by' => $actor->id,
                    'head_approved_at' => now(),
                ];
            }

            $reimbursement->update($payload);
            $this->notifyStatusUpdated($reimbursement);

            return __('Reimbursement rejected.');
        });
    }

    public function managementQuery(User $actor, string $statusFilter = 'pending', string $search = ''): Builder
    {
        $subordinateIds = $this->approvalActors->subordinateIds($actor);
        $canFinalize = $this->approvalActors->canFinalizeReimbursementApproval($actor);

        return Reimbursement::query()
            ->with(['user', 'approvedBy', 'headApprover', 'financeApprover'])
            ->when(! $actor->can('view_reimbursements'), function (Builder $query) use ($canFinalize, $subordinateIds) {
                if ($canFinalize) {
                    return $query->where(function (Builder $nested) use ($subordinateIds) {
                        $nested->where('status', 'pending_finance')
                            ->orWhere('status', 'pending_matrix')
                            ->orWhereHas('user', fn (Builder $q) => $q->whereIn('id', $subordinateIds));
                    });
                }

                return $query->where(function (Builder $nested) use ($subordinateIds) {
                    $nested->whereHas('user', fn (Builder $q) => $q->whereIn('id', $subordinateIds))
                        ->orWhere('status', 'pending_matrix');
                });
            })
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
            })
            ->latest();
    }

    protected function notifyStatusUpdated(Reimbursement $reimbursement): void
    {
        $reimbursement->loadMissing('user');
        $reimbursement->user?->notify(new ReimbursementStatusUpdated($reimbursement));
    }

    private function approveWithMatrix(Reimbursement $reimbursement, User $actor): ?string
    {
        $reimbursement->loadMissing('user');
        $steps = $this->approvalMatrix->storedOrResolvedSteps(ApprovalMatrixRule::MODULE_REIMBURSEMENT, $reimbursement);

        if ($steps === []) {
            return null;
        }

        $this->ensureReviewable($reimbursement, ['pending', 'pending_finance', 'pending_matrix']);

        $completed = $this->approvalMatrix->completedSteps($reimbursement);
        $currentStep = $this->approvalMatrix->currentStep($steps, $completed);

        if (! $this->approvalMatrix->canActorApproveStep($actor, $reimbursement, $currentStep)) {
            throw new AuthorizationException;
        }

        $completed[] = [
            'key' => (string) ($currentStep['key'] ?? ''),
            'label' => (string) ($currentStep['label'] ?? $currentStep['key'] ?? ''),
            'actor_id' => $actor->id,
            'approved_at' => now()->toIso8601String(),
        ];

        $nextStep = $this->approvalMatrix->currentStep($steps, $completed);
        $payload = $this->matrixPayload($reimbursement, $steps, $completed, $nextStep);

        if (($currentStep['key'] ?? '') === 'direct_manager') {
            $payload += [
                'head_approved_by' => $actor->id,
                'head_approved_at' => now(),
            ];
        }

        if (in_array(($currentStep['key'] ?? ''), ['finance', 'finance_head'], true)) {
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

        $reimbursement->update($payload);
        $this->notifyStatusUpdated($reimbursement);

        return $nextStep === null
            ? __('Reimbursement approved.')
            : __('Reimbursement forwarded to the next approval step.');
    }

    private function rejectWithMatrix(Reimbursement $reimbursement, User $actor): ?string
    {
        $reimbursement->loadMissing('user');
        $steps = $this->approvalMatrix->storedOrResolvedSteps(ApprovalMatrixRule::MODULE_REIMBURSEMENT, $reimbursement);

        if ($steps === []) {
            return null;
        }

        $this->ensureReviewable($reimbursement, ['pending', 'pending_finance', 'pending_matrix']);

        $currentStep = $this->approvalMatrix->currentStep($steps, $this->approvalMatrix->completedSteps($reimbursement));

        if (! $this->approvalMatrix->canActorApproveStep($actor, $reimbursement, $currentStep)) {
            throw new AuthorizationException;
        }

        $payload = [
            'status' => 'rejected',
            'approval_steps' => $steps,
            'approval_matrix_rule_id' => $reimbursement->approval_matrix_rule_id
                ?: $this->approvalMatrix->ruleId(ApprovalMatrixRule::MODULE_REIMBURSEMENT, $reimbursement),
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
            ];
        }

        $reimbursement->update($payload);
        $this->notifyStatusUpdated($reimbursement);

        return __('Reimbursement rejected.');
    }

    private function matrixPayload(Reimbursement $reimbursement, array $steps, array $completed, ?array $nextStep): array
    {
        return [
            'status' => $nextStep === null ? 'approved' : $this->approvalMatrix->statusForStep($nextStep),
            'approval_matrix_rule_id' => $reimbursement->approval_matrix_rule_id
                ?: $this->approvalMatrix->ruleId(ApprovalMatrixRule::MODULE_REIMBURSEMENT, $reimbursement),
            'approval_steps' => $steps,
            'approval_current_step' => $nextStep['key'] ?? null,
            'approval_completed_steps' => $completed,
        ];
    }

    private function lock(Reimbursement $reimbursement): Reimbursement
    {
        return Reimbursement::query()
            ->whereKey($reimbursement->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  list<string>  $allowedStatuses
     */
    private function ensureReviewable(Reimbursement $reimbursement, array $allowedStatuses): void
    {
        $status = $reimbursement->status?->value ?? $reimbursement->status;

        if (! in_array($status, $allowedStatuses, true)) {
            throw new AuthorizationException(__('This reimbursement has already been reviewed.'));
        }
    }
}
