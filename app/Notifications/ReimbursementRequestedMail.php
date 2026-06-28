<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimbursementRequestedMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reimbursement $reimbursement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employeeName = $this->reimbursement->employee?->full_name ?? 'Karyawan';
        $amount = number_format((float) $this->reimbursement->amount, 0, ',', '.');
        $date = $this->reimbursement->expense_date?->translatedFormat('d M Y') ?? '-';

        return (new MailMessage)
            ->subject('Pengajuan Reimbursement Baru - '.$employeeName)
            ->greeting('Halo,')
            ->line('Sebuah pengajuan reimbursement baru telah diajukan oleh '.$employeeName.'.')
            ->line('Judul: '.$this->reimbursement->title)
            ->line('Jumlah: Rp '.$amount)
            ->line('Tanggal: '.$date)
            ->line('Kategori: '.($this->reimbursement->category?->name ?? '-'))
            ->line('Deskripsi: '.($this->reimbursement->description ?? '-'))
            ->action('Review Pengajuan', url('/api/v1/reimbursement/'.$this->reimbursement->id))
            ->line('Silakan review pengajuan ini.');
    }
}
