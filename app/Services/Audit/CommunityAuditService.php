<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Contracts\AuditServiceInterface;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

/**
 * Community Edition Audit Service — lightweight stub.
 * Records audit entries to the activity_log table with minimal processing.
 *
 * In the Enterprise edition this would include integrity verification,
 * blockchain anchoring, and read-replica query routing.
 */
class CommunityAuditService implements AuditServiceInterface
{
    public function record(string $action, ?string $description = null)
    {
        try {
            $user = auth()->user();

            return ActivityLog::query()->create([
                'user_id' => $user?->getAuthIdentifier(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('CommunityAuditService::record failed', [
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getTrail(array $filters = []): array
    {
        $query = ActivityLog::query()
            ->with('user')
            ->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($filters['per_page'] ?? 50)->items();
    }
}
