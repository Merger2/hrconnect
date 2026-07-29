<?php

namespace App\Notifications;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payroll $payroll,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengajuan Payroll Periode '.$this->payroll->period)
            ->line('Payroll periode '.$this->payroll->period.' telah diajukan dan membutuhkan verifikasi.')
            ->line('Total karyawan: 1')
            ->action('Lihat Payroll', url('/admin/payrolls'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payroll_id' => $this->payroll->id,
            'period' => $this->payroll->period,
            'message' => 'Payroll periode '.$this->payroll->period.' telah diajukan.',
        ];
    }
}
