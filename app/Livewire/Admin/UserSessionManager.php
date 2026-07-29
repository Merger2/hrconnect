<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserSessionManager extends Component
{
    public function render()
    {
        return view('livewire.admin.user-session-manager');
    }
}
