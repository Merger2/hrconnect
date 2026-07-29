<?php

namespace App\Livewire\User;

use App\Enums\Permission;
use App\Livewire\Concerns\InteractsWithNotificationInbox;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsPage extends Component
{
    use InteractsWithNotificationInbox;
    use WithPagination;

    public function mount(): void
    {
        if (Auth::user()?->can(Permission::VIEW_NOTIFICATIONS->value)) {
            $this->redirectRoute('admin.notifications', navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.user.notifications-page', $this->getNotificationInboxViewData())
            ->layout('layouts.app');
    }
}
