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
        $statusLabel = __(ucfirst((string) $this->advance->status));

        return (new MailMessage)
            ->subject(__('Cash Advance: :status', ['status' => $statusLabel]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your cash advance request status has been updated.'))
            ->line(__('Amount: Rp :amount', ['amount' => $amount]))
            ->line(__('Status: **:status**', ['status' => $statusLabel]))
            ->action(__('View Details'), url('/my-kasbon'));
    }
}
