<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class LeaveRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Attendance $attendance,
        public Carbon $fromDate,
        public Carbon $toDate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'attendance_id' => $this->attendance->id,
            'from_date' => $this->fromDate->toDateString(),
            'to_date' => $this->toDate->toDateString(),
            'message' => 'Pengajuan cuti baru dari tanggal '.$this->fromDate->format('d M Y').' sampai '.$this->toDate->format('d M Y'),
            'type' => 'leave_request',
        ];
    }
}
