<div>
    <x-admin.page-shell :title="__('Create Employee')" :description="__('Add a new employee to the organization.')">
        <form wire:submit="store">
            @csrf
            @error('form.email')
                <p {{ $attributes->merge(['class' => 'mb-4 rounded-lg border border-error/30 bg-error/10 p-3 text-sm text-error']) }}>{{ $message }}</p>
            @enderror
            <div class="space-y-6 rounded-xl border border-slate-200 bg-white p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-forms.label for="create_name" value="{{ __('Full Name') }}" />
                        <x-forms.input id="create_name" type="text" class="mt-1 block w-full" wire:model="form.name" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="create_email" value="{{ __('Email') }}" />
                        <x-forms.input id="create_email" type="email" class="mt-1 block w-full" wire:model="form.email" />
                        <x-forms.input-error for="form.email" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="create_nip" value="{{ __('NIP') }}" />
                        <x-forms.input id="create_nip" type="text" class="mt-1 block w-full" wire:model="form.nip" />
                        <x-forms.input-error for="form.nip" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="create_password" value="{{ __('Password') }}" />
                        <x-forms.input id="create_password" type="password" class="mt-1 block w-full"
                            wire:model="form.password" />
                        <x-forms.input-error for="form.password" class="mt-2" />
                        <x-forms.label for="create_password_confirmation" value="{{ __('Confirm Password') }}" class="mt-3" />
                        <x-forms.input id="create_password_confirmation" type="password" class="mt-1 block w-full"
                            wire:model="form.password_confirmation" />
                        <x-forms.input-error for="form.password_confirmation" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="create_phone" value="{{ __('Phone') }}" />
                        <x-forms.input id="create_phone" type="text" class="mt-1 block w-full" wire:model="form.phone" />
                        <x-forms.input-error for="form.phone" class="mt-2" />
                    </div>

                    <div>
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

                    <div>
                        <x-forms.label for="create_join_date" value="{{ __('Join Date') }}" />
                        <x-forms.input id="create_join_date" type="date" class="mt-1 block w-full" wire:model="form.join_date" />
                        <x-forms.input-error for="form.join_date" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="create_birth_date" value="{{ __('Birth Date') }}" />
                        <x-forms.input id="create_birth_date" type="date" class="mt-1 block w-full" wire:model="form.birth_date" max="{{ now()->subDay()->format('Y-m-d') }}" />
                        <x-forms.input-error for="form.birth_date" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="create_employment_type" value="{{ __('Employment Type') }}" />
                        <x-forms.select id="create_employment_type" wire:model="form.employment_type" class="mt-1 block w-full">
                            <option value="permanent">{{ __('Permanent') }}</option>
                            <option value="contract">{{ __('Contract') }}</option>
                            <option value="intern">{{ __('Intern') }}</option>
                        </x-forms.select>
                        <x-forms.input-error for="form.employment_type" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="create_marital_status" value="{{ __('Marital Status') }}" />
                        <x-forms.select id="create_marital_status" wire:model="form.marital_status" class="mt-1 block w-full">
                            @foreach (\App\Enums\MaritalStatus::cases() as $marital)
                                <option value="{{ $marital->value }}" @selected($form->marital_status === $marital->value)>{{ $marital->label() }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="form.marital_status" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-forms.label for="create_provinsi" value="{{ __('Province') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="create_provinsi" wire:model.live="form.provinsi_kode"
                                    placeholder="{{ __('Select Province') }}" :options="$provinces->map(fn($p) => ['id' => $p->kode, 'name' => $p->nama])" />
                            </div>
                            <x-forms.input-error for="form.provinsi_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_kabupaten" value="{{ __('Regency / City') }}" />
                            <div class="mt-1" wire:key="create-kab-{{ $form->provinsi_kode ?? 'empty' }}">
                                <x-forms.tom-select id="create_kabupaten" wire:model.live="form.kabupaten_kode"
                                    placeholder="{{ __('Select Regency/City') }}" :options="$regencies->map(fn($r) => ['id' => $r->kode, 'name' => $r->nama])" />
                            </div>
                            <x-forms.input-error for="form.kabupaten_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_kecamatan" value="{{ __('District') }}" />
                            <div class="mt-1" wire:key="create-kec-{{ $form->kabupaten_kode ?? 'empty' }}">
                                <x-forms.tom-select id="create_kecamatan" wire:model.live="form.kecamatan_kode"
                                    placeholder="{{ __('Select District') }}" :options="$districts->map(fn($d) => ['id' => $d->kode, 'name' => $d->nama])" />
                            </div>
                            <x-forms.input-error for="form.kecamatan_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_kelurahan" value="{{ __('Village') }}" />
                            <div class="mt-1" wire:key="create-kel-{{ $form->kecamatan_kode ?? 'empty' }}">
                                <x-forms.tom-select id="create_kelurahan" wire:model.live="form.kelurahan_kode"
                                    placeholder="{{ __('Select Village') }}" :options="$villages->map(fn($v) => ['id' => $v->kode, 'name' => $v->nama])" />
                            </div>
                            <x-forms.input-error for="form.kelurahan_kode" class="mt-2" />
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="create_address" value="{{ __('Address') }}" />
                        <x-forms.textarea id="create_address" class="mt-1 block w-full" wire:model="form.address" rows="2" />
                        <x-forms.input-error for="form.address" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 space-y-4">
                        <div>
                            <x-forms.label for="create_division" value="{{ __('Division') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="create_division" wire:model.live="form.division_id"
                                    placeholder="{{ __('Select Division') }}" :options="App\Models\Division::all()
                                        ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                                        ->values()" />
                            </div>
                            <x-forms.input-error for="form.division_id" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_position" value="{{ __('Position') }}" />
                            <div class="mt-1" wire:key="create-position-wrapper-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="create_position" wire:model.live="form.position_id"
                                    placeholder="{{ __('Select Position') }}" :options="$availablePositions
                                        ->map(fn($j) => ['id' => $j->id, 'name' => $j->name])
                                        ->values()" />
                            </div>
                            <x-forms.input-error for="form.position_id" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_manager" value="{{ __('Direct Manager') }}" />
                            <div class="mt-1" wire:key="create-manager-wrapper-{{ $form->user?->id ?? 'new' }}-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="create_manager" wire:model.live="form.manager_id"
                                    placeholder="{{ __('No direct manager') }}" :options="$managerOptions" />
                            </div>
                            <x-forms.input-error for="form.manager_id" class="mt-2" />
                        </div>
                    </div>

                    <div class="sm:col-span-2">
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
                            <x-forms.label for="create_basic_salary" value="{{ __('Basic Salary (Rp)') }}" />
                            <x-forms.input id="create_basic_salary" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="{{ __('e.g. 5.000.000') }}" />
                            <x-forms.input-error for="form.basic_salary" class="mt-2" />
                        </div>
                    </div>

                    <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-forms.label for="create_education_level" value="{{ __('Education Level') }}" />
                            <x-forms.select id="create_education_level" wire:model="form.education_level" class="mt-1 block w-full">
                                <option value="sd">{{ __('SD / Sederajat') }}</option>
                                <option value="smp">{{ __('SMP / Sederajat') }}</option>
                                <option value="sma">{{ __('SMA / Sederajat') }}</option>
                                <option value="smk">{{ __('SMK / Sederajat') }}</option>
                                <option value="diploma">{{ __('Diploma (D1-D4)') }}</option>
                                <option value="bachelor">{{ __('Sarjana (S1)') }}</option>
                                <option value="master">{{ __('Magister (S2)') }}</option>
                                <option value="doctorate">{{ __('Doktor (S3)') }}</option>
                                <option value="other">{{ __('Lainnya') }}</option>
                            </x-forms.select>
                            <x-forms.input-error for="form.education_level" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_institution_name" value="{{ __('Institution') }}" />
                            <x-forms.input id="create_institution_name" type="text" class="mt-1 block w-full" wire:model="form.institution_name" />
                            <x-forms.input-error for="form.institution_name" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_graduation_year" value="{{ __('Graduation Year') }}" />
                            <x-forms.input id="create_graduation_year" type="number" class="mt-1 block w-full" wire:model="form.graduation_year" min="1970" max="{{ now()->year }}" />
                            <x-forms.input-error for="form.graduation_year" class="mt-2" />
                        </div>
                    </div>

                    @if ($canManageEmployeeStatuses)
                        <div class="sm:col-span-2">
                            <x-forms.label for="create_employment_status" value="{{ __('Employment Status') }}" />
                            {{-- Karyawan baru selalu Active (lifecycle-managed) — form edit
                                 salah salin ke create menampilkan status penghapusan akun. --}}
                            <x-forms.input id="create_employment_status" type="text" value="{{ __($employmentStatuses[\App\Models\Employee::EMPLOYMENT_STATUS_ACTIVE]) }}"
                                disabled class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('New employees always start as Active.') }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                    <x-actions.button type="submit" wire:loading.attr="disabled">
                        {{ __('Create Employee') }}
                    </x-actions.button>
                    <x-actions.secondary-button type="button" onclick="window.location.href='{{ route('admin.employees') }}'">
                        {{ __('Cancel') }}
                    </x-actions.secondary-button>
                </div>
            </div>
        </form>
    </x-admin.page-shell>
</div>