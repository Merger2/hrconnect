<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Overtime $overtime,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->overtime->employee->full_name ?? 'Karyawan';
        $totalHours = $this->overtime->total_hours ?? 0;

        return (new MailMessage)
            ->subject('Pengajuan Lembur Baru - '.$userName)
            ->line('Seorang karyawan mengajukan lembur.')
            ->line('Total jam: '.$totalHours)
            ->action('Lihat Pengajuan', url('/overtime'));
    }
}
