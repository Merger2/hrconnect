<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OvertimeRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Overtime $overtime,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'overtime_id' => $this->overtime->id,
            'message' => 'Pengajuan lembur baru membutuhkan persetujuan.',
            'type' => 'overtime_request',
        ];
    }
}
