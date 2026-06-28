<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimbursementStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reimbursement $reimbursement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->reimbursement->status?->label() ?? $this->reimbursement->status;
        $amount = number_format((float) $this->reimbursement->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject('Status Reimbursement: '.$statusLabel)
            ->greeting('Halo '.($notifiable->name ?? '').',')
            ->line('Status pengajuan reimbursement Anda telah diperbarui.')
            ->line('Judul: '.$this->reimbursement->title)
            ->line('Jumlah: Rp '.$amount)
            ->line('Status: **'.$statusLabel.'**');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reimbursement_status',
            'reimbursement_id' => $this->reimbursement->id,
            'title' => $this->reimbursement->title,
            'status' => $this->reimbursement->status?->value,
            'status_label' => $this->reimbursement->status?->label(),
            'message' => 'Status reimbursement "'.$this->reimbursement->title.'" menjadi '
                .$this->reimbursement->status?->label(),
        ];
    }
}
