<x-layouts::app.sidebar>
    <div x-data="employeeShow()">
        <x-page-shell :title="__('Employee Detail')">
            <x-slot:actions>
                <x-button variant="secondary" icon="arrow_back" href="{{ route('admin.employees.index') }}" wire:navigate>
                    {{ __('Back') }}
                </x-button>
                @can('manage_employees')
                    <x-button variant="primary" icon="edit" @click="$dispatch('open-modal', 'create-employee'); Alpine.$data(document.querySelector('[x-data=\"employeesIndex()\"]'))?.editEmployee(employeeData)">
                        {{ __('Edit') }}
                    </x-button>
                @endcan
            </x-slot:actions>

            {{-- Employee Header --}}
            <div x-show="!loading" class="rounded-xl border border-outline-variant bg-canvas p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-surface-dim text-2xl font-semibold text-on-surface-variant" x-text="employee.full_name?.charAt(0)?.toUpperCase()"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-semibold text-ink" x-text="employee.full_name"></h2>
                            <x-status-badge :tone="employee.status === 'active' ? 'success' : (employee.status === 'resigned' ? 'warning' : (employee.status === 'terminated' ? 'error' : 'neutral'))" pill x-show="true">
                                <span x-text="statusLabel(employee.status)"></span>
                            </x-status-badge>
                        </div>
                        <p class="mt-1 text-sm text-on-surface-variant" x-text="`#${employee.employee_number}`"></p>
                        <div class="mt-2 flex flex-wrap gap-4 text-sm text-on-surface-variant">
                            <span x-text="employee.position?.name || '-'"></span>
                            <span class="text-outline-variant">|</span>
                            <span x-text="employee.department?.name || '-'"></span>
                            <span class="text-outline-variant">|</span>
                            <span x-text="employee.branch?.name || '-'"></span>
                            <span class="text-outline-variant">|</span>
                            <span>{{ __('Join') }}: <span x-text="employee.join_date || '-'"></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="loading" class="py-16">
                <x-loading-skeleton mode="card" />
            </div>

            {{-- Tabs --}}
            <div x-show="!loading" class="mt-6">
                <div class="flex border-b border-outline-variant/50">
                    <button @click="tab = 'personal'" :class="tab === 'personal' ? 'border-b-2 border-ink text-ink' : 'text-on-surface-variant hover:text-ink'" class="px-4 py-3 text-sm font-medium transition-colors">{{ __('Personal') }}</button>
                    <button @click="tab = 'bank'" :class="tab === 'bank' ? 'border-b-2 border-ink text-ink' : 'text-on-surface-variant hover:text-ink'" class="px-4 py-3 text-sm font-medium transition-colors">{{ __('Bank & Tax') }}</button>
                    <button @click="tab = 'family'" :class="tab === 'family' ? 'border-b-2 border-ink text-ink' : 'text-on-surface-variant hover:text-ink'" class="px-4 py-3 text-sm font-medium transition-colors">{{ __('Family') }}</button>
                    <button @click="tab = 'documents'" :class="tab === 'documents' ? 'border-b-2 border-ink text-ink' : 'text-on-surface-variant hover:text-ink'" class="px-4 py-3 text-sm font-medium transition-colors">{{ __('Documents') }}</button>
                </div>

                {{-- Personal Tab --}}
                <div x-show="tab === 'personal'" class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Gender') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="genderLabel(employee.gender)"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Birth Date') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="employee.birth_date || '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Marital Status') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="maritalLabel(employee.marital_status)"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Blood Type') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="employee.blood_type || '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Education') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="educationLabel(employee.education_level)"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Employment Type') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="employmentLabel(employee.employment_type)"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Shift') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="employee.shift?.name || '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Manager') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="employee.manager?.full_name || '-'"></p>
                    </div>
                </div>

                {{-- Bank & Tax Tab --}}
                <div x-show="tab === 'bank'" class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Bank Name') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="employee.bank_name || '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Salary Type') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="salaryLabel(employee.salary_type)"></p>
                    </div>
                    <div x-show="hasPiiAccess">
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('NIK') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="pii.nik || '-'"></p>
                    </div>
                    <div x-show="hasPiiAccess">
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('NPWP') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="pii.npwp || '-'"></p>
                    </div>
                    <div x-show="hasPiiAccess">
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Phone') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="pii.phone || '-'"></p>
                    </div>
                    <div x-show="hasPiiAccess">
                        <p class="text-xs font-medium text-on-surface-variant">{{ __('Bank Account') }}</p>
                        <p class="mt-1 text-sm text-ink" x-text="pii.bank_account_number || '-'"></p>
                    </div>
                </div>

                {{-- Family Tab --}}
                <div x-show="tab === 'family'" class="mt-4">
                    <div x-show="families.length === 0" class="rounded-xl border border-dashed border-outline-variant p-8 text-center text-sm text-on-surface-variant">
                        {{ __('No family data recorded') }}
                    </div>
                    <div x-show="families.length > 0" class="space-y-3">
                        <template x-for="f in families" :key="f.id">
                            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-ink" x-text="f.full_name"></p>
                                        <p class="text-xs text-on-surface-variant" x-text="relationshipLabel(f.relationship)"></p>
                                    </div>
                                    <span class="text-xs text-on-surface-variant" x-text="f.birth_date || ''"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Documents Tab --}}
                <div x-show="tab === 'documents'" class="mt-4">
                    <div class="rounded-xl border border-dashed border-outline-variant p-8 text-center text-sm text-on-surface-variant">
                        {{ __('No documents uploaded') }}
                    </div>
                </div>
            </div>
        </x-page-shell>
    </div>

    <script>
        function employeeShow() {
            return {
                employee: @json($employee->load(['user:id,email', 'branch:id,name', 'department:id,name', 'position:id,name,grade,basic_salary', 'shift:id,name', 'manager:id,full_name'])),
                pii: {},
                families: @json($employee->families ?? []),
                loading: false,
                hasPiiAccess: @json(auth()->user()?.can('viewPii', $employee) ?? false),
                tab: 'personal',

                init() {
                    if (this.hasPiiAccess) {
                        this.fetchPii();
                    }
                },

                async fetchPii() {
                    try {
                        const res = await fetch(`/api/v1/employees/${this.employee.id}/pii`);
                        const json = await res.json();
                        this.pii = json.data || {};
                    } catch (e) {
                        console.error('Failed to load PII', e);
                    }
                },

                statusLabel(status) {
                    const labels = {
                        active: '{{ __('Active') }}',
                        inactive: '{{ __('Inactive') }}',
                        resigned: '{{ __('Resigned') }}',
                        terminated: '{{ __('Terminated') }}',
                        deceased: '{{ __('Deceased') }}',
                    };
                    return labels[status] || status;
                },

                genderLabel(g) {
                    return g === 'L' ? '{{ __('Male') }}' : g === 'P' ? '{{ __('Female') }}' : '-';
                },

                maritalLabel(m) {
                    const labels = {
                        single: '{{ __('Single') }}',
                        married: '{{ __('Married') }}',
                        divorced: '{{ __('Divorced') }}',
                        widowed: '{{ __('Widowed') }}',
                    };
                    return labels[m] || m || '-';
                },

                educationLabel(e) {
                    const labels = {
                        sd: 'SD', smp: 'SMP', sma: 'SMA', smk: 'SMK',
                        diploma: '{{ __('Diploma') }}', bachelor: '{{ __('Bachelor') }}',
                        master: '{{ __('Master') }}', doctorate: '{{ __('Doctorate') }}',
                        other: '{{ __('Other') }}',
                    };
                    return labels[e] || e || '-';
                },

                employmentLabel(e) {
                    const labels = {
                        permanent: '{{ __('Permanent') }}',
                        contract: '{{ __('Contract') }}',
                        probation: '{{ __('Probation') }}',
                        intern: '{{ __('Intern') }}',
                    };
                    return labels[e] || e || '-';
                },

                salaryLabel(s) {
                    const labels = {
                        monthly: '{{ __('Monthly') }}',
                        daily: '{{ __('Daily') }}',
                        hourly: '{{ __('Hourly') }}',
                    };
                    return labels[s] || s || '-';
                },

                relationshipLabel(r) {
                    const labels = {
                        spouse: '{{ __('Spouse') }}',
                        child: '{{ __('Child') }}',
                        parent: '{{ __('Parent') }}',
                        sibling: '{{ __('Sibling') }}',
                    };
                    return labels[r] || r || '-';
                },
            };
        }
    </script>
</x-layouts::app.sidebar>
