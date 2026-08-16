<?php

namespace App\Support;

use App\Enums\ReimbursementStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UserReimbursementService
{
    public function __construct(
        protected UserNotificationRecipientService $notificationRecipients,
    ) {}

    /**
     * @return array{claims: Collection<int, Reimbursement>, total: int}
     */
    public function claimListing(string|int $userId, string $search = '', string $statusFilter = 'all', string $typeFilter = 'all', int $limit = 5): array
    {
        $query = $this->queryForUser($userId, $search, $statusFilter, $typeFilter);
        $total = (clone $query)->count();

        return [
            'claims' => $query->take($limit)->get(),
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClaim(User $user, array $data, ?UploadedFile $attachment = null): Reimbursement
    {
        $employeeId = $user->employee?->id;

        if (! $employeeId) {
            throw new BusinessRuleException('Data karyawan tidak ditemukan.');
        }

        // Map type to category_id if possible
        $categoryId = $this->resolveCategoryId($data['type'] ?? null);

        $claim = Reimbursement::create([
            'employee_id' => $employeeId,
            'expense_date' => $data['date'] ?? now()->toDateString(),
            'category_id' => $categoryId,
            'title' => ucfirst($data['type'] ?? 'other').' '.__('Reimbursement'),
            'amount' => $this->normalizeAmount($data['amount'] ?? 0),
            'description' => $data['description'] ?? '',
            'attachment_path' => $attachment?->store('reimbursements', 'local'),
            'status' => ReimbursementStatus::PENDING,
        ]);

        $claim->loadMissing('employee.user.employee.position', 'employee.user.employee.division');
        $this->notificationRecipients->notifyReimbursementRequested($claim);

        return $claim;
    }

    /**
     * Resolve a category ID from a type string.
     */
    protected function resolveCategoryId(?string $type): ?int
    {
        if (! $type) {
            return null;
        }

        $category = ReimbursementCategory::where('code', $type)
            ->orWhere('name', 'like', $type)
            ->first();

        return $category?->id;
    }

    protected function queryForUser(string|int $userId, string $search, string $statusFilter, string $typeFilter): Builder
    {
        return Reimbursement::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $subQuery) use ($search) {
                    $term = '%'.trim($search).'%';

                    $subQuery->where('description', 'like', $term)
                        ->orWhere('title', 'like', $term);
                });
            })
            ->when($statusFilter !== 'all', fn (Builder $builder) => $builder->where('status', $statusFilter))
            ->when($typeFilter !== 'all', function (Builder $builder) use ($typeFilter) {
                // Filter by category code if type filter is set
                $builder->whereHas('category', fn ($q) => $q->where('code', $typeFilter));
            })
            ->latest('expense_date');
    }

    protected function normalizeAmount(mixed $amount): float
    {
        $normalized = str_replace(['.', ','], '', (string) $amount);

        return (float) $normalized;
    }
}
