<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Pengaturan Profil')] class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo = null;
    public ?string $profilePhotoPath = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->profilePhotoPath = $user->profile_photo_path;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:1024']]);

        $user = Auth::user();
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $this->photo->store('avatars', 'public');
        $user->update(['profile_photo_path' => $path]);
        $this->profilePhotoPath = $path;

        $this->dispatch('toast', variant: 'success', text: __('Foto diperbarui.'));
    }

    public function removePhoto(): void
    {
        $user = Auth::user();
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
        $user->update(['profile_photo_path' => null]);
        $this->profilePhotoPath = null;
        $this->dispatch('toast', variant: 'success', text: __('Foto dihapus.'));
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $validated = $this->validate($this->profileRules($user->id));
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
        $this->dispatch('toast', variant: 'success', text: __('Profil diperbarui.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();
        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }
        $user->sendEmailVerificationNotification();
        $this->dispatch('toast', text: __('Link verifikasi telah dikirim ke email Anda.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && !Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return !Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<x-layouts::app.sidebar :title="__('Pengaturan Profil')">
    <div class="mx-auto max-w-2xl">
        @include('partials.settings-heading')

        {{-- Avatar Card --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
            <div class="p-6">
                <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                    <div class="relative shrink-0">
                        <div class="flex size-24 items-center justify-center overflow-hidden rounded-full bg-surface-container text-2xl font-semibold text-ink ring-2 ring-outline-variant">
                            @if ($profilePhotoPath)
                                <img src="{{ Storage::disk('public')->url($profilePhotoPath) }}" alt="{{ $name }}" class="size-full object-cover" />
                            @else
                                {{ auth()->user()->initials() }}
                            @endif
                        </div>
                        <label class="absolute bottom-0 right-0 flex size-8 cursor-pointer items-center justify-center rounded-full bg-ink text-white shadow-sm ring-2 ring-canvas">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                            <input type="file" wire:model="photo" accept="image/*" class="hidden" />
                        </label>
                    </div>

                    <div class="flex flex-col items-center gap-1 sm:items-start">
                        <h2 class="text-xl font-semibold text-ink">{{ $name }}</h2>
                        <p class="text-sm text-on-surface-variant">{{ $email }}</p>
                        @if ($profilePhotoPath)
                            <button type="button" wire:click="removePhoto" class="mt-1 rounded-lg px-3 py-1 text-xs font-medium text-error hover:bg-error/10">{{ __('Hapus Foto') }}</button>
                        @endif
                        @error('photo')<p class="text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Profile Form --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
            <div class="border-b border-outline-variant/50 px-6 py-4">
                <h3 class="text-sm font-semibold text-ink">{{ __('Informasi Profil') }}</h3>
                <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('Perbarui nama dan alamat email Anda') }}</p>
            </div>
            <form wire:submit="updateProfileInformation" class="p-6 space-y-5">
                <div>
                    <x-forms.label for="name" value="{{ __('Nama') }}" />
                    <x-forms.input wire:model="name" type="text" required autofocus autocomplete="name" class="mt-1.5 w-full" />
                    <x-forms.error name="name" />
                </div>

                <div>
                    <x-forms.label for="email" value="{{ __('Surel') }}" />
                    <x-forms.input wire:model="email" type="email" required autocomplete="email" class="mt-1.5 w-full" />
                    <x-forms.error name="email" />

                    @if ($this->hasUnverifiedEmail)
                        <div class="mt-4 rounded-xl border border-warning/30 bg-warning/5 px-4 py-3">
                            <p class="text-sm text-warning">
                                {{ __('Alamat email Anda belum diverifikasi.') }}
                                <button class="font-medium underline hover:no-underline" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Kirim ulang email verifikasi.') }}
                                </button>
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3 border-t border-outline-variant/50 pt-4">
                    <x-button type="submit" variant="primary">{{ __('Simpan') }}</x-button>
                </div>
            </form>
        </div>

        {{-- Navigation Links --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
            <div class="border-b border-outline-variant/50 px-6 py-4">
                <h3 class="text-sm font-semibold text-ink">{{ __('Pengaturan Lainnya') }}</h3>
            </div>
            <div class="divide-y divide-outline-variant/50">
                <a href="{{ route('attendance.face-registration') }}" wire:navigate class="flex items-center gap-3 px-6 py-3.5 text-sm font-medium text-ink transition-colors hover:bg-surface-dim/30">
                    <span class="material-symbols-outlined text-xl">face</span>
                    <div class="flex-1 min-w-0">
                        <span>{{ __('Pendaftaran Wajah') }}</span>
                        <p class="text-xs text-on-surface-variant">{{ __('Daftarkan wajah untuk absensi') }}</p>
                    </div>
                    <span class="material-symbols-outlined text-base text-on-surface-variant">chevron_right</span>
                </a>
                <a href="{{ route('security.edit') }}" wire:navigate class="flex items-center gap-3 px-6 py-3.5 text-sm font-medium text-ink transition-colors hover:bg-surface-dim/30">
                    <span class="material-symbols-outlined text-xl">lock</span>
                    <div class="flex-1 min-w-0">
                        <span>{{ __('Keamanan') }}</span>
                        <p class="text-xs text-on-surface-variant">{{ __('Kata sandi & verifikasi dua langkah') }}</p>
                    </div>
                    <span class="material-symbols-outlined text-base text-on-surface-variant">chevron_right</span>
                </a>
            </div>
        </div>

        {{-- Delete Account --}}
        @if ($this->showDeleteUser)
            <div class="mb-6 overflow-hidden rounded-xl border border-error/30 bg-error/5">
                <div class="border-b border-error/20 px-6 py-4">
                    <h3 class="text-sm font-semibold text-error">{{ __('Hapus Akun') }}</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-on-surface-variant">{{ __('Hapus akun Anda dan seluruh data secara permanen. Tindakan ini tidak dapat dibatalkan.') }}</p>
                    <div class="mt-4">
                        <livewire:pages::settings.delete-user-form />
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app.sidebar>
