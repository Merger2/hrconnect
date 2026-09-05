<?php

namespace App\Notifications;

use App\Models\ShiftSwapRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftSwapStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShiftSwapRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->request->status;
        $statusLabel = match ($status) {
            'approved_l1' => 'Disetujui (Tahap 1)',
            'approved' => 'Disetujui Final',
            'rejected' => 'Ditolak',
            default => 'Perlu Tinjauan',
        };

        $subject = match ($status) {
            'approved_l1' => 'Pengajuan Tukar Shift Disetujui Tahap 1',
            'approved' => 'Pengajuan Tukar Shift Disetujui Final',
            'rejected' => 'Pengajuan Tukar Shift Ditolak',
            default => 'Update Status Pengajuan Tukar Shift',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Pengajuan tukar shift Anda telah diperbarui.')
            ->line('Tanggal: ' . $this->request->schedule_date)
            ->line('Dari Shift: ' . ($this->request->currentShift?->name ?? 'Tidak ada'))
            ->line('Ke Shift: ' . ($this->request->requestedShift?->name ?? 'Tidak ada'))
            ->line('Status: ' . $statusLabel);

        if ($status === 'rejected' && $this->request->rejection_note) {
            $mail->line('Alasan: ' . $this->request->rejection_note);
        }

        return $mail->action('Lihat Detail', url('/shift-swap-requests'))
            ->line('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_swap_status_updated',
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'schedule_date' => $this->request->schedule_date?->toDateString(),
            'message' => 'Pengajuan tukar shift ' . $this->request->status,
        ];
    }
}