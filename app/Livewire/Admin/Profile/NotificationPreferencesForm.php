<?php

namespace App\Livewire\Admin\Profile;

use App\Models\UserNotificationPreference;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NotificationPreferencesForm extends Component
{
    public array $preferences = [];

    public function mount(): void
    {
        $prefs = UserNotificationPreference::query()
            ->where('user_id', auth()->id())
            ->get();

        if ($prefs->isEmpty()) {
            $pref = UserNotificationPreference::query()->create([
                'user_id' => auth()->id(),
                'event_key' => 'system_alerts',
                'channels' => ['in_app', 'email'],
            ]);
            $prefs->push($pref);
        }

        foreach ($prefs as $pref) {
            $this->preferences[$pref->id] = [
                'event_key' => $pref->event_key,
                'in_app' => in_array('in_app', $pref->channels ?? [], true),
                'email' => in_array('email', $pref->channels ?? [], true),
                'whatsapp' => in_array('whatsapp', $pref->channels ?? [], true),
            ];
        }
    }

    public function save(): void
    {
        foreach ($this->preferences as $id => $data) {
            $pref = UserNotificationPreference::query()
                ->where('user_id', auth()->id())
                ->find($id);

            if ($pref) {
                $channels = [];
                if ($data['in_app']) {
                    $channels[] = 'in_app';
                }
                if ($data['email']) {
                    $channels[] = 'email';
                }
                if ($data['whatsapp']) {
                    $channels[] = 'whatsapp';
                }

                $pref->update(['channels' => $channels]);
            }
        }

        $this->dispatch('saved');
    }

    public function render(): View
    {
        return view('livewire.admin.profile.notification-preferences-form');
    }
}
