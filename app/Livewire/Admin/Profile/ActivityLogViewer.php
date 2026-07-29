<?php

namespace App\Livewire\Admin\Profile;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ActivityLogViewer extends Component
{
    use WithPagination;

    public function render(): View
    {
        $logs = ActivityLog::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.admin.profile.activity-log-viewer', [
            'logs' => $logs,
        ]);
    }
}
