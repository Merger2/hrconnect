<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Overtime $overtime,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = __(ucfirst((string) $this->overtime->status));

        return (new MailMessage)
            ->subject(__('Overtime Request: :status', ['status' => $statusLabel]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your overtime request status has been updated.'))
            ->line(__('Date: :date', ['date' => $this->overtime->date->format('d M Y') ?? '-']))
            ->line(__('Status: **:status**', ['status' => $statusLabel]))
            ->action(__('View Details'), url('/overtime'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overtime_status_updated',
            'overtime_id' => $this->overtime->id,
            'status' => $this->overtime->status,
            'message' => __('Overtime request status updated to :status.', [
                'status' => __(ucfirst((string) $this->overtime->status)),
            ]),
        ];
    }
}
