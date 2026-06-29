<x-form-modal name="create-employee" size="2xl">
    <x-slot:title>
        <span x-text="editing ? '{{ __('Edit Employee') }}' : '{{ __('Add Employee') }}'"></span>
    </x-slot:title>

    <div class="space-y-5">
        {{-- Personal Information --}}
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Account') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-forms.input name="name" label="{{ __('Name') }}" x-model="form.name" required />
                <x-forms.input name="email" label="{{ __('Email') }}" type="email" x-model="form.email" required />
            </div>
            <div x-show="!editing" class="mt-4">
                <x-forms.input name="password" label="{{ __('Password') }}" type="password" x-model="form.password" required />
            </div>
        </div>

        <x-sections.section-border />

        {{-- Employee Details --}}
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Employee Details') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-forms.input name="employee_number" label="{{ __('Employee Number') }}" x-model="form.employee_number" required />
                <x-forms.input name="full_name" label="{{ __('Full Name') }}" x-model="form.full_name" required />
                <x-forms.input name="nik" label="{{ __('NIK') }}" x-model="form.nik" maxlength="16" required />
                <x-forms.input name="phone" label="{{ __('Phone') }}" x-model="form.phone" required />

                <x-forms.select name="gender" x-model="form.gender" required
                    :options="['' => __('Select...'), 'L' => __('Male'), 'P' => __('Female')]" />

                <x-forms.select name="marital_status" x-model="form.marital_status" required
                    :options="['' => __('Select...'), 'single' => __('Single'), 'married' => __('Married'), 'divorced' => __('Divorced'), 'widowed' => __('Widowed')]" />

                <x-forms.select name="blood_type" x-model="form.blood_type"
                    :options="['' => __('Select...'), 'A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-']" />

                <x-forms.input name="birth_date" label="{{ __('Birth Date') }}" type="date" x-model="form.birth_date" required />
            </div>
        </div>

        <x-sections.section-border />

        {{-- Employment --}}
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Employment') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-forms.label for="company_id" required>{{ __('Company') }}</x-forms.label>
                    <select x-model="form.company_id" id="company_id" name="company_id" required
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink focus:border-ink focus:ring-0">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="c in companies" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <x-forms.label for="branch_id" required>{{ __('Branch') }}</x-forms.label>
                    <select x-model="form.branch_id" id="branch_id" name="branch_id" required
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink focus:border-ink focus:ring-0">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="b in branches" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <x-forms.label for="department_id" required>{{ __('Department') }}</x-forms.label>
                    <select x-model="form.department_id" id="department_id" name="department_id" required
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink focus:border-ink focus:ring-0">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="d in departments" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <x-forms.label for="position_id" required>{{ __('Position') }}</x-forms.label>
                    <select x-model="form.position_id" id="position_id" name="position_id" required
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink focus:border-ink focus:ring-0">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="p in positions" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <x-forms.label for="parent_id">{{ __('Manager / Supervisor') }}</x-forms.label>
                    <select x-model="form.parent_id" id="parent_id" name="parent_id"
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink focus:border-ink focus:ring-0">
                        <option value="">{{ __('None') }}</option>
                        <template x-for="m in managers" :key="m.id">
                            <option :value="m.id" x-text="m.full_name"></option>
                        </template>
                    </select>
                </div>

                <x-forms.select name="employment_type" x-model="form.employment_type" required
                    :options="['' => __('Select...'), 'permanent' => __('Permanent'), 'contract' => __('Contract'), 'probation' => __('Probation'), 'intern' => __('Intern')]" />

                <x-forms.select name="salary_type" x-model="form.salary_type" required
                    :options="['' => __('Select...'), 'monthly' => __('Monthly'), 'daily' => __('Daily'), 'hourly' => __('Hourly')]" />

                <x-forms.input name="join_date" label="{{ __('Join Date') }}" type="date" x-model="form.join_date" required />
            </div>
        </div>

        <x-sections.section-border />

        {{-- Education --}}
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Education') }}</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-forms.select name="education_level" x-model="form.education_level" required
                    :options="['' => __('Select...'), 'sd' => __('SD / Sederajat'), 'smp' => __('SMP / Sederajat'), 'sma' => __('SMA / Sederajat'), 'smk' => __('SMK / Sederajat'), 'diploma' => __('Diploma (D1-D4)'), 'bachelor' => __('Sarjana (S1)'), 'master' => __('Magister (S2)'), 'doctorate' => __('Doktor (S3)'), 'other' => __('Lainnya')]" />

                <x-forms.input name="institution_name" label="{{ __('Institution') }}" x-model="form.institution_name" required />

                <x-forms.input name="graduation_year" label="{{ __('Graduation Year') }}" type="number" x-model="form.graduation_year" min="1950" required />
            </div>
        </div>

        {{-- Error Display --}}
        <div x-show="formError" x-cloak class="rounded-xl bg-error/10 p-3 text-sm text-error">
            <p x-text="formError"></p>
        </div>
    </div>

    <x-slot:actions>
        <button type="button" @click="$dispatch('close-modal', 'create-employee')"
            class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
            {{ __('Cancel') }}
        </button>
        <button @click="submitEmployee"
            x-bind:disabled="formLoading"
            class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
            <span x-show="!formLoading" x-text="editing ? '{{ __('Update') }}' : '{{ __('Save') }}'"></span>
            <span x-show="formLoading" x-cloak>{{ __('Saving...') }}</span>
        </button>
    </x-slot:actions>
</x-form-modal>


