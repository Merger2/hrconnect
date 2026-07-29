<div>
    <x-admin.page-shell :title="__('Edit Employee')" :description="__('Update employee information and settings.')">
        <form wire:submit="update">
            @csrf
            <div class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700/50 dark:bg-slate-800/50">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
