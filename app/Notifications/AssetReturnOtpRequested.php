<?php

namespace App\Notifications;

use App\Models\CompanyAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AssetReturnOtpRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CompanyAsset $asset,
        public string $userName,
        public string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'asset_id' => $this->asset->id,
            'asset_name' => $this->asset->name,
            'user_name' => $this->userName,
            'message' => 'OTP diperlukan untuk pengembalian aset: '.$this->asset->name,
            'type' => 'asset_return_otp',
        ];
    }
}
