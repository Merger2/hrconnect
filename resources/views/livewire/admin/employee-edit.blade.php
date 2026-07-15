<div>
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
                    <div class="sm:col-span-2">
                        <x-forms.label for="address_detail">{{ __('Detail Alamat') }}</x-forms.label>
                        <textarea wire:model="form.address_detail" id="address_detail" rows="2" placeholder="{{ __('Rt/Rw, nama jalan, nomor rumah') }}"
                            class="mt-1.5 w-full rounded-md border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink"></textarea>
                        <x-forms.error name="form.address_detail" />
                    </div>
                </div>
            </div>

            {{-- Kepegawaian --}}
            <div class="rounded-xl border border-outline-variant/30 bg-canvas p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3 border-b border-outline-variant/30 pb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary-soft text-primary">
                        <span class="material-symbols-outlined text-lg">work</span>
                    </div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">{{ __('Kepegawaian') }}</h3>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="company_id">{{ __('Perusahaan') }}</x-forms.label>
                        <x-forms.tom-select wire:model.live="form.company_id" :options="$companies->pluck('name', 'id')" :placeholder="__('Pilih perusahaan')" id="company_id" />
                        <x-forms.error name="form.company_id" />
                    </div>
                    <div wire:key="branch-select">
                        <x-forms.label for="branch_id">{{ __('Cabang') }}</x-forms.label>
                        <x-forms.tom-select wire:model.live="form.branch_id" :options="$branches->pluck('name', 'id')" :placeholder="__('Pilih cabang')" id="branch_id" />
                        <x-forms.error name="form.branch_id" />
                    </div>
                    <div>
                        <x-forms.label for="department_id">{{ __('Departemen') }}</x-forms.label>
                        <x-forms.tom-select wire:model.live="form.department_id" :options="$departments->pluck('name', 'id')" :placeholder="__('Pilih departemen')" id="department_id" />
                        <x-forms.error name="form.department_id" />
                    </div>
                    <div wire:key="position-select">
                        <x-forms.label for="position_id">{{ __('Jabatan') }}</x-forms.label>
                        <x-forms.tom-select wire:model.live="form.position_id" :options="$positions->pluck('name', 'id')" :placeholder="__('Pilih jabatan')" id="position_id" />
                        <x-forms.error name="form.position_id" />
                    </div>
                    <div>
                        <x-forms.label for="parent_id">{{ __('Atasan Langsung') }}</x-forms.label>
                        <x-forms.tom-select wire:model="form.parent_id" :options="$managers->pluck('full_name', 'id')" :placeholder="__('Tidak ada')" id="parent_id" />
                        <x-forms.error name="form.parent_id" />
                    </div>
                    <div>
                        <x-forms.label for="employment_type">{{ __('Jenis Kepegawaian') }}</x-forms.label>
                        <select wire:model="form.employment_type" id="employment_type" required
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            <option value="permanent">{{ __('Tetap') }}</option>
                            <option value="contract">{{ __('Kontrak') }}</option>
                            <option value="probation">{{ __('Percobaan') }}</option>
                            <option value="intern">{{ __('Magang') }}</option>
                        </select>
                        <x-forms.error name="form.employment_type" />
                    </div>
                    <div>
                        <x-forms.label for="salary_type">{{ __('Tipe Gaji') }}</x-forms.label>
                        <select wire:model="form.salary_type" id="salary_type" required
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            <option value="monthly">{{ __('Bulanan') }}</option>
                            <option value="daily">{{ __('Harian') }}</option>
                            <option value="hourly">{{ __('Per Jam') }}</option>
                        </select>
                        <x-forms.error name="form.salary_type" />
                    </div>
                    <div>
                        <x-forms.label for="join_date">{{ __('Tanggal Masuk') }}</x-forms.label>
                        <x-forms.input wire:model="form.join_date" type="date" id="join_date" required class="mt-1.5 w-full" placeholder="{{ __('YYYY-MM-DD') }}" />
                        <x-forms.error name="form.join_date" />
                    </div>
                </div>
            </div>

            {{-- Pendidikan --}}
            <div class="rounded-xl border border-outline-variant/30 bg-canvas p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3 border-b border-outline-variant/30 pb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-coral-100 text-coral-600">
                        <span class="material-symbols-outlined text-lg">school</span>
                    </div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">{{ __('Pendidikan') }}</h3>
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <x-forms.label for="education_level">{{ __('Jenjang') }}</x-forms.label>
                        <select wire:model="form.education_level" id="education_level" required
                            class="mt-1.5 h-10 w-full rounded-md border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Pilih...') }}</option>
                            @foreach (App\Enums\EducationLevel::cases() as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                        <x-forms.error name="form.education_level" />
                    </div>
                    <div>
                        <x-forms.label for="institution_name">{{ __('Institusi') }}</x-forms.label>
                        <x-forms.input wire:model="form.institution_name" id="institution_name" required class="mt-1.5 w-full" placeholder="{{ __('Nama universitas/sekolah') }}" />
                        <x-forms.error name="form.institution_name" />
                    </div>
                    <div>
                        <x-forms.label for="graduation_year">{{ __('Tahun Lulus') }}</x-forms.label>
                        <x-forms.input wire:model="form.graduation_year" type="number" id="graduation_year" min="1950" max="{{ date('Y') }}" required class="mt-1.5 w-full" placeholder="{{ date('Y') }}" />
                        <x-forms.error name="form.graduation_year" />
                    </div>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="flex items-center justify-end gap-3 rounded-xl border border-outline-variant/30 bg-cloud p-4">
                <a href="{{ route('admin.employees.show', $employee) }}" wire:navigate>
                    <x-button variant="outline-coral" type="button">
                        <span class="material-symbols-outlined text-lg">close</span>
                        {{ __('Batal') }}
                    </x-button>
                </a>
                <x-button type="submit" variant="primary">
                    {{ __('Perbarui') }}
                </x-button>
            </div>
        </form>
    </div>
</div>
