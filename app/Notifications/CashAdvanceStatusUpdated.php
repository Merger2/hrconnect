<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashAdvanceStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CashAdvance $advance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->advance->status;
        $statusLabel = match ($status) {
            'approved_l1' => 'Disetujui (Tahap 1)',
            'approved' => 'Disetujui Final',
            'rejected' => 'Ditolak',
            default => 'Perlu Tinjauan',
        };

        $subject = match ($status) {
            'approved_l1' => 'Pengajuan Kasbon Disetujui Tahap 1',
            'approved' => 'Pengajuan Kasbon Disetujui Final',
            'rejected' => 'Pengajuan Kasbon Ditolak',
            default => 'Update Status Pengajuan Kasbon',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Pengajuan kasbon Anda telah diperbarui.')
            ->line('Jumlah: ' . number_format($this->advance->amount, 0, ',', '.'))
            ->line('Tujuan: ' . $this->advance->purpose)
            ->line('Status: ' . $statusLabel);

        if ($status === 'rejected' && $this->advance->rejection_reason) {
            $mail->line('Alasan: ' . $this->advance->rejection_reason);
        } elseif ($status === 'approved' && $this->advance->notes) {
            $mail->line('Catatan: ' . $this->advance->notes);
        }

        return $mail->action('Lihat Detail', url('/my-kasbon'))
            ->line('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cash_advance_status_updated',
            'advance_id' => $this->advance->id,
            'status' => $this->advance->status,
            'amount' => $this->advance->amount,
            'message' => 'Pengajuan kasbon ' . $this->advance->status,
        ];
    }
}