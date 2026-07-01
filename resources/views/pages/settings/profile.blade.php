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
        $this->validate([
            'photo' => ['image', 'max:1024'],
        ]);

        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $this->photo->store('avatars', 'public');
        $user->update(['profile_photo_path' => $path]);
        $this->profilePhotoPath = $path;

        $this->dispatch('toast', variant: 'success', text: __('Photo updated.'));
    }

    public function removePhoto(): void
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->update(['profile_photo_path' => null]);
        $this->profilePhotoPath = null;

        $this->dispatch('toast', variant: 'success', text: __('Photo removed.'));
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

        $this->dispatch('toast', variant: 'success', text: __('Profile updated.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        $this->dispatch('toast', text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    {{-- Avatar Card --}}
    <div class="mb-6 rounded-lg border border-outline-variant bg-surface-container-low p-6">
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

            <div class="flex flex-col items-center gap-2 sm:items-start">
                <h2 class="text-xl font-semibold text-ink">{{ $name }}</h2>
                <p class="text-sm text-on-surface-variant">{{ $email }}</p>
                <div class="flex gap-2 pt-1">
                    <button type="button" wire:click="$refresh" class="rounded-lg px-3 py-1.5 text-xs font-medium text-on-surface-variant hover:bg-surface-container-high">
                        {{ __('Cancel') }}
                    </button>
                    @if ($profilePhotoPath)
                        <button type="button" wire:click="removePhoto" class="rounded-lg px-3 py-1.5 text-xs font-medium text-error hover:bg-error/10">
                            {{ __('Remove') }}
                        </button>
                    @endif
                </div>
                @error('photo')
                    <p class="text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Profile Form Card --}}
    <div class="mb-6 rounded-lg border border-outline-variant bg-surface-container-low p-6">
        <h3 class="text-lg font-semibold text-ink">{{ __('Profile') }}</h3>
        <p class="text-sm text-on-surface-variant">{{ __('Update your name and email address') }}</p>

        <form wire:submit="updateProfileInformation" class="mt-5 space-y-6">
            <div>
                <label class="mb-1 block text-sm font-medium text-on-background">{{ __('Name') }}</label>
                <input wire:model="name" type="text" required autofocus autocomplete="name" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink" />
                @error('name')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-on-background">{{ __('Email') }}</label>
                <input wire:model="email" type="email" required autocomplete="email" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink" />
                @error('email')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-4">
                        <p class="text-sm text-on-surface-variant">
                            {{ __('Your email address is unverified.') }}

                            <button class="cursor-pointer text-sm font-medium text-ink underline hover:text-on-surface-variant" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" data-test="update-profile-button">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Quick Links --}}
    <div class="mb-6 rounded-lg border border-outline-variant bg-surface-container-low p-6">
        <h3 class="text-sm font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Pengaturan Lainnya') }}</h3>
        <div class="mt-3 space-y-1">
            <a href="{{ route('attendance.face-registration') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-xl">face</span>
                <span>{{ __('Wajah') }}</span>
                <span class="text-xs text-on-surface-variant">{{ __('Daftarkan wajah untuk absensi') }}</span>
                <span class="material-symbols-outlined ml-auto text-base text-on-surface-variant">chevron_right</span>
            </a>
            <a href="{{ route('security.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-xl">lock</span>
                <span>{{ __('Keamanan') }}</span>
                <span class="text-xs text-on-surface-variant">{{ __('Kata sandi & verifikasi dua langkah') }}</span>
                <span class="material-symbols-outlined ml-auto text-base text-on-surface-variant">chevron_right</span>
            </a>
            <a href="{{ route('appearance.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-xl">palette</span>
                <span>{{ __('Tampilan') }}</span>
                <span class="text-xs text-on-surface-variant">{{ __('Mode terang/gelap') }}</span>
                <span class="material-symbols-outlined ml-auto text-base text-on-surface-variant">chevron_right</span>
            </a>
        </div>
    </div>

    {{-- Delete Account Card --}}
    @if ($this->showDeleteUser)
        <div class="rounded-lg border border-error/20 bg-error/5 p-6">
            <h3 class="text-lg font-semibold text-error">{{ __('Delete account') }}</h3>
            <p class="text-sm text-on-surface-variant">{{ __('Permanently delete your account and all of its resources. This action cannot be undone.') }}</p>
            <div class="mt-4">
                <livewire:pages::settings.delete-user-form />
            </div>
        </div>
    @endif
</section>
