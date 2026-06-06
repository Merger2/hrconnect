<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder Clock-In')
            ->greeting('Halo!')
            ->line('Anda belum melakukan clock-in hari ini.')
            ->line('Jangan lupa untuk clock-in sebelum pukul 10:00.')
            ->action('Clock-In Sekarang', url('/attendances/clock-in'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Reminder: Anda belum clock-in hari ini.',
        ];
    }
}
