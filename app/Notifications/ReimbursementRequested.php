<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReimbursementRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reimbursement $reimbursement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format((float) $this->reimbursement->amount, 0, ',', '.');

        return [
            'type' => 'reimbursement_request',
            'reimbursement_id' => $this->reimbursement->id,
            'employee_id' => $this->reimbursement->employee_id,
            'employee_name' => $this->reimbursement->employee?->full_name,
            'title' => $this->reimbursement->title,
            'amount' => $amount,
            'message' => 'Pengajuan reimbursement baru dari '.($this->reimbursement->employee?->full_name ?? 'Karyawan').': '
                .$this->reimbursement->title.' (Rp '.$amount.')',
        ];
    }
}
