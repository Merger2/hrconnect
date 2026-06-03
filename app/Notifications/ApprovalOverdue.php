<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ApprovalOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Approval $approval,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approvable = $this->approval->approvable;

        return [
            'approval_id' => $this->approval->id,
            'approvable_type' => class_basename($this->approval->approvable_type),
            'approvable_id' => $this->approval->approvable_id,
            'approver_name' => $this->approval->approver->full_name ?? 'Unknown',
            'message' => 'Approval '.class_basename($this->approval->approvable_type).' #'.$this->approval->approvable_id.' belum diproses dan sudah melewati batas waktu.',
        ];
    }
}
