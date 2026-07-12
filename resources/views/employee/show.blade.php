<x-layouts::app.sidebar :title="__('Detail Karyawan')">
    <div x-data="employeeShow()">
        <x-page-shell :title="__('Informasi Karyawan')" :subtitle="__('Data detail karyawan')">
            <x-slot:actions>
                @can('manage_employees')
                    <x-button variant="primary" icon="edit" href="{{ route('admin.employees.edit', $employee) }}" wire:navigate>
                        {{ __('Edit') }}
                    </x-button>
                @endcan
            </x-slot:actions>

        {{-- Loading --}}
        <div x-show="loading" class="py-12">
            <x-loading-skeleton mode="card" />
        </div>

        {{-- Header Card --}}
        <div x-show="!loading" class="rounded-xl border border-outline-variant bg-canvas p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <template x-if="employee.photo_url">
                    <img :src="employee.photo_url" alt=""
                         class="h-16 w-16 shrink-0 rounded-xl border border-outline-variant bg-surface-dim object-cover shadow-sm">
                </template>
                <template x-if="!employee.photo_url">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-surface-dim text-xl font-semibold text-on-surface-variant shadow-sm"
                         x-text="employee.full_name?.charAt(0)?.toUpperCase()"></div>
                </template>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-ink" x-text="employee.full_name"></h2>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                            :class="statusClass(employee.status)"
                            x-text="statusLabel(employee.status)"></span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-info/10 text-info ring-info/20"
                            x-show="employee.employment_type"
                            x-text="employmentLabel(employee.employment_type)"></span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-surface-dim text-on-surface-variant ring-outline-variant/30"
                            x-show="employee.position?.name"
                            x-text="employee.position.name"></span>
                    </div>
                    <p class="mt-1 text-sm text-on-surface-variant" x-text="`#${employee.employee_number}`"></p>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2">
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-on-surface-variant">{{ __('Departemen') }}</p>
                            <p class="mt-0.5 text-sm font-medium text-ink" x-text="employee.department?.name || '-'"></p>
                        </div>
                        <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2">
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-on-surface-variant">{{ __('Cabang') }}</p>
                            <p class="mt-0.5 text-sm font-medium text-ink" x-text="employee.branch?.name || '-'"></p>
                        </div>
                        <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2">
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-on-surface-variant">{{ __('Bergabung') }}</p>
                            <p class="mt-0.5 text-sm font-medium text-ink" x-text="employee.join_date || '-'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Cards --}}
        <div x-show="!loading" class="space-y-4">
            {{-- Personal --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <h4 class="text-sm font-semibold text-ink">{{ __('Informasi Pribadi') }}</h4>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Jenis Kelamin') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.gender || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('NIK') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="hasPiiAccess ? employee.nik : employee.nik_masked || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Telepon') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="hasPiiAccess ? employee.phone : employee.phone_masked || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Status') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.marital_status || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Gol. Darah') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.blood_type || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tanggal Lahir') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.birth_date || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Employment --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <h4 class="text-sm font-semibold text-ink">{{ __('Informasi Kepegawaian') }}</h4>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Perusahaan') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.company?.name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Departemen') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.department?.name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Jabatan') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.position?.name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Jenis Kepegawaian') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employmentLabel(employee.employment_type)"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tipe Gaji') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.salary_type || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tanggal Masuk') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.join_date || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Bank & Tax --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <h4 class="text-sm font-semibold text-ink">{{ __('Bank & Pajak') }}</h4>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Bank') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.bank_name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('No. Rekening') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="hasPiiAccess ? employee.bank_account_number : (employee.bank_account_number_masked || '-')"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('NPWP') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="hasPiiAccess ? employee.npwp : (employee.npwp_masked || '-')"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('BPJS Kesehatan') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-ink" x-text="employee.bpjs_kesehatan || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Address --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4" x-show="hasAddress">
                <h4 class="text-sm font-semibold text-ink">{{ __('Alamat') }}</h4>
                <p class="mt-2 text-sm text-ink" x-text="employee.address || ''"></p>
            </div>

            {{-- Family --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <h4 class="text-sm font-semibold text-ink">{{ __('Keluarga') }}</h4>
                <div x-show="families.length === 0" class="mt-3 rounded-xl border border-dashed border-outline-variant/50 p-6 text-center">
                    <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">family_history</span>
                    <p class="mt-2 text-sm text-on-surface-variant">{{ __('Belum ada data keluarga') }}</p>
                </div>
                <div x-show="families.length > 0" class="mt-3 space-y-2">
                    <template x-for="f in families" :key="f.id">
                        <div class="flex items-center justify-between rounded-lg border border-outline-variant/30 bg-surface-dim/30 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-ink" x-text="f.full_name"></p>
                                <p class="text-xs text-on-surface-variant" x-text="relationshipLabel(f.relationship)"></p>
                            </div>
                            <p class="text-xs text-on-surface-variant" x-text="f.birth_date || ''"></p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Documents --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <h4 class="text-sm font-semibold text-ink">{{ __('Dokumen') }}</h4>
                <div class="mt-3 rounded-xl border border-dashed border-outline-variant/50 p-6 text-center">
                    <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">description</span>
                    <p class="mt-2 text-sm text-on-surface-variant">{{ __('Belum ada dokumen diunggah') }}</p>
                </div>
            </div>
        </div>
        </x-page-shell>
    </div>
</x-layouts::app.sidebar>
