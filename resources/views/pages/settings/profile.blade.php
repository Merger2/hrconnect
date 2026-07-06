<?php

use App\Concerns\ProfileValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Models\UserNotificationPreference;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Indonesia\Models\Province;
use Laravel\Indonesia\Models\City;
use Laravel\Indonesia\Models\District;
use Laravel\Indonesia\Models\Village;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Activitylog\Models\Activity;

new #[Title('Pengaturan Profil')] class extends Component {
    use ProfileValidationRules, PasswordValidationRules, WithFileUploads;

    // --- Details ---
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $photo = null;
    public ?string $profilePhotoPath = null;
    public ?string $province_id = null;
    public ?string $city_id = null;
    public ?string $district_id = null;
    public ?string $village_id = null;
    public string $address_detail = '';
    public string $postal_code = '';

    // --- Password ---
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    // --- 2FA ---
    public bool $canManageTwoFactor;
    public bool $twoFactorEnabled;
    public bool $requiresConfirmation;

    // --- Active Tab ---
    public string $activeTab = 'details';

    public function mount(DisableTwoFactorAuthentication $disable): void
    {
        $user = Auth::user();
        $employee = $user->employee;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->profilePhotoPath = $user->profile_photo_path;

        if ($employee) {
            $this->phone = $employee->phone ?? '';
            $this->province_id = $employee->province_id ? (string) $employee->province_id : null;
            $this->city_id = $employee->city_id ? (string) $employee->city_id : null;
            $this->district_id = $employee->district_id ? (string) $employee->district_id : null;
            $this->village_id = $employee->village_id ? (string) $employee->village_id : null;
            $this->address_detail = $employee->address_detail ?? '';
            $this->postal_code = $employee->postal_code ?? '';
        }

        $this->mountNotificationPrefs();

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();
        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disable(auth()->user());
            }
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    // ======== DETAILS TAB ========

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

    public function updatedProvinceId(): void
    {
        $this->city_id = null;
        $this->district_id = null;
        $this->village_id = null;
    }

    public function updatedCityId(): void
    {
        $this->district_id = null;
        $this->village_id = null;
    }

    public function updatedDistrictId(): void
    {
        $this->village_id = null;
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

        if ($employee = $user->employee) {
            $employee->update([
                'phone' => $this->phone,
                'province_id' => $this->province_id ?: null,
                'city_id' => $this->city_id ?: null,
                'district_id' => $this->district_id ?: null,
                'village_id' => $this->village_id ?: null,
                'address_detail' => $this->address_detail,
                'postal_code' => $this->postal_code,
            ]);
        }

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

    // ======== PASSWORD TAB ========

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'new_password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'new_password', 'new_password_confirmation');
            throw $e;
        }

        Auth::user()->update(['password' => $validated['new_password']]);
        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        $this->dispatch('toast', variant: 'success', text: __('Kata sandi diperbarui.'));
    }

    // ======== 2FA TAB ========

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disableTwoFactor(DisableTwoFactorAuthentication $disable): void
    {
        $disable(auth()->user());
        $this->twoFactorEnabled = false;
        $this->dispatch('toast', variant: 'success', text: __('2FA dinonaktifkan.'));
    }

    // ======== SESSIONS TAB ========

    #[Computed]
    public function sessions()
    {
        try {
            return DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', Auth::id())
                ->where('id', '!=', request()->session()->getId())
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    public function logoutOtherSessions(): void
    {
        $user = Auth::user();
        $user->update(['password' => $user->password]);

        request()->session()->passwordConfirmed();

        $this->dispatch('toast', variant: 'success', text: __('Perangkat lain telah logout.'));
    }

    // ======== ACTIVITY LOG TAB ========

    #[Computed]
    public function activities()
    {
        try {
            return Activity::where('causer_id', Auth::id())
                ->where('causer_type', get_class(Auth::user()))
                ->latest()
                ->paginate(10);
        } catch (\Throwable) {
            return new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 10);
        }
    }

    // ======== NOTIFICATION PREFERENCES TAB ========

    public array $notificationPrefs = [];

    public function mountNotificationPrefs(): void
    {
        try {
            $prefs = UserNotificationPreference::where('user_id', Auth::id())->get();
            if ($prefs->isEmpty()) {
                $prefs = collect([
                    UserNotificationPreference::create([
                        'user_id' => Auth::id(),
                        'event_key' => 'system_alerts',
                        'channels' => ['in_app', 'email'],
                    ]),
                ]);
            }
            foreach ($prefs as $pref) {
                $this->notificationPrefs[$pref->id] = [
                    'event_key' => $pref->event_key,
                    'in_app' => in_array('in_app', $pref->channels ?? []),
                    'email' => in_array('email', $pref->channels ?? []),
                ];
            }
        } catch (\Throwable) {
            $this->notificationPrefs = [];
        }
    }

    public function saveNotificationPreferences(): void
    {
        foreach ($this->notificationPrefs as $id => $data) {
            $channels = [];
            if ($data['in_app']) $channels[] = 'in_app';
            if ($data['email']) $channels[] = 'email';
            UserNotificationPreference::where('id', $id)->update(['channels' => $channels]);
        }
        $this->dispatch('toast', variant: 'success', text: __('Preferensi notifikasi diperbarui.'));
    }

    // ======== COMPUTED ========

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

    #[Computed]
    public function provinces()
    {
        try {
            return Province::orderBy('name')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    #[Computed]
    public function cities()
    {
        if (!$this->province_id) return collect();
        try {
            return City::where('province_code', Province::find($this->province_id)?->code)->orderBy('name')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    #[Computed]
    public function districts()
    {
        if (!$this->city_id) return collect();
        try {
            return District::where('city_code', City::find($this->city_id)?->code)->orderBy('name')->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    #[Computed]
    public function villages()
    {
        if (!$this->district_id) return collect();
        try {
            return Village::where('district_code', District::find($this->district_id)?->code)->orderBy('name')->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}; ?>

<div
    x-data="{
        activeTab: '{{ $activeTab }}',
        tabs: ['details', 'password', 'security', 'sessions', 'activity', 'notifications'],
        init() {
            const hash = window.location.hash.slice(1);
            if (this.tabs.includes(hash)) this.activeTab = hash;
        },
        setTab(tab) {
            this.activeTab = tab;
            window.history.replaceState({}, '', '#' + tab);
        }
    }"
    class="mx-auto max-w-4xl"
>
    @include('partials.settings-heading')

    <div class="flex items-start gap-8 max-md:flex-col">
        {{-- Sidebar --}}
        <aside class="w-full shrink-0 md:w-48">
            <nav class="flex flex-wrap gap-1 md:flex-col">
                <button @click="setTab('details')" :class="activeTab === 'details' ? 'bg-ink/10 text-ink font-semibold' : 'text-on-surface-variant hover:text-ink hover:bg-ink/5'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors">
                    <span class="material-symbols-outlined text-lg">person</span>
                    <span>{{ __('Detail') }}</span>
                </button>
                <button @click="setTab('password')" :class="activeTab === 'password' ? 'bg-ink/10 text-ink font-semibold' : 'text-on-surface-variant hover:text-ink hover:bg-ink/5'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors">
                    <span class="material-symbols-outlined text-lg">lock</span>
                    <span>{{ __('Kata Sandi') }}</span>
                </button>
                <button @click="setTab('security')" :class="activeTab === 'security' ? 'bg-ink/10 text-ink font-semibold' : 'text-on-surface-variant hover:text-ink hover:bg-ink/5'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors">
                    <span class="material-symbols-outlined text-lg">security</span>
                    <span>{{ __('2FA') }}</span>
                </button>
                <button @click="setTab('sessions')" :class="activeTab === 'sessions' ? 'bg-ink/10 text-ink font-semibold' : 'text-on-surface-variant hover:text-ink hover:bg-ink/5'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors">
                    <span class="material-symbols-outlined text-lg">devices</span>
                    <span>{{ __('Perangkat') }}</span>
                </button>
                <button @click="setTab('activity')" :class="activeTab === 'activity' ? 'bg-ink/10 text-ink font-semibold' : 'text-on-surface-variant hover:text-ink hover:bg-ink/5'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors">
                    <span class="material-symbols-outlined text-lg">history</span>
                    <span>{{ __('Aktivitas') }}</span>
                </button>
                <button @click="setTab('notifications')" :class="activeTab === 'notifications' ? 'bg-ink/10 text-ink font-semibold' : 'text-on-surface-variant hover:text-ink hover:bg-ink/5'" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors">
                    <span class="material-symbols-outlined text-lg">notifications</span>
                    <span>{{ __('Notifikasi') }}</span>
                </button>
            </nav>
        </aside>

        {{-- Content --}}
        <div class="min-w-0 flex-1">
            {{-- DETAILS TAB --}}
            <section x-show="activeTab === 'details'" x-cloak x-transition.opacity.duration.150ms>
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

                <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                    <div class="border-b border-outline-variant/50 px-6 py-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Informasi Profil') }}</h3>
                    </div>
                    <form wire:submit="updateProfileInformation" class="space-y-5 p-6">
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
                        <div>
                            <x-forms.label for="phone" value="{{ __('No. Telepon') }}" />
                            <x-forms.input wire:model="phone" type="text" class="mt-1.5 w-full" />
                            <x-forms.error name="phone" />
                        </div>

                        {{-- Regional Cascade --}}
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <x-forms.label for="province_id" value="{{ __('Provinsi') }}" />
                                <select wire:model.live="province_id" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                                    <option value="">{{ __('Pilih Provinsi') }}</option>
                                    @foreach ($this->provinces as $prov)
                                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-forms.label for="city_id" value="{{ __('Kabupaten/Kota') }}" />
                                <select wire:model.live="city_id" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                                    <option value="">{{ __('Pilih Kabupaten/Kota') }}</option>
                                    @foreach ($this->cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-forms.label for="district_id" value="{{ __('Kecamatan') }}" />
                                <select wire:model.live="district_id" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                                    <option value="">{{ __('Pilih Kecamatan') }}</option>
                                    @foreach ($this->districts as $district)
                                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-forms.label for="village_id" value="{{ __('Kelurahan/Desa') }}" />
                                <select wire:model.live="village_id" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                                    <option value="">{{ __('Pilih Kelurahan/Desa') }}</option>
                                    @foreach ($this->villages as $village)
                                        <option value="{{ $village->id }}">{{ $village->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-forms.label for="address_detail" value="{{ __('Alamat Lengkap') }}" />
                            <textarea wire:model="address_detail" rows="2" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"></textarea>
                        </div>

                        <div class="sm:w-1/3">
                            <x-forms.label for="postal_code" value="{{ __('Kode Pos') }}" />
                            <x-forms.input wire:model="postal_code" type="text" class="mt-1.5 w-full" />
                        </div>

                        <div class="flex items-center gap-3 border-t border-outline-variant/50 pt-4">
                            <x-button type="submit" variant="primary">{{ __('Simpan') }}</x-button>
                        </div>
                    </form>
                </div>

                @if ($this->showDeleteUser)
                    <div class="mt-6 overflow-hidden rounded-xl border border-error/30 bg-error/5">
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
            </section>

            {{-- PASSWORD TAB --}}
            <section x-show="activeTab === 'password'" x-cloak x-transition.opacity.duration.150ms>
                <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                    <div class="border-b border-outline-variant/50 px-6 py-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Perbarui Kata Sandi') }}</h3>
                        <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('Gunakan kata sandi yang kuat, kombinasi huruf, angka, dan simbol.') }}</p>
                    </div>
                    <form wire:submit="updatePassword" class="space-y-5 p-6">
                        <div>
                            <x-forms.label for="current_password" value="{{ __('Kata Sandi Saat Ini') }}" />
                            <x-forms.input wire:model="current_password" type="password" required autocomplete="current-password" class="mt-1.5 w-full" />
                            <x-forms.error name="current_password" />
                        </div>
                        <div>
                            <x-forms.label for="new_password" value="{{ __('Kata Sandi Baru') }}" />
                            <x-forms.input wire:model="new_password" type="password" required autocomplete="new-password" class="mt-1.5 w-full" />
                            <x-forms.error name="new_password" />
                        </div>
                        <div>
                            <x-forms.label for="new_password_confirmation" value="{{ __('Konfirmasi Kata Sandi') }}" />
                            <x-forms.input wire:model="new_password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 w-full" />
                        </div>
                        <div class="flex items-center gap-3 border-t border-outline-variant/50 pt-4">
                            <x-button type="submit" variant="primary">{{ __('Simpan') }}</x-button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- SECURITY / 2FA TAB --}}
            <section x-show="activeTab === 'security'" x-cloak x-transition.opacity.duration.150ms>
                <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                    <div class="border-b border-outline-variant/50 px-6 py-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Verifikasi Dua Langkah') }}</h3>
                        <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('Tingkatkan keamanan akun dengan kode verifikasi tambahan.') }}</p>
                    </div>
                    <div class="p-6">
                        @if ($canManageTwoFactor)
                            @if ($twoFactorEnabled)
                                <p class="mb-4 text-sm text-on-surface-variant">{{ __('Kode acak akan diminta dari aplikasi authenticator setiap login.') }}</p>
                                <div class="flex items-center gap-3">
                                    <x-button variant="danger" wire:click="disableTwoFactor">{{ __('Nonaktifkan 2FA') }}</x-button>
                                </div>
                                <div class="mt-6">
                                    <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                                </div>
                            @else
                                <p class="mb-4 text-sm text-on-surface-variant">{{ __('Aktifkan verifikasi dua langkah untuk keamanan ekstra.') }}</p>
                                <x-button variant="primary"
                                    x-data
                                    @click="$dispatch('open-modal', 'two-factor-setup-modal'); $wire.dispatch('start-two-factor-setup')"
                                >{{ __('Aktifkan 2FA') }}</x-button>
                                <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                            @endif
                        @else
                            <p class="text-sm text-on-surface-variant">{{ __('Fitur 2FA tidak tersedia.') }}</p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- SESSIONS TAB --}}
            <section x-show="activeTab === 'sessions'" x-cloak x-transition.opacity.duration.150ms>
                <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                    <div class="border-b border-outline-variant/50 px-6 py-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Perangkat & Sesi') }}</h3>
                        <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('Kelola perangkat yang terhubung ke akun Anda.') }}</p>
                    </div>
                    <div class="p-6">
                        <div class="mb-4 space-y-3">
                            @forelse ($this->sessions as $session)
                                <div class="flex items-center gap-3 rounded-lg border border-outline-variant/50 p-3">
                                    <span class="material-symbols-outlined text-2xl text-on-surface-variant">
                                        {{ str_contains($session->user_agent ?? '', 'Mobile') ? 'smartphone' : 'desktop_windows' }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-ink">{{ $session->ip_address }}</p>
                                        <p class="truncate text-xs text-on-surface-variant">{{ $session->user_agent }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ __('Terakhir aktif') }}: {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-on-surface-variant">{{ __('Tidak ada sesi lain.') }}</p>
                            @endforelse
                        </div>
                        <x-button variant="secondary" wire:click="logoutOtherSessions" wire:confirm="{{ __('Yakin ingin logout dari semua perangkat lain?') }}">
                            {{ __('Logout Perangkat Lain') }}
                        </x-button>
                    </div>
                </div>
            </section>

            {{-- ACTIVITY LOG TAB --}}
            <section x-show="activeTab === 'activity'" x-cloak x-transition.opacity.duration.150ms>
                <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                    <div class="border-b border-outline-variant/50 px-6 py-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Log Aktivitas') }}</h3>
                        <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('Riwayat aktivitas akun Anda.') }}</p>
                    </div>
                    <div class="p-6">
                        @php $activities = $this->activities; @endphp
                        @forelse ($activities as $log)
                            <div class="flex items-start gap-3 border-b border-outline-variant/30 py-3 last:border-0">
                                <span class="material-symbols-outlined mt-0.5 text-base text-on-surface-variant">circle</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-ink">{{ $log->description }}</p>
                                    <p class="text-xs text-on-surface-variant">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-on-surface-variant">{{ __('Belum ada aktivitas.') }}</p>
                        @endforelse
                        <div class="mt-4">
                            {{ $activities->links() }}
                        </div>
                    </div>
                </div>
            </section>

            {{-- NOTIFICATION PREFERENCES TAB --}}
            <section x-show="activeTab === 'notifications'" x-cloak x-transition.opacity.duration.150ms>
                <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                    <div class="border-b border-outline-variant/50 px-6 py-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Preferensi Notifikasi') }}</h3>
                        <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('Atur notifikasi yang ingin Anda terima.') }}</p>
                    </div>
                    <form wire:submit="saveNotificationPreferences" class="p-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant/50 text-left text-xs font-medium text-on-surface-variant">
                                    <th class="pb-2 font-medium">{{ __('Event') }}</th>
                                    <th class="pb-2 px-4 text-center font-medium">{{ __('In-App') }}</th>
                                    <th class="pb-2 text-center font-medium">{{ __('Email') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notificationPrefs as $id => $data)
                                    <tr class="border-b border-outline-variant/30">
                                        <td class="py-3 text-ink">{{ \Illuminate\Support\Str::headline($data['event_key']) }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <input type="checkbox" wire:model="notificationPrefs.{{ $id }}.in_app" class="rounded border-outline-variant text-ink focus:ring-ink" />
                                        </td>
                                        <td class="py-3 text-center">
                                            <input type="checkbox" wire:model="notificationPrefs.{{ $id }}.email" class="rounded border-outline-variant text-ink focus:ring-ink" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4 flex items-center gap-3 border-t border-outline-variant/50 pt-4">
                            <x-button type="submit" variant="primary">{{ __('Simpan') }}</x-button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
