<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification implements ShouldQueue
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
        $employee = $this->leave->employee;

        return (new MailMessage)
            ->subject('Pengajuan Cuti Baru — '.$employee->full_name)
            ->line('Karyawan '.$employee->full_name.' telah mengajukan cuti.')
            ->line('Jenis cuti: '.$this->leave->leaveType->name)
            ->line('Periode: '.$this->leave->start_date->format('d M Y').' — '.$this->leave->end_date->format('d M Y'))
            ->line('Durasi: '.$this->leave->total_days.' hari')
            ->action('Lihat Detail', url('/leaves/'.$this->leave->id))
            ->line('Mohon melakukan persetujuan jika Anda adalah approver yang ditunjuk.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'employee_name' => $this->leave->employee->full_name,
            'leave_type' => $this->leave->leaveType->name,
            'start_date' => $this->leave->start_date->format('Y-m-d'),
            'end_date' => $this->leave->end_date->format('Y-m-d'),
            'message' => 'Pengajuan cuti baru dari '.$this->leave->employee->full_name,
        ];
    }
}
