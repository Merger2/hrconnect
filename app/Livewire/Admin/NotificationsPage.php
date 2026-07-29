<?php

namespace App\Livewire\Admin;

use App\Enums\Permission;
use App\Livewire\Concerns\InteractsWithNotificationInbox;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationsPage extends Component
{
    use InteractsWithNotificationInbox;
    use WithPagination;

    public function boot(): void
    {
        $this->authorize(Permission::VIEW_NOTIFICATIONS->value);
    }

    public function render()
    {
        return view('livewire.admin.notifications-page', $this->getNotificationInboxViewData());
    }
}
