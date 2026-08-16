<?php

namespace App\Notifications;

use App\Enums\PayrollStatus;
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
        // U11 AUDIT: 'Total karyawan: 1' dulu hardcode — hitung jumlah payroll
        // yang sudah diajukan untuk periode yang sama (1 baris per karyawan).
        $employeeCount = Payroll::query()
            ->where('period', $this->payroll->period)
            ->where('status', PayrollStatus::SUBMITTED)
            ->count();

        return (new MailMessage)
            ->subject('Pengajuan Payroll Periode '.$this->payroll->period)
            ->line('Payroll periode '.$this->payroll->period.' telah diajukan dan membutuhkan verifikasi.')
            ->line('Total karyawan: '.$employeeCount)
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
