<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class LeaveRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Attendance $attendance,
        public Carbon $fromDate,
        public Carbon $toDate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengajuan Cuti Baru')
            ->line('Seorang karyawan mengajukan cuti.')
            ->line('Periode: '.$this->fromDate->format('d M Y').' - '.$this->toDate->format('d M Y'))
            ->action('Lihat Pengajuan', url('/leave-requests'));
    }
}
