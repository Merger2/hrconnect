<?php

namespace App\Livewire\User\Profile;

use Livewire\Component;

class FaceRegistration extends Component
{
    public function mount(): void
    {
        $this->redirectRoute('face.enrollment');
    }

    public function render()
    {
        return view('livewire.user.profile.face-registration');
    }
}
