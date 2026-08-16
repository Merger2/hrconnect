<?php

namespace App\Livewire\Admin;

use App\Services\Admin\SettingsManagementService;
use App\Support\MailBranding;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Settings extends Component
{
    use WithFileUploads;

    public $logo = null;

    protected SettingsManagementService $settings;

    public function boot(SettingsManagementService $settings): void
    {
        $this->settings = $settings;
    }

    public function mount()
    {
        Gate::authorize('viewAdminSettings');
    }

    public function uploadLogo(): void
    {
        Gate::authorize('manageSystemSettings');

        $this->validate([
            'logo' => 'image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        $disk = Storage::disk('public');
        $disk->delete('images/icons/logo.png');
        $disk->delete('images/icons/logo.jpg');
        $disk->delete('images/icons/logo.jpeg');

        $this->logo->storeAs('images/icons', 'logo.png', 'public');

        $this->logo = null;

        $this->dispatch('saved', message: __('Logo updated successfully.'));
    }

    public function removeLogo(): void
    {
        Gate::authorize('manageSystemSettings');

        $disk = Storage::disk('public');
        $disk->delete('images/icons/logo.png');
        $disk->delete('images/icons/logo.jpg');
        $disk->delete('images/icons/logo.jpeg');

        $this->dispatch('saved', message: __('Logo removed.'));
    }

    public function updateValue($id, $value)
    {
        Gate::authorize('manageSystemSettings');

        if (auth()->user()?->is_demo) {
            $this->dispatch('error', message: __('Settings cannot be modified in demo mode.'));

            return;
        }

        if ($this->settings->updateValue($id, $value)) {
            $this->dispatch('saved');
        }
    }

    public function render()
    {
        $disk = Storage::disk('public');
        $logoPath = null;
        foreach (['logo.png', 'logo.jpg', 'logo.jpeg'] as $name) {
            if ($disk->exists("images/icons/{$name}")) {
                $logoPath = "images/icons/{$name}";
                break;
            }
        }

        $logoUrl = $logoPath ? $disk->url($logoPath) : MailBranding::logoUrl();
        $hasLogo = $logoPath !== null || is_file(public_path('images/icons/logo.png'))
            || is_file(public_path('images/icons/logo.jpg'))
            || is_file(public_path('images/icons/logo.jpeg'));

        return view('livewire.admin.settings', [
            'groups' => $this->settings->groupedSettings(),
            'logoUrl' => $logoUrl,
            'hasLogo' => $hasLogo,
        ]);
    }
}
