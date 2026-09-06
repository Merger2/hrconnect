<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApprovalActorService
{
    /**
     * @return Collection<int, string>
     */
    public function subordinateIds(User $user): Collection
    {
        $explicitReportIds = User::query()
            ->where('manager_id', $user->id)
            ->when($user->company_id !== null, fn (Builder $query) => $query->where('company_id', $user->company_id))
            ->pluck('id');

        // Hierarki organisasi (employees) — konsisten dengan daftar pending yang
        // ditampilkan (Employee::subordinates() via parent_id) dan direct manager
        // eksplisit (employees.manager_id). Sebelumnya hanya users.manager_id yang
        // dibaca → manager TIDAK bisa approve pengajuan karyawan yang relasinya
        // via employees.parent_id/manager_id (seed/hierarki legacy): tombol
        // approve di /approvals gagal diam-diam (silent return di approveLeave).
        $orgReportIds = collect();
        if ($user->employee) {
            $orgReportIds = Employee::query()
                ->where('user_id', '!=', $user->id)
                ->whereNotNull('user_id')
                ->where(function (Builder $query) use ($user): void {
                    $query->where('parent_id', $user->employee->id)
                        ->orWhere('manager_id', $user->employee->id);
                })
                ->when($user->company_id !== null, fn (Builder $query) => $query->where('company_id', $user->company_id))
                ->pluck('user_id');
        }

        $baseIds = $explicitReportIds->merge($orgReportIds)->unique()->values();

        if (! $this->canManageDivisionSubordinates($user) || ! $user->division_id || ! $user->jobTitle?->jobLevel) {
            return $baseIds;
        }

        $rank = (int) $user->jobTitle->jobLevel->rank;

        $divisionReportIds = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('employee', fn (Builder $query) => $query->where('division_id', $user->division_id))
            ->whereNull('manager_id')
            ->when($user->company_id !== null, fn (Builder $query) => $query->where('company_id', $user->company_id))
            ->whereHas('employee.position.jobTitle.jobLevel', fn (Builder $query) => $query->where('rank', '>', $rank))
            ->pluck('id');

        return $baseIds
            ->merge($divisionReportIds)
            ->unique()
            ->values();
    }

    public function hasSubordinates(User $user): bool
    {
        return $this->subordinateIds($user)->isNotEmpty();
    }

    public function canFinalizeReimbursementApproval(User $user): bool
    {
        return $user->allowsAdminPermission('admin.reimbursements.approve')
            || $this->isFinanceHead($user);
    }

    public function canFinalizeCashAdvanceApproval(User $user): bool
    {
        return $user->can('manageCashAdvances')
            || $this->isFinanceHead($user);
    }

    public function isFinanceHead(User $user): bool
    {
        return (int) ($user->jobTitle?->jobLevel->rank ?? 99) <= 2
            && strtolower((string) $user->division?->name) === 'finance';
    }

    public function canManageDivisionSubordinates(User $user): bool
    {
        return (int) ($user->jobTitle?->jobLevel->rank ?? 99) <= 2;
    }
}
