<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashAdvanceUpdatedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CashAdvance $advance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->advance->amount, 0, ',', '.');
        $statusLabel = $this->getStatusLabel($this->advance->status);

        return (new MailMessage)
            ->subject('Pengajuan Kasbon: '.$statusLabel)
            ->greeting('Halo '.($notifiable->name ?? '').',')
            ->line('Status pengajuan kasbon Anda telah diperbarui.')
            ->line('Jumlah: Rp '.$amount)
            ->line('Tujuan: '.$this->advance->purpose)
            ->line('Status: **'.$statusLabel.'**');
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Sedang Diproses',
            'pending_finance' => 'Menunggu Persetujuan Finance',
            'pending_matrix' => 'Menunggu Approval Matrix',
            'approved' => 'Disetujui Final',
            'rejected' => 'Ditolak',
            'paid' => 'Sudah Dibayarkan',
            default => 'Perlu Tinjauan',
        };
    }
}
