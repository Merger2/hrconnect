<?php

namespace App\Notifications;

use App\Models\CustomFormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomFormSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CustomFormSubmission $submission,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'custom_form_submitted',
            'submission_id' => $this->submission->id,
            'title' => $this->submission->template?->title ?? 'Form Baru',
            'message' => 'Pengajuan form baru membutuhkan review.',
        ];
    }
}
