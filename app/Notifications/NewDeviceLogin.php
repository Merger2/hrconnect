<?php

namespace App\Notifications;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDeviceLogin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Device $device,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Login dari Perangkat Baru')
            ->line('Akun Anda baru saja login dari perangkat yang belum dikenali.')
            ->line('Perangkat: '.($this->device->device_name ?: $this->device->device_uuid))
            ->line('Browser: '.($this->device->browser ?: 'Unknown'))
            ->line('Waktu: '.now()->format('d M Y H:i:s'))
            ->line('Jika bukan Anda, segera ganti password Anda.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'device_id' => $this->device->id,
            'device_name' => $this->device->device_name,
            'browser' => $this->device->browser,
            'os' => $this->device->os,
            'message' => 'Login dari perangkat baru terdeteksi.',
        ];
    }
}
