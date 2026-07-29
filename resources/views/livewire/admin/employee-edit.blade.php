<div>
<<<<<<< HEAD
    <x-slot:title>{{ __('Edit Karyawan') }}</x-slot:title>

    <div class="space-y-8">
        {{-- Header --}}
        <section class="ess-card p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-soft text-primary">
                    <span class="material-symbols-outlined text-xl">edit</span>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-ink">{{ __('Edit Karyawan') }}</h1>
                    <p class="mt-0.5 text-sm text-on-surface-variant">{{ __('Ubah data karyawan') }}</p>
                </div>
            </div>
        </section>

        <form x-data="{
            nik: '',
            phone: '',
            showPw: false,
            showPwConf: false,
            submitForm() {
                $wire.form.nik = this.nik;
                $wire.form.phone = this.phone;
                $wire.save();
            }
        }" @submit.prevent="submitForm" class="mx-auto max-w-3xl space-y-6">
            {{-- Akun --}}
            <div class="rounded-xl border border-outline-variant/30 bg-canvas p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3 border-b border-outline-variant/30 pb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary-soft text-primary">
                        <span class="material-symbols-outlined text-lg">account_circle</span>
                    </div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">{{ __('Akun') }}</h3>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="name">{{ __('Nama') }}</x-forms.label>
                        <x-forms.input wire:model="form.name" id="name" required class="mt-1.5 w-full" placeholder="{{ __('Nama pengguna') }}" />
                        <x-forms.error name="form.name" />
                    </div>
                    <div>
                        <x-forms.label for="email">{{ __('Email') }}</x-forms.label>
                        <x-forms.input wire:model="form.email" type="email" id="email" autocomplete="off" required class="mt-1.5 w-full" placeholder="{{ __('email') }}" />
                        <x-forms.error name="form.email" />
                    </div>
                    <div class="sm:col-span-2 grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-forms.label for="password">{{ __('Kata Sandi Baru') }}</x-forms.label>
                            <div class="relative mt-1.5">
                                <input x-model="password" x-bind:type="showPw ? 'text' : 'password'" type="password" id="password" autocomplete="new-password"
                                    class="block w-full rounded-md border bg-canvas px-3 py-2 text-sm text-ink placeholder:text-muted-soft focus:border-ink focus:ring-0 border-outline-variant pr-10"
                                    placeholder="{{ __('Kosongkan jika tidak ingin mengubah') }}" />
                                <button type="button" @click="showPw = !showPw" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-ink">
                                    <span class="material-symbols-outlined text-lg" x-text="showPw ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-forms.error name="form.password" />
                        </div>
                        <div>
                            <x-forms.label for="password_confirmation">{{ __('Konfirmasi Kata Sandi') }}</x-forms.label>
                            <div class="relative mt-1.5">
                                <input x-model="password_confirmation" x-bind:type="showPwConf ? 'text' : 'password'" type="password" id="password_confirmation" autocomplete="new-password"
                                    class="block w-full rounded-md border bg-canvas px-3 py-2 text-sm text-ink placeholder:text-muted-soft focus:border-ink focus:ring-0 border-outline-variant pr-10"
                                    placeholder="{{ __('Kosongkan jika tidak ingin mengubah') }}" />
                                <button type="button" @click="showPwConf = !showPwConf" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-ink">
                                    <span class="material-symbols-outlined text-lg" x-text="showPwConf ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-forms.error name="form.password_confirmation" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Karyawan --}}
            <div class="rounded-xl border border-outline-variant/30 bg-canvas p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3 border-b border-outline-variant/30 pb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary-soft text-primary">
                        <span class="material-symbols-outlined text-lg">badge</span>
                    </div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">{{ __('Data Karyawan') }}</h3>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="employee_number">{{ __('Nomor Karyawan') }}</x-forms.label>
                        <x-forms.input wire:model="form.employee_number" id="employee_number" required class="mt-1.5 w-full" placeholder="{{ __('EMP-001') }}" />
                        <x-forms.error name="form.employee_number" />
                    </div>
                    <div>
                        <x-forms.label for="full_name">{{ __('Nama Lengkap') }}</x-forms.label>
                        <x-forms.input wire:model="form.full_name" id="full_name" required class="mt-1.5 w-full" placeholder="{{ __('Nama lengkap sesuai KTP') }}" />
                        <x-forms.error name="form.full_name" />
                    </div>
                    <div>
                        <x-forms.label for="nik">{{ __('NIK') }}</x-forms.label>
                        <x-forms.input x-model="nik" id="nik" maxlength="16" required class="mt-1.5 w-full" placeholder="{{ __('16 digit NIK') }}" />
                        <x-forms.error name="form.nik" />
                    </div>
                    <div>
                        <x-forms.label for="phone">{{ __('Telepon') }}</x-forms.label>
                        <x-forms.input x-model="phone" id="phone" required class="mt-1.5 w-full" placeholder="{{ __('+62xxx atau 08xxx') }}" />
                        <x-forms.error name="form.phone" />
                    </div>
                    <div>
                        <x-forms.label for="gender">{{ __('Jenis Kelamin') }}</x-forms.label>
                        <select wire:model="form.gender" id="gender" required
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            <option value="L">{{ __('Laki-laki') }}</option>
                            <option value="P">{{ __('Perempuan') }}</option>
                        </select>
                        <x-forms.error name="form.gender" />
                    </div>
                    <div>
                        <x-forms.label for="marital_status">{{ __('Status Pernikahan') }}</x-forms.label>
                        <select wire:model="form.marital_status" id="marital_status" required
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            <option value="single">{{ __('Lajang') }}</option>
                            <option value="married">{{ __('Menikah') }}</option>
                            <option value="divorced">{{ __('Cerai') }}</option>
                            <option value="widowed">{{ __('Duda/Janda') }}</option>
                        </select>
                        <x-forms.error name="form.marital_status" />
                    </div>
                    <div>
                        <x-forms.label for="blood_type">{{ __('Gol. Darah') }}</x-forms.label>
                        <select wire:model="form.blood_type" id="blood_type"
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                        <x-forms.error name="form.blood_type" />
                    </div>
                    <div>
                        <x-forms.label for="birth_date">{{ __('Tanggal Lahir') }}</x-forms.label>
                        <x-forms.input wire:model="form.birth_date" type="date" id="birth_date" required class="mt-1.5 w-full" placeholder="{{ __('YYYY-MM-DD') }}" />
                        <x-forms.error name="form.birth_date" />
                    </div>
                </div>
            </div>

            {{-- Alamat --}}
            <div class="rounded-xl border border-outline-variant/30 bg-canvas p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3 border-b border-outline-variant/30 pb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-coral-100 text-coral-600">
                        <span class="material-symbols-outlined text-lg">location_on</span>
                    </div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">{{ __('Alamat') }}</h3>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="province_id">{{ __('Provinsi') }}</x-forms.label>
                        <select wire:model.live="form.province_id" id="province_id"
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            @foreach ($provinces as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <x-forms.error name="form.province_id" />
                    </div>
                    <div wire:key="city-select-{{ $form->province_id ?? 'empty' }}">
                        <x-forms.label for="city_id">{{ __('Kota/Kabupaten') }}</x-forms.label>
                        <select wire:model.live="form.city_id" id="city_id"
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            @foreach ($cities as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <x-forms.error name="form.city_id" />
                    </div>
                    <div wire:key="district-select-{{ $form->city_id ?? 'empty' }}">
                        <x-forms.label for="district_id">{{ __('Kecamatan') }}</x-forms.label>
                        <select wire:model.live="form.district_id" id="district_id"
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <x-forms.error name="form.district_id" />
                    </div>
                    <div wire:key="village-select-{{ $form->district_id ?? 'empty' }}">
                        <x-forms.label for="village_id">{{ __('Desa/Kelurahan') }}</x-forms.label>
                        <select wire:model.live="form.village_id" id="village_id"
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            @foreach ($villages as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                        <x-forms.error name="form.village_id" />
                    </div>
=======
    <x-admin.page-shell :title="__('Edit Employee')" :description="__('Update employee information and settings.')">
        <form wire:submit="update">
            @csrf
            <div class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700/50 dark:bg-slate-800/50">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
>>>>>>> main
                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_name" value="{{ __('Full Name') }}" />
                        <x-forms.input id="edit_name" type="text" class="mt-1 block w-full" wire:model="form.name" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="edit_email" value="{{ __('Email') }}" />
                        <x-forms.input id="edit_email" type="email" class="mt-1 block w-full" wire:model="form.email" />
                        <x-forms.input-error for="form.email" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="edit_nip" value="{{ __('NIP') }}" />
                        <x-forms.input id="edit_nip" type="text" class="mt-1 block w-full" wire:model="form.nip" />
                        <x-forms.input-error for="form.nip" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_password" value="{{ __('Password') }}" />
                        <x-forms.input id="edit_password" type="password" class="mt-1 block w-full"
                            wire:model="form.password" placeholder="{{ __('Leave blank to keep current password') }}" />
                        <x-forms.input-error for="form.password" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_phone" value="{{ __('Phone') }}" />
                        <x-forms.input id="edit_phone" type="text" class="mt-1 block w-full" wire:model="form.phone" />
                        <x-forms.input-error for="form.phone" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label value="{{ __('Gender') }}" />
                        <div class="mt-3 flex gap-4">
                            <label class="inline-flex items-center">
                                <x-forms.radio name="gender" value="male" wire:model="form.gender" />
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <x-forms.radio name="gender" value="female" wire:model="form.gender" />
                                <span class="ml-2 text-sm">{{ __('Female') }}</span>
                            </label>
                        </div>
                        <x-forms.input-error for="form.gender" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-forms.label for="edit_provinsi" value="{{ __('Province') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="edit_provinsi" wire:model.live="form.provinsi_kode"
                                    placeholder="{{ __('Select Province') }}" :options="$provinces->map(fn($p) => ['id' => $p->kode, 'name' => $p->nama])" />
                            </div>
                            <x-forms.input-error for="form.provinsi_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_kabupaten" value="{{ __('Regency / City') }}" />
                            <div class="mt-1" wire:key="edit-kab-{{ $form->provinsi_kode ?? 'empty' }}">
                                <x-forms.tom-select id="edit_kabupaten" wire:model.live="form.kabupaten_kode"
                                    placeholder="{{ __('Select Regency/City') }}" :options="$regencies->map(fn($r) => ['id' => $r->kode, 'name' => $r->nama])" />
                            </div>
                            <x-forms.input-error for="form.kabupaten_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_kecamatan" value="{{ __('District') }}" />
                            <div class="mt-1" wire:key="edit-kec-{{ $form->kabupaten_kode ?? 'empty' }}">
                                <x-forms.tom-select id="edit_kecamatan" wire:model.live="form.kecamatan_kode"
                                    placeholder="{{ __('Select District') }}" :options="$districts->map(fn($d) => ['id' => $d->kode, 'name' => $d->nama])" />
                            </div>
                            <x-forms.input-error for="form.kecamatan_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_kelurahan" value="{{ __('Village') }}" />
                            <div class="mt-1" wire:key="edit-kel-{{ $form->kecamatan_kode ?? 'empty' }}">
                                <x-forms.tom-select id="edit_kelurahan" wire:model.live="form.kelurahan_kode"
                                    placeholder="{{ __('Select Village') }}" :options="$villages->map(fn($v) => ['id' => $v->kode, 'name' => $v->nama])" />
                            </div>
                            <x-forms.input-error for="form.kelurahan_kode" class="mt-2" />
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_address" value="{{ __('Address') }}" />
                        <x-forms.textarea id="edit_address" class="mt-1 block w-full" wire:model="form.address" rows="2" />
                        <x-forms.input-error for="form.address" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 space-y-4">
                        <div>
                            <x-forms.label for="edit_division" value="{{ __('Division') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="edit_division" wire:model.live="form.division_id"
                                    placeholder="{{ __('Select Division') }}" :options="App\Models\Division::all()
                                        ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                                        ->values()" />
                            </div>
                            <x-forms.input-error for="form.division_id" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_jobTitle" value="{{ __('Job Title') }}" />
                            <div class="mt-1" wire:key="edit-job-title-wrapper-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="edit_jobTitle" wire:model.live="form.job_title_id"
                                    placeholder="{{ __('Select Job Title') }}" :options="$availableJobTitles
                                        ->map(fn($j) => ['id' => $j->id, 'name' => $j->name])
                                        ->values()" />
                            </div>
                            <x-forms.input-error for="form.job_title_id" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_manager" value="{{ __('Direct Manager') }}" />
                            <div class="mt-1" wire:key="edit-manager-wrapper-{{ $form->user?->id ?? 'new' }}-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="edit_manager" wire:model.live="form.manager_id"
                                    placeholder="{{ __('No direct manager') }}" :options="$managerOptions" />
                            </div>
                            <x-forms.input-error for="form.manager_id" class="mt-2" />
                        </div>
                    </div>

                    <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div x-data="{
                            displayValue: '',
                            model: @entangle('form.basic_salary'),
                            format(value) {
                                if (!value) return '';
                                return new Intl.NumberFormat('id-ID').format(value);
                            },
                            update(event) {
                                let val = event.target.value.replace(/\./g, '');
                                if (isNaN(val)) val = 0;
                                this.model = val;
                                this.displayValue = this.format(val);
                            }
                        }" x-init="displayValue = format(model); $watch('model', value => displayValue = format(value))">
                            <x-forms.label for="edit_basic_salary" value="{{ __('Basic Salary (Rp)') }}" />
                            <x-forms.input id="edit_basic_salary" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="e.g. 5.000.000" />
                            <x-forms.input-error for="form.basic_salary" class="mt-2" />
                        </div>

                        <div x-data="{
                            displayValue: '',
                            model: @entangle('form.hourly_rate'),
                            format(value) {
                                if (!value) return '';
                                return new Intl.NumberFormat('id-ID').format(value);
                            },
                            update(event) {
                                let val = event.target.value.replace(/\./g, '');
                                if (isNaN(val)) val = 0;
                                this.model = val;
                                this.displayValue = this.format(val);
                            }
                        }" x-init="displayValue = format(model); $watch('model', value => displayValue = format(value))">
                            <x-forms.label for="edit_hourly_rate" value="{{ __('Hourly Rate (Rp)') }}" />
                            <x-forms.input id="edit_hourly_rate" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="e.g. 25.000" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Leave blank to auto-calc (Salary / 173)') }}</p>
                            <x-forms.input-error for="form.hourly_rate" class="mt-2" />
                        </div>
                    </div>

                    @if ($canManageEmployeeStatuses)
                        <div class="sm:col-span-2">
                            <x-forms.label for="edit_employment_status" value="{{ __('Employment Status') }}" />
                            <x-forms.select id="edit_employment_status" wire:model="form.employment_status"
                                class="mt-1 block w-full">
                                @if (isset($employmentStatuses[$form->employment_status]) && ! in_array($form->employment_status, $manualEmploymentStatuses, true))
                                    <option value="{{ $form->employment_status }}">{{ __($employmentStatuses[$form->employment_status]) }}</option>
                                @endif
                                @foreach ($manualEmploymentStatuses as $statusKey)
                                    <option value="{{ $statusKey }}">{{ __($employmentStatuses[$statusKey]) }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="form.employment_status" class="mt-2" />
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3 border-t border-slate-200 pt-6 dark:border-slate-700/50">
                    <x-actions.button type="submit" wire:loading.attr="disabled">
                        {{ __('Update Employee') }}
                    </x-actions.button>
                    <x-actions.secondary-button type="button" onclick="window.location.href='{{ route('admin.employees') }}'">
                        {{ __('Cancel') }}
                    </x-actions.secondary-button>
                </div>
            </div>
        </form>
    </x-admin.page-shell>
</div>
