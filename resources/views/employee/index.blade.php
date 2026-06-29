<x-layouts::app.sidebar>
    <div x-data="employeesIndex()">
        <x-page-shell title="{{ __('Employees') }}" subtitle="{{ __('Manage employee master data') }}">
            <x-slot:actions>
                @can('manage_employees')
                    <x-button variant="primary" icon="add" @click="openCreateModal()">
                        {{ __('Add Employee') }}
                    </x-button>
                    <x-button variant="secondary" icon="download" @click="exportCSV">
                        {{ __('Export') }}
                    </x-button>
                @endcan
            </x-slot:actions>

            <x-slot:toolbar>
                <x-page-toolbar search search-placeholder="{{ __('Search by name or employee number...') }}">
                    <x-slot:filters>
                        <select x-model="filters.status" @change="page = 1; fetchEmployees()"
                            class="h-10 rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="resigned">{{ __('Resigned') }}</option>
                            <option value="terminated">{{ __('Terminated') }}</option>
                            <option value="deceased">{{ __('Deceased') }}</option>
                        </select>
                        <select x-model="filters.department_id" @change="page = 1; fetchEmployees()"
                            class="h-10 rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('All Departments') }}</option>
                            <template x-for="d in departments" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <button @click="toggleView"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-dim hover:text-ink"
                            x-bind:title="view === 'table' ? '{{ __('Card view') }}' : '{{ __('Table view') }}'">
                            <span class="material-symbols-outlined text-lg" x-text="view === 'table' ? 'grid_view' : 'table_rows'"></span>
                        </button>
                    </x-slot:filters>
                </x-page-toolbar>
            </x-slot:toolbar>

            <div x-show="loading" class="py-16">
                <x-loading-skeleton mode="table" :rows="5" :cols="6" />
            </div>

            <template x-if="!loading && employees.length === 0">
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant">group</span>
                    <p class="mt-2 text-sm text-on-surface-variant">{{ __('No employees found') }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('Try adjusting your search or filter criteria') }}</p>
                </div>
            </template>

            <template x-if="!loading && employees.length > 0 && view === 'table'">
                <x-simple-table :headers="[__('Employee'), __('Department'), __('Position'), __('Status'), __('Join Date'), __('Actions')]">
                    <template x-for="e in employees" :key="e.id">
                        <tr class="transition-colors hover:bg-surface-dim">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-dim text-sm font-semibold text-on-surface-variant" x-text="e.full_name?.charAt(0)?.toUpperCase()"></div>
                                    <div class="min-w-0">
                                        <a :href="`/admin/employees/${e.id}`" class="text-sm font-medium text-ink hover:underline" x-text="e.full_name" wire:navigate></a>
                                        <p class="text-xs text-on-surface-variant" x-text="`#${e.employee_number}`"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink" x-text="e.department?.name || '-'"></td>
                            <td class="px-4 py-3 text-sm text-ink" x-text="e.position?.name || '-'"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="{
                                        'bg-success/10 text-success ring-success/20': e.status === 'active',
                                        'bg-surface-dim text-on-surface-variant ring-outline-variant/30': e.status === 'inactive' || e.status === 'deceased',
                                        'bg-warning/10 text-warning ring-warning/20': e.status === 'resigned',
                                        'bg-error/10 text-error ring-error/20': e.status === 'terminated',
                                    }"
                                    x-text="statusLabel(e.status)">
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-on-surface-variant" x-text="e.join_date || '-'"></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a :href="`/admin/employees/${e.id}`" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-on-surface-variant transition-colors hover:text-ink hover:bg-surface-container-high">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        <span class="hidden sm:inline">{{ __('View') }}</span>
                                    </a>
                                    @can('manage_employees')
                                        <button @click="openEditModal(e)" class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-on-surface-variant transition-colors hover:text-ink hover:bg-surface-container-high">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                            <span class="hidden sm:inline">{{ __('Edit') }}</span>
                                        </button>
                                        <button @click="openTerminateModal(e)" class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-on-surface-variant transition-colors hover:text-error hover:bg-error/5">
                                            <span class="material-symbols-outlined text-base">block</span>
                                            <span class="hidden sm:inline">{{ __('Terminate') }}</span>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    </template>
                </x-simple-table>
            </template>

            <template x-if="!loading && employees.length > 0 && view === 'grid'">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <template x-for="e in employees" :key="e.id">
                        <div class="rounded-xl border border-outline-variant bg-canvas p-4 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-dim text-base font-semibold text-on-surface-variant" x-text="e.full_name?.charAt(0)?.toUpperCase()"></div>
                                <div class="min-w-0 flex-1">
                                    <a :href="`/admin/employees/${e.id}`" class="text-sm font-medium text-ink hover:underline" x-text="e.full_name" wire:navigate></a>
                                    <p class="text-xs text-on-surface-variant" x-text="`#${e.employee_number} · ${e.position?.name || '-'}`"></p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="{
                                        'bg-success/10 text-success ring-success/20': e.status === 'active',
                                        'bg-surface-dim text-on-surface-variant ring-outline-variant/30': e.status === 'inactive' || e.status === 'deceased',
                                        'bg-warning/10 text-warning ring-warning/20': e.status === 'resigned',
                                        'bg-error/10 text-error ring-error/20': e.status === 'terminated',
                                    }"
                                    x-text="statusLabel(e.status)">
                                </span>
                                <span class="text-xs text-on-surface-variant" x-text="e.department?.name || ''"></span>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                @can('manage_employees')
                                    <button @click="openEditModal(e)" class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant px-3 py-1.5 text-xs font-semibold text-ink transition-colors hover:bg-surface-dim">
                                        <span class="material-symbols-outlined text-base">edit</span>
                                        {{ __('Edit') }}
                                    </button>
                                    <button @click="openTerminateModal(e)" class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant px-3 py-1.5 text-xs font-semibold text-ink transition-colors hover:bg-surface-dim">
                                        <span class="material-symbols-outlined text-base">block</span>
                                        {{ __('Terminate') }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <div x-show="!loading && employees.length > 0" class="mt-4">
                <div x-show="lastPage > 1" class="flex items-center justify-between gap-4">
                    <button @click="page = Math.max(1, page - 1); fetchEmployees()" x-bind:disabled="page <= 1"
                        class="flex items-center gap-1 rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                        {{ __('Previous') }}
                    </button>
                    <span class="text-sm text-on-surface-variant">
                        {{ __('Page') }} <span x-text="page"></span> / <span x-text="lastPage"></span>
                        ({{ __('Total') }} <span x-text="total"></span>)
                    </span>
                    <button @click="page = Math.min(lastPage, page + 1); fetchEmployees()" x-bind:disabled="page >= lastPage"
                        class="flex items-center gap-1 rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim disabled:opacity-40">
                        {{ __('Next') }}
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
            </div>
        </x-page-shell>

        {{-- Create / Edit Modal --}}
        <template x-teleport="body">
            <div x-show="createModalOpen" x-cloak @keydown.escape.window="createModalOpen = false"
                class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="createModalOpen = false"></div>
                <div class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-canvas p-6 shadow-xl">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-ink" x-text="editing ? '{{ __('Edit Employee') }}' : '{{ __('Add Employee') }}'"></h2>
                        <button @click="createModalOpen = false" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-dim hover:text-ink">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>

                    <div class="space-y-4">
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

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Employment') }}</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Company') }} *</label>
                                    <select x-model="form.company_id"
                                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Select...') }}</option>
                                        <template x-for="c in lookup.companies" :key="c.id">
                                            <option :value="c.id" x-text="c.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Branch') }} *</label>
                                    <select x-model="form.branch_id"
                                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Select...') }}</option>
                                        <template x-for="b in lookup.branches" :key="b.id">
                                            <option :value="b.id" x-text="b.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Department') }} *</label>
                                    <select x-model="form.department_id"
                                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Select...') }}</option>
                                        <template x-for="d in lookup.departments" :key="d.id">
                                            <option :value="d.id" x-text="d.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Position') }} *</label>
                                    <select x-model="form.position_id"
                                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Select...') }}</option>
                                        <template x-for="p in lookup.positions" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Manager / Supervisor') }}</label>
                                    <select x-model="form.parent_id"
                                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('None') }}</option>
                                        <template x-for="m in lookup.managers" :key="m.id">
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

                        <div x-show="formError" x-cloak class="rounded-xl bg-error/10 p-3 text-sm text-error">
                            <p x-text="formError"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-outline-variant/50 pt-4">
                        <button type="button" @click="createModalOpen = false"
                            class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="submitEmployee()" x-bind:disabled="formLoading"
                            class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                            <span x-show="!formLoading" x-text="editing ? '{{ __('Update') }}' : '{{ __('Save') }}'"></span>
                            <span x-show="formLoading" x-cloak>{{ __('Saving...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Terminate Modal --}}
        <template x-teleport="body">
            <div x-show="terminateModalOpen" x-cloak @keydown.escape.window="terminateModalOpen = false"
                class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="terminateModalOpen = false"></div>
                <div class="relative z-10 w-full max-w-sm rounded-2xl bg-canvas p-6 shadow-xl">
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-error/10">
                            <span class="material-symbols-outlined text-2xl text-error">warning</span>
                        </div>
                        <h3 class="text-lg font-semibold text-ink">{{ __('Terminate Employee') }}</h3>
                        <p class="mt-1 text-sm text-on-surface-variant">{{ __('Are you sure you want to terminate this employee? This action cannot be undone.') }}</p>

                        <div class="mt-4 w-full space-y-3 text-left">
                            <div class="rounded-lg bg-warning/10 p-3 text-sm text-warning">
                                <p class="font-medium">{{ __('Warning') }}</p>
                                <p class="mt-1 text-xs">{{ __('Terminating an employee will revoke all system access and mark their status as terminated.') }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Termination Type') }} *</label>
                                <select x-model="terminateForm.type"
                                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
<option value="dismissed">{{ __('PHK (Dismissed)') }}</option>
<option value="resign">{{ __('Resignation') }}</option>
<option value="contract_end">{{ __('Contract End') }}</option>
<option value="deceased">{{ __('Deceased') }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Reason') }} *</label>
                                <textarea x-model="terminateForm.reason" rows="3"
                                    class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink"
                                    placeholder="{{ __('Explain the reason for termination...') }}"></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Effective Date') }} *</label>
                                <input type="date" x-model="terminateForm.date"
                                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            </div>

                            <div x-show="terminateError" x-cloak class="rounded-xl bg-error/10 p-3 text-sm text-error">
                                <p x-text="terminateError"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button @click="terminateModalOpen = false"
                            class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="submitTerminate()" x-bind:disabled="terminateLoading"
                            class="rounded-xl bg-error px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                            <span x-show="!terminateLoading">{{ __('Confirm Termination') }}</span>
                            <span x-show="terminateLoading" x-cloak>{{ __('Processing...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Import — placeholder untuk V2 --}}
    </div>


</x-layouts::app.sidebar>
