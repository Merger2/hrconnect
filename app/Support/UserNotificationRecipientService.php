<?php

namespace App\Support;

use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AssetReturnOtpRequested;
use App\Notifications\AssetReturnOtpRequestedEmail;
use App\Notifications\CashAdvanceRequested;
use App\Notifications\CashAdvanceRequestedEmail;
use App\Notifications\OvertimeRequested;
use App\Notifications\OvertimeRequestedEmail;
use App\Notifications\ReimbursementRequested;
use App\Notifications\ReimbursementRequestedMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class UserNotificationRecipientService
{
    public const EVENT_REIMBURSEMENT_REQUESTED = 'reimbursement_requested';

    public const EVENT_OVERTIME_REQUESTED = 'overtime_requested';

    public const EVENT_ASSET_RETURN_OTP = 'asset_return_otp';

    public const EVENT_CASH_ADVANCE_REQUESTED = 'cash_advance_requested';

    public function __construct(
        protected ApprovalActorService $approvalActors,
        protected NotificationPreferenceService $preferences,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function leaveApprovers(User $user): Collection
    {
        return $this->reviewersWithSupervisor(
            $user,
            fn (User $reviewer): bool => $reviewer->can('manageLeaveApprovals'),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function reimbursementApprovers(User $user): Collection
    {
        return $this->reviewersWithSupervisor(
            $user,
            fn (User $reviewer): bool => $this->approvalActors->canFinalizeReimbursementApproval($reviewer),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function overtimeApprovers(User $user): Collection
    {
        return $this->reviewersWithSupervisor(
            $user,
            fn (User $reviewer): bool => $reviewer->can('manageOvertime'),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function assetReturnApprovers(User $user): Collection
    {
        $supervisor = $this->supervisor($user);

        if ($supervisor !== null) {
            return collect([$supervisor]);
        }

        return $this->usersMatching(fn (User $reviewer): bool => $reviewer->can('view_assets'));
    }

    public function notifyReimbursementRequested(Reimbursement $reimbursement): int
    {
        $reimbursement->loadMissing('user.employee.division', 'user.employee.position');
        $recipients = $this->reimbursementApprovers($reimbursement->user);

        if ($recipients->isNotEmpty()) {
            $this->notifyWithPreferences($recipients, new ReimbursementRequested($reimbursement), self::EVENT_REIMBURSEMENT_REQUESTED);
            $this->notifyWithPreferences($recipients, new ReimbursementRequestedMail($reimbursement), self::EVENT_REIMBURSEMENT_REQUESTED, ['mail']);
        }

        $this->notifyConfiguredAdminEmail(new ReimbursementRequestedMail($reimbursement));

        return $recipients->count();
    }

    public function notifyOvertimeRequested(Overtime $overtime): int
    {
        $overtime->loadMissing('user.employee.division', 'user.employee.position');
        $recipients = $this->overtimeApprovers($overtime->user);

        if ($recipients->isNotEmpty()) {
            $this->notifyWithPreferences($recipients, new OvertimeRequested($overtime), self::EVENT_OVERTIME_REQUESTED);
            $this->notifyWithPreferences($recipients, new OvertimeRequestedEmail($overtime), self::EVENT_OVERTIME_REQUESTED, ['mail']);
        }

        $this->notifyConfiguredAdminEmail(new OvertimeRequestedEmail($overtime));

        return $recipients->count();
    }

    public function notifyAssetReturnOtp(User $user, CompanyAsset $asset, string $otp): int
    {
        $user->loadMissing('employee.division', 'employee.position');
        $recipients = $this->assetReturnApprovers($user);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $this->notifyWithPreferences($recipients, new AssetReturnOtpRequested($asset, $user->name, $otp), self::EVENT_ASSET_RETURN_OTP);
        $this->notifyWithPreferences($recipients, new AssetReturnOtpRequestedEmail($asset, $user->name, $otp), self::EVENT_ASSET_RETURN_OTP, ['mail']);

        return $recipients->count();
    }

    public function notifyCashAdvanceRequested(CashAdvance $cashAdvance): int
    {
        $recipients = $this->cashAdvanceReviewers($cashAdvance);

        if ($recipients->isNotEmpty()) {
            $this->notifyWithPreferences($recipients, new CashAdvanceRequested($cashAdvance), self::EVENT_CASH_ADVANCE_REQUESTED);
            $this->notifyWithPreferences($recipients, new CashAdvanceRequestedEmail($cashAdvance), self::EVENT_CASH_ADVANCE_REQUESTED, ['mail']);
        }

        $this->notifyConfiguredAdminEmail(new CashAdvanceRequestedEmail($cashAdvance));

        return $recipients->count();
    }

    /**
     * @return Collection<int, User>
     */
    protected function cashAdvanceReviewers(CashAdvance $cashAdvance): Collection
    {
        $cashAdvance->loadMissing('user.employee.position', 'user.employee.division');

        return $this->reviewersWithSupervisor(
            $cashAdvance->user,
            fn (User $reviewer): bool => $this->approvalActors->canFinalizeCashAdvanceApproval($reviewer),
        );
    }

    /**
     * @param  callable(User): bool  $reviewerFilter
     * @return Collection<int, User>
     */
    protected function reviewersWithSupervisor(User $user, callable $reviewerFilter): Collection
    {
        $recipients = collect();
        $supervisor = $this->supervisor($user);

        if ($supervisor !== null) {
            $recipients->push($supervisor);
        }

        return $recipients
            ->merge($this->usersMatching($reviewerFilter))
            ->unique('id')
            ->values();
    }

    /**
     * @param  callable(User): bool  $filter
     * @return Collection<int, User>
     */
    protected function usersMatching(callable $filter): Collection
    {
        return User::query()
            ->with(['roles', 'employee.division', 'employee.position'])
            ->get()
            ->reject(fn (User $user): bool => $user->isDemo)
            ->filter(fn (User $user): bool => $filter($user))
            ->values();
    }

    protected function supervisor(User $user): ?User
    {
        $user->loadMissing('employee.division', 'employee.position');

        return $user->supervisor;
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    protected function notifyWithPreferences(Collection $recipients, object $notification, string $eventKey, array $defaultChannels = ['database', 'mail']): void
    {
        /** @var User $recipient */
        foreach ($recipients as $recipient) {
            $channels = $this->preferences->laravelChannelsFor($recipient, $eventKey, $defaultChannels);

            if (empty($channels)) {
                continue;
            }

            Notification::send($recipient, $notification);
        }
    }

    protected function notifyConfiguredAdminEmail(object $notification): void
    {
        $adminEmail = Setting::getValue('notif.admin_email');

        if (! is_string($adminEmail) || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Notification::route('mail', $adminEmail)->notify($notification);
        } catch (\Throwable) {
            // Intentionally ignore mail routing failures for optional admin copies.
        }
    }
}
