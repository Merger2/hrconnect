<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Leave $leave,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cuti Anda Telah Disetujui')
            ->line('Pengajuan cuti Anda telah disetujui oleh atasan.')
            ->line('Jenis cuti: '.$this->leave->leaveType->name)
            ->line('Periode: '.$this->leave->start_date->format('d M Y').' — '.$this->leave->end_date->format('d M Y'))
            ->line('Durasi: '.$this->leave->total_days.' hari')
            ->action('Lihat Payslip', url('/leaves/'.$this->leave->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'leave_type' => $this->leave->leaveType->name,
            'start_date' => $this->leave->start_date->format('Y-m-d'),
            'end_date' => $this->leave->end_date->format('Y-m-d'),
            'message' => 'Cuti Anda telah disetujui.',
        ];
    }
}
