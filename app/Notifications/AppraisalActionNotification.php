<?php

namespace App\Notifications;

use App\Models\Appraisal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppraisalActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Appraisal $appraisal,
        public string $message,
        public string $actionUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Performance Appraisal Update'))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line($this->message)
            ->action(__('View Appraisal'), $this->actionUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appraisal_id' => $this->appraisal->id,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'type' => 'appraisal_action',
        ];
    }
}
