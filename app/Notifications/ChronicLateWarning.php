<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChronicLateWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public int $lateCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Peringatan Keterlambatan Kronis')
            ->line('Halo '.$this->employee->full_name.',')
            ->line('Anda telah terlambat '.$this->lateCount.' kali bulan ini.')
            ->line('Mohon memperhatikan disiplin kehadiran Anda.')
            ->action('Lihat Rekap Absensi', url('/attendances'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->full_name,
            'late_count' => $this->lateCount,
            'message' => 'Peringatan: keterlambatan kronis ('.$this->lateCount.'x bulan ini).',
        ];
    }
}
