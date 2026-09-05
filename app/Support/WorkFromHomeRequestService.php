<?php

namespace App\Support;

use App\Enums\RequestStatus;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Notifications\WfhRequestStatusUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkFromHomeRequestService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(User $user, array $payload): WorkFromHomeRequest
    {
        Gate::forUser($user)->authorize('create', WorkFromHomeRequest::class);

        return WorkFromHomeRequest::query()->create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'start_date' => $payload['start_date'] ?? $payload['date'],
            'end_date' => $payload['end_date'] ?? $payload['date'],
            'date' => $payload['date'],
            'start_time' => $payload['start_time'] ?? null,
            'end_time' => $payload['end_time'] ?? null,
            'location_address' => $payload['location_address'] ?? null,
            'reason' => $payload['reason'],
            'status' => WorkFromHomeRequest::STATUS_PENDING,
            'metadata' => $payload['metadata'] ?? null,
        ]);
    }

    public function approve(WorkFromHomeRequest $request, User $actor, ?string $note = null): string
    {
        Gate::forUser($actor)->authorize('approve', $request);

        DB::transaction(function () use ($request, $actor, $note): void {
            $request = $this->lock($request);
            $this->ensurePending($request);

            $request->forceFill([
                'status' => RequestStatus::APPROVED_L1,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();
        });

        $request->user?->notify(new WfhRequestStatusUpdated($request));

        return __('WFH request approved.');
    }

    public function finalize(WorkFromHomeRequest $request, User $actor, ?string $note = null): string
    {
        Gate::forUser($actor)->authorize('finalize', $request);

        DB::transaction(function () use ($request, $actor, $note): void {
            $request = $this->lock($request);
            $this->ensureApprovedL1($request);

            $request->forceFill([
                'status' => RequestStatus::APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'final_note' => $note,
            ])->save();
        });

        $request->user?->notify(new WfhRequestStatusUpdated($request));

        return __('WFH request approved.');
    }

    public function reject(WorkFromHomeRequest $request, User $actor, ?string $note = null): string
    {
        Gate::forUser($actor)->authorize('reject', $request);

        DB::transaction(function () use ($request, $actor, $note): void {
            $request = $this->lock($request);
            $this->ensurePending($request);

            $request->forceFill([
                'status' => RequestStatus::REJECTED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();
        });

        $request->user?->notify(new WfhRequestStatusUpdated($request));

        return __('WFH request rejected.');
    }

    public function queryForUser(User $user): Builder
    {
        return WorkFromHomeRequest::query()
            ->with('reviewer:id,name')
            ->where('user_id', $user->id)
            ->latest('date')
            ->latest();
    }

    private function lock(WorkFromHomeRequest $request): WorkFromHomeRequest
    {
        return WorkFromHomeRequest::query()
            ->whereKey($request->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensurePending(WorkFromHomeRequest $request): void
    {
        if ($request->status !== WorkFromHomeRequest::STATUS_PENDING) {
            throw new AuthorizationException(__('This WFH request has already been reviewed.'));
        }
    }

    private function ensureApprovedL1(WorkFromHomeRequest $request): void
    {
        if ($request->status !== WorkFromHomeRequest::STATUS_APPROVED_L1) {
            throw new AuthorizationException(__('This WFH request is not waiting for final approval.'));
        }
    }
}
