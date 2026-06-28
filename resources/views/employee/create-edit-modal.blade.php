<x-form-modal name="create-employee" size="2xl">
    <x-slot:title>
        <span x-text="editing ? '{{ __('Edit Employee') }}' : '{{ __('Add Employee') }}'"></span>
    </x-slot:title>

    <div class="space-y-4">
        {{-- Personal Information --}}
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Account') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Name') }} *</label>
                    <input type="text" x-model="form.name"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Email') }} *</label>
                    <input type="email" x-model="form.email"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
            </div>
            <div x-show="!editing" class="mt-4">
                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Password') }} *</label>
                <input type="password" x-model="form.password"
                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
            </div>
        </div>

        <hr class="border-outline-variant/50">

        {{-- Employee Details --}}
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Employee Details') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Employee Number') }} *</label>
                    <input type="text" x-model="form.employee_number"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Full Name') }} *</label>
                    <input type="text" x-model="form.full_name"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('NIK') }} *</label>
                    <input type="text" x-model="form.nik" maxlength="16"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Phone') }} *</label>
                    <input type="text" x-model="form.phone"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Gender') }} *</label>
                    <select x-model="form.gender"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="L">{{ __('Male') }}</option>
                        <option value="P">{{ __('Female') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Marital Status') }} *</label>
                    <select x-model="form.marital_status"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="single">{{ __('Single') }}</option>
                        <option value="married">{{ __('Married') }}</option>
                        <option value="divorced">{{ __('Divorced') }}</option>
                        <option value="widowed">{{ __('Widowed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Blood Type') }}</label>
                    <select x-model="form.blood_type"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Birth Date') }} *</label>
                    <input type="date" x-model="form.birth_date"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
            </div>
        </div>

        <hr class="border-outline-variant/50">

        {{-- Employment --}}
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Employment') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Company') }} *</label>
                    <select x-model="form.company_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="c in companies" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Branch') }} *</label>
                    <select x-model="form.branch_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="b in branches" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Department') }} *</label>
                    <select x-model="form.department_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="d in departments" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Position') }} *</label>
                    <select x-model="form.position_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <template x-for="p in positions" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Manager / Supervisor') }}</label>
                    <select x-model="form.parent_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('None') }}</option>
                        <template x-for="m in managers" :key="m.id">
                            <option :value="m.id" x-text="m.full_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Employment Type') }} *</label>
                    <select x-model="form.employment_type"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="permanent">{{ __('Permanent') }}</option>
                        <option value="contract">{{ __('Contract') }}</option>
                        <option value="probation">{{ __('Probation') }}</option>
                        <option value="intern">{{ __('Intern') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Salary Type') }} *</label>
                    <select x-model="form.salary_type"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="daily">{{ __('Daily') }}</option>
                        <option value="hourly">{{ __('Hourly') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Join Date') }} *</label>
                    <input type="date" x-model="form.join_date"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
            </div>
        </div>

        <hr class="border-outline-variant/50">

        {{-- Education --}}
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Education') }}</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Education Level') }} *</label>
                    <select x-model="form.education_level"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="sd">{{ __('SD / Sederajat') }}</option>
                        <option value="smp">{{ __('SMP / Sederajat') }}</option>
                        <option value="sma">{{ __('SMA / Sederajat') }}</option>
                        <option value="smk">{{ __('SMK / Sederajat') }}</option>
                        <option value="diploma">{{ __('Diploma (D1-D4)') }}</option>
                        <option value="bachelor">{{ __('Sarjana (S1)') }}</option>
                        <option value="master">{{ __('Magister (S2)') }}</option>
                        <option value="doctorate">{{ __('Doktor (S3)') }}</option>
                        <option value="other">{{ __('Lainnya') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Institution') }} *</label>
                    <input type="text" x-model="form.institution_name"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Graduation Year') }} *</label>
                    <input type="number" x-model="form.graduation_year" min="1950" :max="new Date().getFullYear()"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                </div>
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

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('createEmployeeForm', () => ({
            form: {
                name: '', email: '', password: '',
                employee_number: '', full_name: '', nik: '', phone: '',
                gender: '', marital_status: '', blood_type: '',
                birth_date: '', join_date: '',
                company_id: '', branch_id: '', department_id: '', position_id: '',
                parent_id: '', employment_type: '', salary_type: '',
                education_level: '', institution_name: '', graduation_year: '',
            },
            formError: '',
            formLoading: false,
            companies: [],
            branches: [],
            departments: [],
            positions: [],
            managers: [],
            editing: null,

            init() {
                this.fetchLookups();
                this.$watch('editing', (val) => {
                    if (val) {
                        Object.assign(this.form, {
                            name: val.email?.split('@')[0] || '',
                            email: val.email || '',
                            password: '',
                            employee_number: val.employee_number || '',
                            full_name: val.full_name || '',
                            nik: '', phone: '',
                            gender: val.gender || '',
                            marital_status: val.marital_status || '',
                            blood_type: val.blood_type || '',
                            birth_date: val.birth_date || '',
                            join_date: val.join_date || '',
                            company_id: val.company_id || val.branch?.company_id || '',
                            branch_id: val.branch?.id || '',
                            department_id: val.department?.id || '',
                            position_id: val.position?.id || '',
                            parent_id: val.manager?.id || '',
                            employment_type: val.employment_type || '',
                            salary_type: val.salary_type || '',
                            education_level: val.education_level || '',
                            institution_name: val.institution_name || '',
                            graduation_year: val.graduation_year || '',
                        });
                    } else {
                        this.resetForm();
                    }
                });
            },

            resetForm() {
                this.form = {
                    name: '', email: '', password: '',
                    employee_number: '', full_name: '', nik: '', phone: '',
                    gender: '', marital_status: '', blood_type: '',
                    birth_date: '', join_date: '',
                    company_id: '', branch_id: '', department_id: '', position_id: '',
                    parent_id: '', employment_type: '', salary_type: '',
                    education_level: '', institution_name: '', graduation_year: '',
                };
                this.formError = '';
            },

            async fetchLookups() {
                try {
                    const [cRes, bRes, dRes, pRes, mRes] = await Promise.all([
                        fetch('/api/v1/companies?per_page=200'),
                        fetch('/api/v1/branches?per_page=200'),
                        fetch('/api/v1/departments?per_page=200'),
                        fetch('/api/v1/positions?per_page=200'),
                        fetch('/api/v1/employees?per_page=200'),
                    ]);
                    this.companies = (await cRes.json()).data || [];
                    this.branches = (await bRes.json()).data || [];
                    this.departments = (await dRes.json()).data || [];
                    this.positions = (await pRes.json()).data || [];
                    this.managers = (await mRes.json()).data || [];
                } catch (e) {
                    console.error('Failed to load lookup data', e);
                }
            },

            async submitEmployee() {
                this.formError = '';
                this.formLoading = true;
                try {
                    const isEdit = this.editing?.id;
                    const url = isEdit ? `/api/v1/employees/${this.editing.id}` : '/api/v1/employees';
                    const method = isEdit ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(this.form),
                    });

                    if (!res.ok) {
                        const err = await res.json();
                        this.formError = err.message || Object.values(err.errors || {}).flat().join(', ');
                        return;
                    }

                    this.$dispatch('close-modal', 'create-employee');
                    this.resetForm();
                    this.editing = null;
                    if (window.employeesIndexInstance) {
                        window.employeesIndexInstance.fetchEmployees();
                    }
                } catch (e) {
                    this.formError = '{{ __('An error occurred') }}';
                } finally {
                    this.formLoading = false;
                }
            },
        }));
    });
</script>
