<?php

namespace App\Notifications;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payroll $payroll,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payroll Periode '.$this->payroll->period.' Ditolak')
            ->line('Payroll untuk periode '.$this->payroll->period.' telah ditolak.')
            ->line('Alasan: '.$this->reason)
            ->action('Lihat Payroll', url('/admin/payrolls'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payroll_id' => $this->payroll->id,
            'period' => $this->payroll->period,
            'reason' => $this->reason,
            'message' => 'Payroll periode '.$this->payroll->period.' ditolak: '.$this->reason,
        ];
    }
}
