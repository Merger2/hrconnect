<?php

namespace App\Notifications;

use App\Models\CustomFormSubmission;
use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectTaskAssignedFromForm extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProjectTask $task,
        public CustomFormSubmission $submission,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $templateTitle = $this->submission->template?->title ?? __('Form');

        return (new MailMessage)
            ->subject(__('Task Assigned: :title', ['title' => $this->task->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('A new task has been assigned to you from a form submission.'))
            ->line(__('Form: :title', ['title' => $templateTitle]))
            ->line(__('Task: :title', ['title' => $this->task->title]))
            ->line(__('Priority: :priority', ['priority' => __(ucfirst((string) $this->task->priority))]))
            ->action(__('View Task'), url('/my-tasks'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned_from_form',
            'task_id' => $this->task->id,
            'submission_id' => $this->submission->id,
            'title' => $this->task->title,
            'message' => __('New task assigned: :title', ['title' => $this->task->title]),
        ];
    }
}
