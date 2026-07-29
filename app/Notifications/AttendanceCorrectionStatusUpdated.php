<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AttendanceCorrection $correction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->correction->statusLabel();

        return (new MailMessage)
            ->subject(__('Attendance Correction: :status', ['status' => $statusLabel]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your attendance correction request status has been updated.'))
            ->line(__('Date: :date', ['date' => $this->correction->attendance_date?->format('d M Y') ?? '-']))
            ->line(__('Status: **:status**', ['status' => $statusLabel]))
            ->action(__('View Details'), url('/attendance-corrections'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'attendance_correction_status_updated',
            'attendance_correction_id' => $this->correction->id,
            'status' => $this->correction->status,
            'message' => $this->correction->statusLabel(),
        ];
    }
}
