<?php

namespace App\Notifications;

use App\Models\WorkFromHomeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WfhRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WorkFromHomeRequest $request,
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
            'approved_l1' => 'Pengajuan WFH Disetujui Tahap 1',
            'approved' => 'Pengajuan WFH Disetujui Final',
            'rejected' => 'Pengajuan WFH Ditolak',
            default => 'Update Status Pengajuan WFH',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Pengajuan WFH Anda telah diperbarui.')
            ->line('Tanggal: '.$this->request->date)
            ->line('Status: '.$statusLabel);

        if ($status === 'rejected' && $this->request->rejection_note) {
            $mail->line('Alasan: '.$this->request->rejection_note);
        } elseif ($status === 'approved' && $this->request->final_note) {
            $mail->line('Catatan: '.$this->request->final_note);
        }

        return $mail->action('Lihat Detail', url('/wfh-requests'))
            ->line('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wfh_status_updated',
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'date' => $this->request->date?->toDateString(),
            'message' => 'Pengajuan WFH '.$this->request->status,
        ];
    }
}
