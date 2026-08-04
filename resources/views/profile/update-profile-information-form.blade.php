<div class="profile-section__card">
    <div class="profile-section__header">
        <div class="min-w-0">
            <h3 class="profile-section__title">{{ __('Profile Information') }}</h3>
            <p class="profile-section__desc">{{ __('Update your account\'s profile information and email address.') }}</p>
        </div>
    </div>

    <form wire:submit="updateProfileInformation" class="profile-section__body">
        {{-- Profile Photo --}}
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="{photoName: null, photoPreview: null}" class="mb-5">
                <input type="file" id="profile-photo-input" class="sr-only"
                    wire:model.live="photo"
                    x-ref="photo"
                    x-on:change="
                        photoName = $refs.photo.files[0].name;
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; };
                        reader.readAsDataURL($refs.photo.files[0]);
                    " />

                <label class="profile-field__label" for="profile-photo-input">{{ __('Photo') }}</label>

                <div class="mt-2 flex items-end gap-4">
                    <div class="shrink-0">
                        <div class="mt-2" x-show="! photoPreview">
                            <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}" class="rounded-full size-20 object-cover ring-2 ring-slate-100">
                        </div>
                        <div class="mt-2" x-show="photoPreview" style="display: none;">
                            <span class="block rounded-full size-20 bg-cover bg-no-repeat bg-center ring-2 ring-slate-100"
                                  x-bind:style="'background-image: url(\'' + photoPreview + '\');'"></span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <label for="profile-photo-input"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            {{ __('Select A New Photo') }}
                        </label>
                        @if ($this->user->profile_photo_path)
                            <button type="button"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                wire:click="deleteProfilePhoto">
                                {{ __('Remove Photo') }}
                            </button>
                        @endif
                    </div>
                </div>
                @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Name --}}
        <div class="mb-4">
            <label class="profile-field__label" for="name">{{ __('Name') }}</label>
            <input id="name" type="text"
                class="profile-field__input mt-1 block w-full"
                wire:model="state.name" required autocomplete="name" />
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label class="profile-field__label" for="email">{{ __('Email') }}</label>
            <input id="email" type="email"
                class="profile-field__input mt-1 block w-full"
                wire:model="state.email" required autocomplete="username" />
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('Your email address is unverified.') }}
                    <button type="button"
                        class="font-semibold text-primary-600 underline hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
                        wire:click.prevent="sendEmailVerification">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>
                @if ($this->verificationLinkSent)
                    <p class="mt-1 text-sm font-medium text-emerald-600">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <div x-data="{ shown: false, timeout: null }"
                 x-init="window.Livewire.find('{{ $__livewire->getId() }}').on('saved', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
                 x-show.transition.out.opacity.duration.1500ms="shown"
                 x-cloak
                 class="text-sm font-medium text-emerald-600">
                {{ __('Saved.') }}
            </div>
            <button type="submit"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                wire:loading.attr="disabled" wire:target="photo">
                {{ __('Save') }}
            </button>
        </div>
    </form>
</div>
