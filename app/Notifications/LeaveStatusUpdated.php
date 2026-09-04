<?php

namespace App\Notifications;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Attendance|Leave $leaveRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->statusLabel();

        return (new MailMessage)
            ->subject(__('Leave Request: :status', ['status' => $statusLabel]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your leave request status has been updated.'))
            ->line(__('Status: **:status**', ['status' => $statusLabel]))
            ->line(__('Date: :date', ['date' => $this->dateLabel()]))
            ->action(__('View Details'), route('apply-leave'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_status_updated',
            'attendance_id' => $this->leaveRequest instanceof Attendance ? $this->leaveRequest->id : null,
            'leave_id' => $this->leaveRequest instanceof Leave ? $this->leaveRequest->id : null,
            'status' => $this->statusValue(),
            'message' => __('Leave request status updated to :status.', [
                'status' => $this->statusLabel(),
            ]),
        ];
    }

    private function statusLabel(): string
    {
        if ($this->leaveRequest instanceof Leave) {
            return $this->leaveRequest->status?->label() ?? __('Updated');
        }

        return match ($this->leaveRequest->approval_status) {
            ApprovalStatus::APPROVED => __('Approved'),
            ApprovalStatus::REJECTED => __('Rejected'),
            default => $this->leaveRequest->approval_status?->label() ?? __('Updated'),
        };
    }

    private function statusValue(): ?string
    {
        return $this->leaveRequest instanceof Leave
            ? $this->leaveRequest->status?->value
            : $this->leaveRequest->approval_status?->value;
    }

    private function dateLabel(): string
    {
        if ($this->leaveRequest instanceof Leave) {
            return $this->leaveRequest->start_date->format('d M Y').' - '.$this->leaveRequest->end_date->format('d M Y');
        }

        return $this->leaveRequest->date?->format('d M Y') ?? '-';
    }
}
