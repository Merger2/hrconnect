<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CashAdvanceUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CashAdvance $advance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format((float) $this->advance->amount, 0, ',', '.');

        return [
            'type' => 'cash_advance_updated',
            'cash_advance_id' => $this->advance->id,
            'status' => $this->advance->status,
            'amount' => $amount,
            'message' => __('Cash advance status updated to :status.', [
                'status' => __(ucfirst((string) $this->advance->status)),
            ]),
        ];
    }
}
