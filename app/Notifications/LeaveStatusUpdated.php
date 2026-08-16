<?php

namespace App\Notifications;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Attendance $attendance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = match ($this->attendance->approval_status) {
            ApprovalStatus::APPROVED => __('Approved'),
            ApprovalStatus::REJECTED => __('Rejected'),
            default => $this->attendance->approval_status?->label() ?? __('Updated'),
        };

        return (new MailMessage)
            ->subject(__('Leave Request: :status', ['status' => $statusLabel]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your leave request status has been updated.'))
            ->line(__('Status: **:status**', ['status' => $statusLabel]))
            ->line(__('Date: :date', ['date' => $this->attendance->date->format('d M Y') ?? '-']))
            ->action(__('View Details'), route('apply-leave'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_status_updated',
            'attendance_id' => $this->attendance->id,
            'status' => $this->attendance->approval_status?->value,
            'message' => __('Leave request status updated to :status.', [
                'status' => $this->attendance->approval_status?->label() ?? __('Updated'),
            ]),
        ];
    }
}
