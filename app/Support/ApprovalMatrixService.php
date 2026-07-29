<?php

namespace App\Support;

use App\Models\ApprovalMatrixRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApprovalMatrixService
{
    public function __construct(
        private readonly ApprovalActorService $approvalActor,
    ) {}

    public function matchingRule(string $moduleName, Model $subject): ?ApprovalMatrixRule
    {
        return $this->matchingRows($moduleName, $subject)->first();
    }

    /**
     * @return Collection<int, ApprovalMatrixRule>
     */
    private function matchingRows(string $moduleName, Model $subject): Collection
    {
        $rows = ApprovalMatrixRule::query()
            ->where('module_name', $moduleName)
            ->where('is_active', true)
            ->orderBy('approval_level')
            ->orderBy('id')
            ->get();

        $conditionGroups = $rows->groupBy(fn (ApprovalMatrixRule $r) => $r->condition_type.':'.$r->condition_value);

        $requester = $this->requester($subject);
        $amount = $this->amount($subject);

        foreach ($conditionGroups as $group) {
            $representative = $group->first();

            if ($this->matchesCondition($representative, $requester, $amount)) {
                return $group;
            }
        }

        return collect();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function initializeSteps(string $moduleName, Model $subject): array
    {
        $rows = $this->matchingRows($moduleName, $subject);

        return $rows->map(fn (ApprovalMatrixRule $r) => [
            'key' => (string) $r->approval_level,
            'label' => 'Level '.$r->approval_level,
            'approver_id' => $r->approver_id,
            'approver_role_id' => $r->approver_role_id,
        ])->values()->all();
    }

    public function ruleId(string $moduleName, Model $subject): ?int
    {
        return $this->matchingRule($moduleName, $subject)?->id;
    }

    /**
     * @param  list<array<string, mixed>>|null  $steps
     * @param  list<array<string, mixed>>|null  $completed
     */
    public function currentStep(?array $steps, ?array $completed): ?array
    {
        $completedKeys = collect($completed ?? [])->pluck('key')->filter()->all();

        foreach ($steps ?? [] as $step) {
            $key = (string) ($step['key'] ?? '');

            if ($key !== '' && ! in_array($key, $completedKeys, true)) {
                return $step;
            }
        }

        return null;
    }

    public function canActorApproveStep(User $actor, Model $subject, ?array $step): bool
    {
        if ($step === null) {
            return false;
        }

        $approverId = $step['approver_id'] ?? null;
        $approverRoleId = $step['approver_role_id'] ?? null;

        if ($approverId !== null && (int) $approverId === $actor->id) {
            return true;
        }

        if ($approverRoleId !== null && $actor->hasRole($approverRoleId)) {
            return true;
        }

        return false;
    }

    public function canActorApprove(User $actor, string $moduleName, Model $subject): bool
    {
        $steps = $this->storedOrResolvedSteps($moduleName, $subject);

        if ($steps === []) {
            return false;
        }

        return $this->canActorApproveStep(
            $actor,
            $subject,
            $this->currentStep($steps, $this->completedSteps($subject)),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function storedOrResolvedSteps(string $moduleName, Model $subject): array
    {
        $steps = $subject->getAttribute('approval_steps');

        if (is_array($steps) && $steps !== []) {
            return $steps;
        }

        return $this->initializeSteps($moduleName, $subject);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function completedSteps(Model $subject): array
    {
        $completed = $subject->getAttribute('approval_completed_steps');

        return is_array($completed) ? $completed : [];
    }

    public function statusForStep(?array $step): string
    {
        return match ((string) ($step['key'] ?? '')) {
            'finance', 'finance_head' => 'pending_finance',
            default => 'pending_matrix',
        };
    }

    private function matchesCondition(ApprovalMatrixRule $rule, ?User $requester, float $amount): bool
    {
        return match ($rule->condition_type) {
            'min_amount' => $amount >= (float) $rule->condition_value,
            'max_amount' => $amount <= (float) $rule->condition_value,
            'division_id' => $requester?->division_id !== null && (string) $requester->division_id === (string) $rule->condition_value,
            'requester_group' => $requester?->group !== null && (string) $requester->group === (string) $rule->condition_value,
            'requester_role' => $requester !== null && $requester->hasRole((string) $rule->condition_value),
            default => true,
        };
    }

    private function requester(Model $subject): ?User
    {
        if (method_exists($subject, 'user')) {
            $subject->loadMissing('user');

            $user = $subject->getRelation('user');

            return $user instanceof User ? $user : null;
        }

        return null;
    }

    private function amount(Model $subject): float
    {
        foreach (['amount', 'total_amount', 'net_salary', 'purchase_cost', 'duration'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }
}
