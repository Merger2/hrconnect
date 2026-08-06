<?php

namespace App\Notifications;

use App\Models\EmployeeDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployeeDocumentRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Document Ready'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your requested document is now ready.'))
            ->line(__('Document: :type', ['type' => $this->request->documentType?->name ?? '-']))
            ->line(__('You can download it from the document requests page.'))
            ->action(__('View Document'), url('/document-requests'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_ready',
            'document_request_id' => $this->request->id,
            'document_type' => $this->request->documentType->name,
            'message' => __('Document :type is ready for download.', [
                'type' => $this->request->documentType?->name ?? __('Document'),
            ]),
        ];
    }
}
