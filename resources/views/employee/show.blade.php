<x-layouts::app.sidebar :title="__('Detail Karyawan')">
    @php
        $isSelf = auth()->user()->employee?->id === $employee->id;
        $canManage = auth()->user()?->can('manage_employees');
    @endphp
    <div x-data="employeeShow()" class="space-y-6">
        {{-- Employee Header Card --}}
        <div x-show="!loading" class="rounded-xl border border-outline-variant bg-canvas p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <template x-if="employee.photo_url">
                    <img :src="employee.photo_url" alt=""
                         class="h-16 w-16 shrink-0 rounded-xl border border-outline-variant bg-surface-dim object-cover shadow-sm">
                </template>
                <template x-if="!employee.photo_url">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-surface-dim text-xl font-semibold text-on-surface-variant shadow-sm"
                         x-text="employee.full_name?.charAt(0)?.toUpperCase()">
                    </div>
                </template>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-ink" x-text="employee.full_name"></h2>
                        <x-status-badge :tone="$employee->status === 'active' ? 'success' : ($employee->status === 'resigned' ? 'warning' : ($employee->status === 'terminated' ? 'error' : 'neutral'))" pill>
                            <span x-text="statusLabel(employee.status)"></span>
                        </x-status-badge>
                        <x-status-badge tone="info" pill x-show="!!employee.employment_type">
                            <span x-text="employmentLabel(employee.employment_type)"></span>
                        </x-status-badge>
                        <x-status-badge tone="accent" pill x-show="!!employee.position?.name">
                            <span x-text="employee.position.name"></span>
                        </x-status-badge>
                    </div>
                    <p class="mt-1 text-sm text-on-surface-variant" x-text="`#${employee.employee_number}`"></p>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Departemen') }}</p>
                            <p class="mt-0.5 text-sm font-medium text-ink" x-text="employee.department?.name || '-'"></p>
                        </div>
                        <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Cabang') }}</p>
                            <p class="mt-0.5 text-sm font-medium text-ink" x-text="employee.branch?.name || '-'"></p>
                        </div>
                        <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Bergabung') }}</p>
                            <p class="mt-0.5 text-sm font-medium text-ink" x-text="employee.join_date || '-'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions Bar --}}
        <div x-show="!loading" class="flex flex-wrap gap-2">
            @if($canManage)
            <x-button variant="secondary" icon="arrow_back" href="{{ route('admin.employees.index') }}" wire:navigate>
                {{ __('Kembali') }}
            </x-button>
            @else
            <x-button variant="secondary" icon="arrow_back" href="{{ route('dashboard') }}" wire:navigate>
                {{ __('Dashboard') }}
            </x-button>
            @endif
            @can('manage_employees')
                <x-button variant="primary" icon="edit" href="{{ route('admin.employees.edit', $employee) }}" wire:navigate>
                    {{ __('Edit') }}
                </x-button>
            @endcan
        </div>

        {{-- Section Cards --}}
        <div x-show="!loading" class="space-y-4">
            {{-- Personal Information --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Informasi Pribadi') }}</h4>
                    <span class="text-xs text-on-surface-variant">{{ __('Personal') }}</span>
                </div>
                <dl class="mt-3 grid gap-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Jenis Kelamin') }}</dt>
                        <dd class="text-sm text-ink" x-text="genderLabel(employee.gender)"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tanggal Lahir') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.birth_date || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Status Pernikahan') }}</dt>
                        <dd class="text-sm text-ink" x-text="maritalLabel(employee.marital_status)"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Golongan Darah') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.blood_type || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Pendidikan') }}</dt>
                        <dd class="text-sm text-ink" x-text="educationLabel(employee.education_level)"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Shift') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.shift?.name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Atasan') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.manager?.full_name || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Employment Info --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Informasi Kepegawaian') }}</h4>
                    <span class="text-xs text-on-surface-variant">{{ __('Employment') }}</span>
                </div>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tipe Karyawan') }}</dt>
                        <dd class="text-sm text-ink" x-text="employmentLabel(employee.employment_type)"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tanggal Bergabung') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.join_date || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Status') }}</dt>
                        <dd class="text-sm text-ink" x-text="statusLabel(employee.status)"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tanggal Berakhir') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.end_date || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Bank & Tax --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Bank & Pajak') }}</h4>
                    <span class="text-xs text-on-surface-variant">{{ __('Bank & Tax') }}</span>
                </div>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Bank') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.bank_name || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Tipe Gaji') }}</dt>
                        <dd class="text-sm text-ink" x-text="salaryLabel(employee.salary_type)"></dd>
                    </div>
                    <div x-show="hasPiiAccess">
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('NIK') }}</dt>
                        <dd class="text-sm text-ink" x-text="pii.nik || '-'"></dd>
                    </div>
                    <div x-show="hasPiiAccess">
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('NPWP') }}</dt>
                        <dd class="text-sm text-ink" x-text="pii.npwp || '-'"></dd>
                    </div>
                    <div x-show="hasPiiAccess">
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Telepon') }}</dt>
                        <dd class="text-sm text-ink" x-text="pii.phone || '-'"></dd>
                    </div>
                    <div x-show="hasPiiAccess">
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('No. Rekening') }}</dt>
                        <dd class="text-sm text-ink" x-text="pii.bank_account_number || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Address --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4" x-show="hasAddress">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Alamat') }}</h4>
                    <span class="text-xs text-on-surface-variant">{{ __('Address') }}</span>
                </div>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Alamat') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.address || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Provinsi') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.province || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Kota') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.city || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Kecamatan') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.district || '-'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-on-surface-variant">{{ __('Kelurahan') }}</dt>
                        <dd class="text-sm text-ink" x-text="employee.village || '-'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Family --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Keluarga') }}</h4>
                    <span class="text-xs text-on-surface-variant">{{ __('Family') }}</span>
                </div>
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
                            <span class="text-xs text-on-surface-variant" x-text="f.birth_date || ''"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Documents --}}
            <div class="rounded-xl border border-outline-variant bg-canvas p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-ink">{{ __('Dokumen') }}</h4>
                    <span class="text-xs text-on-surface-variant">{{ __('Documents') }}</span>
                </div>
                <div class="mt-3 rounded-xl border border-dashed border-outline-variant/50 p-6 text-center">
                    <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">description</span>
                    <p class="mt-2 text-sm text-on-surface-variant">{{ __('Belum ada dokumen diunggah') }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function employeeShow() {
            return {
                employee: @json($employee->load(['user:id,email', 'branch:id,name', 'department:id,name', 'position:id,name,grade,basic_salary', 'shift:id,name', 'manager:id,full_name'])),
                pii: {},
                families: @json($employee->families ?? []),
                loading: false,
                hasPiiAccess: @json(auth()->user()?->can('viewPii', $employee) ?? false),

                init() {
                    if (this.hasPiiAccess) {
                        this.fetchPii();
                    }
                },

                get hasAddress() {
                    return !!(this.employee.address || this.employee.province || this.employee.city
                        || this.employee.district || this.employee.village);
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
                        active: '{{ __('Aktif') }}',
                        inactive: '{{ __('Nonaktif') }}',
                        resigned: '{{ __('Resign') }}',
                        terminated: '{{ __('PHK') }}',
                        deceased: '{{ __('Meninggal') }}',
                    };
                    return labels[status] || status;
                },

                genderLabel(g) {
                    return g === 'L' ? '{{ __('Laki-laki') }}' : g === 'P' ? '{{ __('Perempuan') }}' : '-';
                },

                maritalLabel(m) {
                    const labels = {
                        single: '{{ __('Lajang') }}',
                        married: '{{ __('Menikah') }}',
                        divorced: '{{ __('Cerai') }}',
                        widowed: '{{ __('Duda/Janda') }}',
                    };
                    return labels[m] || m || '-';
                },

                educationLabel(e) {
                    const labels = {
                        sd: 'SD', smp: 'SMP', sma: 'SMA', smk: 'SMK',
                        diploma: '{{ __('Diploma') }}', bachelor: '{{ __('S1') }}',
                        master: '{{ __('S2') }}', doctorate: '{{ __('S3') }}',
                        other: '{{ __('Lainnya') }}',
                    };
                    return labels[e] || e || '-';
                },

                employmentLabel(e) {
                    const labels = {
                        permanent: '{{ __('Tetap') }}',
                        contract: '{{ __('Kontrak') }}',
                        probation: '{{ __('Percobaan') }}',
                        intern: '{{ __('Magang') }}',
                    };
                    return labels[e] || e || '-';
                },

                salaryLabel(s) {
                    const labels = {
                        monthly: '{{ __('Bulanan') }}',
                        daily: '{{ __('Harian') }}',
                        hourly: '{{ __('Per Jam') }}',
                    };
                    return labels[s] || s || '-';
                },

                relationshipLabel(r) {
                    const labels = {
                        spouse: '{{ __('Pasangan') }}',
                        child: '{{ __('Anak') }}',
                        parent: '{{ __('Orang Tua') }}',
                        sibling: '{{ __('Saudara') }}',
                    };
                    return labels[r] || r || '-';
                },
            };
        }
    </script>
</x-layouts::app.sidebar>
