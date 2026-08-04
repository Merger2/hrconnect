<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistTask;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Illuminate\Support\Collection;

class UserHomeCommandCenterService
{
    public function __construct(
        private readonly ApprovalActorService $approvalActors,
    ) {}

    /**
     * @return array{
     *     attentionCount:int,
     *     actionItems:array<int, array{label:string, description:string, href:string, icon:string, tone:string, count:int|null}>,
     *     teamItems:array<int, array{label:string, description:string, href:string, icon:string, tone:string, count:int}>,
     *     recentActivities:array<int, array{label:string, description:string, href:string, status:string, tone:string}>
     * }
     */
    public function forUser(User $user): array
    {
        $actionItems = $this->actionItems($user);
        $teamItems = $this->teamItems($user);

        return [
            'attentionCount' => collect($actionItems)
                ->merge($teamItems)
                ->sum(fn (array $item): int => (int) ($item['count'] ?? 1)),
            'actionItems' => $actionItems,
            'teamItems' => $teamItems,
            'recentActivities' => $this->recentActivities($user),
        ];
    }

    /**
     * @return array<int, array{label:string, description:string, href:string, icon:string, tone:string, count:int|null}>
     */
    private function actionItems(User $user): array
    {
        $items = [];

        $announcementCount = Announcement::visibleForUser($user->id)->count();
        $unreadNotificationCount = $user->unreadNotifications()->count();

        if ($unreadNotificationCount > 0) {
            $items[] = [
                'label' => __('Notifications'),
                'description' => __('Unread updates and approval results.'),
                'href' => route('notifications'),
                'icon' => 'bell',
                'tone' => 'primary',
                'count' => $unreadNotificationCount,
            ];
        }

        if ($announcementCount > 0) {
            $items[] = [
                'label' => __('Announcements'),
                'description' => __('Important policies that need your attention.'),
                'href' => route('notifications'),
                'icon' => 'document',
                'tone' => 'warning',
                'count' => $announcementCount,
            ];
        }

        if (! $user->hasFaceRegistered()) {
            $items[] = [
                'label' => __('Face ID'),
                'description' => __('Face ID Registration Required'),
                'href' => route('face.enrollment'),
                'icon' => 'face',
                'tone' => 'warning',
                'count' => null,
            ];
        }

        $hrTaskCount = HrChecklistTask::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', [HrChecklistTask::STATUS_PENDING, HrChecklistTask::STATUS_BLOCKED])
            ->count();

        if ($hrTaskCount > 0) {
            $items[] = [
                'label' => __('HR Tasks'),
                'description' => __('Complete onboarding and offboarding follow-ups.'),
                'href' => route('hr-tasks'),
                'icon' => 'clipboard',
                'tone' => 'info',
                'count' => $hrTaskCount,
            ];
        }

        if (! $user->hasValidPayslipPassword()) {
            $paidPayrollExists = Payroll::query()
                ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', 'paid')
                ->exists();

            if ($paidPayrollExists) {
                $items[] = [
                    'label' => __('Payslip'),
                    'description' => __('Create a password used to open encrypted payslip PDF files.'),
                    'href' => route('my-payslips'),
                    'icon' => 'document',
                    'tone' => 'warning',
                    'count' => null,
                ];
            }
        }

        array_push($items, ...$this->ownRequestItems($user));

        return $items;
    }

    /**
     * @return array<int, array{label:string, description:string, href:string, icon:string, tone:string, count:int}>
     */
    private function teamItems(User $user): array
    {
        $subordinateIds = $this->approvalActors->subordinateIds($user);

        if ($subordinateIds->isEmpty()) {
            return [];
        }

        $leaveCount = Attendance::query()
            ->whereHas('employee', fn ($q) => $q->whereIn('user_id', $subordinateIds))
            ->where('approval_status', Attendance::STATUS_PENDING)
            ->where('status', '!=', 'present')
            ->count();
        $correctionCount = AttendanceCorrection::query()
            ->whereIn('user_id', $subordinateIds)
            ->where('status', AttendanceCorrection::STATUS_PENDING)
            ->count();
        $shiftSwapCount = ShiftSwapRequest::query()
            ->whereIn('user_id', $subordinateIds)
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->count();
        $reimbursementCount = Reimbursement::query()
            ->whereHas('employee', fn ($q) => $q->whereIn('user_id', $subordinateIds))
            ->where('status', 'pending')
            ->count();
        $overtimeCount = Overtime::query()
            ->whereHas('employee', fn ($q) => $q->whereIn('user_id', $subordinateIds))
            ->where('status', 'pending')
            ->count();
        $wfhCount = WorkFromHomeRequest::query()
            ->whereIn('user_id', $subordinateIds)
            ->where('status', WorkFromHomeRequest::STATUS_PENDING)
            ->count();

        $cashAdvanceCount = CashAdvance::query()
            ->whereIn('user_id', $subordinateIds)
            ->where('status', 'pending')
            ->count();

        return collect([
            [
                'label' => __('Team Approvals'),
                'description' => __('Leave, reimbursement, overtime, WFH, and shift requests.'),
                'href' => route('approvals'),
                'icon' => 'check',
                'tone' => 'info',
                'count' => $leaveCount + $reimbursementCount + $overtimeCount + $wfhCount + $shiftSwapCount,
            ],
            [
                'label' => __('Team Attendance'),
                'description' => __('Attendance corrections waiting for review.'),
                'href' => route('approvals', ['activeTab' => 'attendance-corrections']),
                'icon' => 'calendar',
                'tone' => 'primary',
                'count' => $correctionCount,
            ],
            [
                'label' => __('Team Kasbon'),
                'description' => __('Team cash advance requests waiting for review.'),
                'href' => route('team-kasbon'),
                'icon' => 'cash',
                'tone' => 'warning',
                'count' => $cashAdvanceCount,
            ],
        ])->filter(fn (array $item): bool => $item['count'] > 0)->values()->all();
    }

    /**
     * @return array<int, array{label:string, description:string, href:string, icon:string, tone:string, count:int|null}>
     */
    private function ownRequestItems(User $user): array
    {
        $items = [];

        $reimbursementCount = Reimbursement::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['pending', 'pending_finance'])
            ->count();

        if ($reimbursementCount > 0) {
            $items[] = [
                'label' => __('Claim'),
                'description' => __('Your reimbursement requests are still being reviewed.'),
                'href' => route('reimbursement'),
                'icon' => 'cash',
                'tone' => 'muted',
                'count' => $reimbursementCount,
            ];
        }

        $overtimeCount = Overtime::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', 'pending')
            ->count();

        if ($overtimeCount > 0) {
            $items[] = [
                'label' => __('Overtime'),
                'description' => __('Your overtime requests are waiting for approval.'),
                'href' => route('overtime'),
                'icon' => 'clock',
                'tone' => 'muted',
                'count' => $overtimeCount,
            ];
        }

        $wfhCount = WorkFromHomeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', WorkFromHomeRequest::STATUS_PENDING)
            ->count();

        if ($wfhCount > 0) {
            $items[] = [
                'label' => __('WFH'),
                'description' => __('Your work-from-home requests are waiting for review.'),
                'href' => route('wfh-requests'),
                'icon' => 'home',
                'tone' => 'muted',
                'count' => $wfhCount,
            ];
        }

        $cashAdvanceCount = CashAdvance::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'pending_finance', 'approved'])
            ->count();

        if ($cashAdvanceCount > 0) {
            $items[] = [
                'label' => __('Kasbon'),
                'description' => __('Your cash advance request still needs follow-up.'),
                'href' => route('my-kasbon'),
                'icon' => 'cash',
                'tone' => 'muted',
                'count' => $cashAdvanceCount,
            ];
        }

        $correctionCount = AttendanceCorrection::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [AttendanceCorrection::STATUS_PENDING, AttendanceCorrection::STATUS_PENDING_ADMIN])
            ->count();

        if ($correctionCount > 0) {
            $items[] = [
                'label' => __('Correction'),
                'description' => __('Your attendance corrections are waiting for review.'),
                'href' => route('attendance-corrections'),
                'icon' => 'calendar',
                'tone' => 'muted',
                'count' => $correctionCount,
            ];
        }

        $shiftSwapCount = ShiftSwapRequest::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->count();

        if ($shiftSwapCount > 0) {
            $items[] = [
                'label' => __('Shift Swap'),
                'description' => __('Your shift swap requests are waiting for review.'),
                'href' => route('shift-swap-requests'),
                'icon' => 'swap',
                'tone' => 'muted',
                'count' => $shiftSwapCount,
            ];
        }

        $documentCount = EmployeeDocumentRequest::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [
                EmployeeDocumentRequest::STATUS_PENDING,
                EmployeeDocumentRequest::STATUS_REQUESTED,
                EmployeeDocumentRequest::STATUS_UPLOADED,
            ])
            ->count();

        if ($documentCount > 0) {
            $items[] = [
                'label' => __('Documents'),
                'description' => __('Your document requests still need action.'),
                'href' => route('document-requests'),
                'icon' => 'document',
                'tone' => 'muted',
                'count' => $documentCount,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{label:string, description:string, href:string, status:string, tone:string}>
     */
    private function recentActivities(User $user): array
    {
        return collect()
            ->merge($this->latestReimbursements($user))
            ->merge($this->latestOvertimes($user))
            ->merge($this->latestCashAdvances($user))
            ->merge($this->latestWfhRequests($user))
            ->merge($this->latestAttendanceCorrections($user))
            ->merge($this->latestDocumentRequests($user))
            ->sortByDesc('sort_at')
            ->take(4)
            ->map(fn (array $item): array => collect($item)->except('sort_at')->all())
            ->values()
            ->all();
    }

    private function latestReimbursements(User $user): Collection
    {
        return Reimbursement::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')
            ->take(2)
            ->get()
            ->map(fn (Reimbursement $item): array => [
                'label' => __('Claim'),
                'description' => __(ucfirst((string) $item->type)).' · '.number_format((float) $item->amount, 0, ',', '.'),
                'href' => route('reimbursement'),
                'status' => __(str($this->statusValue($item->status))->headline()->toString()),
                'tone' => $this->statusTone($this->statusValue($item->status)),
                'sort_at' => $item->updated_at ?? $item->created_at,
            ]);
    }

    private function latestOvertimes(User $user): Collection
    {
        return Overtime::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')
            ->take(2)
            ->get()
            ->map(fn (Overtime $item): array => [
                'label' => __('Overtime'),
                'description' => $item->date?->translatedFormat('d M Y') ?? __('No date'),
                'href' => route('overtime'),
                'status' => __(str($this->statusValue($item->status))->headline()->toString()),
                'tone' => $this->statusTone($this->statusValue($item->status)),
                'sort_at' => $item->updated_at ?? $item->created_at,
            ]);
    }

    private function latestCashAdvances(User $user): Collection
    {
        return CashAdvance::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->take(2)
            ->get()
            ->map(fn (CashAdvance $item): array => [
                'label' => __('Kasbon'),
                'description' => 'Rp '.number_format((float) $item->amount, 0, ',', '.'),
                'href' => route('my-kasbon'),
                'status' => __(str($this->statusValue($item->status))->headline()->toString()),
                'tone' => $this->statusTone($this->statusValue($item->status)),
                'sort_at' => $item->updated_at ?? $item->created_at,
            ]);
    }

    private function latestWfhRequests(User $user): Collection
    {
        return WorkFromHomeRequest::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->take(2)
            ->get()
            ->map(fn (WorkFromHomeRequest $item): array => [
                'label' => __('WFH'),
                'description' => $item->date?->translatedFormat('d M Y') ?? __('No date'),
                'href' => route('wfh-requests'),
                'status' => __(str($this->statusValue($item->status))->headline()->toString()),
                'tone' => $this->statusTone($this->statusValue($item->status)),
                'sort_at' => $item->updated_at ?? $item->created_at,
            ]);
    }

    private function latestAttendanceCorrections(User $user): Collection
    {
        return AttendanceCorrection::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->take(2)
            ->get()
            ->map(fn (AttendanceCorrection $item): array => [
                'label' => __('Correction'),
                'description' => $item->attendance_date?->translatedFormat('d M Y') ?? __('No date'),
                'href' => route('attendance-corrections'),
                'status' => $item->statusLabel(),
                'tone' => $this->statusTone($this->statusValue($item->status)),
                'sort_at' => $item->updated_at ?? $item->created_at,
            ]);
    }

    private function latestDocumentRequests(User $user): Collection
    {
        return EmployeeDocumentRequest::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->latest('updated_at')
            ->take(2)
            ->get()
            ->map(fn (EmployeeDocumentRequest $item): array => [
                'label' => __('Documents'),
                'description' => $item->documentTypeLabel(),
                'href' => route('document-requests'),
                'status' => $item->statusLabel(),
                'tone' => $this->statusTone($this->statusValue($item->status)),
                'sort_at' => $item->updated_at ?? $item->created_at,
            ]);
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'approved', 'paid', 'ready', 'generated', 'uploaded', 'done' => 'success',
            'rejected', 'blocked', 'expired' => 'danger',
            'pending', 'pending_admin', 'pending_finance', 'requested', 'upload_processing' => 'warning',
            default => 'muted',
        };
    }
}
