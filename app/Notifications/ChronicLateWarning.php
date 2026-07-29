<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChronicLateWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public int $lateCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Chronic Lateness Warning'))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line(__('You have been late :count times this month.', ['count' => $this->lateCount]))
            ->line(__('Please ensure you arrive on time to avoid further disciplinary action.'))
            ->action(__('View Attendance'), url('/attendances'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_number' => $this->employee->employee_number,
            'late_count' => $this->lateCount,
            'message' => __('You have been late :count times this month.', ['count' => $this->lateCount]),
            'type' => 'chronic_late_warning',
        ];
    }
}
