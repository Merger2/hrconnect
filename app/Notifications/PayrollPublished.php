<?php

namespace App\Notifications;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollPublished extends Notification implements ShouldQueue
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
        $employee = $this->payroll->employee;

        return (new MailMessage)
            ->subject('Slip Gaji Bulan '.$this->payroll->period)
            ->line('Halo '.$employee->full_name.',')
            ->line('Slip gaji Anda untuk periode '.$this->payroll->period.' telah diterbitkan.')
            ->line('Gaji bersih: Rp '.number_format($this->payroll->net_salary, 0, ',', '.'))
            ->action('Lihat Slip Gaji', url('/payroll/'.$this->payroll->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payroll_id' => $this->payroll->id,
            'period' => $this->payroll->period,
            'net_salary' => $this->payroll->net_salary,
            'message' => 'Slip gaji periode '.$this->payroll->period.' telah diterbitkan.',
        ];
    }
}
