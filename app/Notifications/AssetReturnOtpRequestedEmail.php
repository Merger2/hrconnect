<?php

namespace App\Notifications;

use App\Models\CompanyAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssetReturnOtpRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CompanyAsset $asset,
        public string $userName,
        public string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('OTP Pengembalian Aset - '.$this->asset->name)
            ->line('OTP diperlukan untuk pengembalian aset: '.$this->asset->name)
            ->line('Karyawan: '.$this->userName)
            ->action('Lihat Pengajuan', url('/my-assets'));
    }
}
