<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Support\CommandCenterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CommandCenter extends Component
{
    protected CommandCenterService $commandCenter;

    public function boot(CommandCenterService $commandCenter): void
    {
        Gate::authorize('viewCommandCenter');

        $this->commandCenter = $commandCenter;
    }

    public function render()
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return view('livewire.admin.command-center', [
            'summary' => $this->commandCenter->summary($user),
            'cards' => $this->commandCenter->cards($user),
            'queues' => $this->commandCenter->actionQueues($user),
        ]);
    }
}
