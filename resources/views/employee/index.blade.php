<x-layouts::app.sidebar :title="__('Direktori Karyawan')">
    <div x-data="employeesIndex()">
        @php $role = auth()->user()->roles->first()?->name @endphp
        @php
            $empTitle = match ($role) {
                'manager' => __('Anggota Tim'),
                'finance' => __('Data Karyawan'),
                default => __('Direktori Karyawan'),
            };
            $empSubtitle = match ($role) {
                'manager' => __('Lihat anggota tim Anda'),
                'finance' => __('Data karyawan untuk keperluan finance'),
                default => __('Lihat dan kelola data karyawan'),
            };
        @endphp
        <x-page-shell :title="$empTitle" :subtitle="$empSubtitle">
            <x-slot:actions>
                @can('manage_employees')
                    <x-button variant="primary" icon="add" @click="openCreateModal()">
                        {{ __('Tambah Karyawan') }}
                    </x-button>
                    <x-button variant="secondary" icon="download" @click="exportCSV">
                        {{ __('Ekspor') }}
                    </x-button>
                @endcan
            </x-slot:actions>

            {{-- Summary Bar (HR/Admin only) --}}
            @can('manage_employees')
            <div x-show="!loading && total > 0" class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-3 py-2.5">
                    <dt class="text-[0.68rem] font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Total') }}</dt>
                    <dd class="mt-0.5 text-base font-semibold text-ink" x-text="total"></dd>
                </div>
                <div class="rounded-lg border border-success/20 bg-success/5 px-3 py-2.5">
                    <dt class="text-[0.68rem] font-semibold uppercase tracking-widest text-success">{{ __('Aktif') }}</dt>
                    <dd class="mt-0.5 text-base font-semibold text-ink" x-text="employees.filter(e => e.status === 'active').length"></dd>
                </div>
                <div class="rounded-lg border border-warning/20 bg-warning/5 px-3 py-2.5">
                    <dt class="text-[0.68rem] font-semibold uppercase tracking-widest text-warning">{{ __('Resign') }}</dt>
                    <dd class="mt-0.5 text-base font-semibold text-ink" x-text="employees.filter(e => e.status === 'resigned').length"></dd>
                </div>
                <div class="rounded-lg border border-error/20 bg-error/5 px-3 py-2.5">
                    <dt class="text-[0.68rem] font-semibold uppercase tracking-widest text-error">{{ __('PHK') }}</dt>
                    <dd class="mt-0.5 text-base font-semibold text-ink" x-text="employees.filter(e => e.status === 'terminated').length"></dd>
                </div>
            </div>
            @endcan

            <x-slot:toolbar>
                <x-page-toolbar search search-placeholder="{{ __('Cari nama atau nomor karyawan...') }}">
                    <x-slot:filters>
                        <select x-model="filters.status" @change="page = 1; fetchEmployees()"
                            class="h-10 rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Semua Status') }}</option>
                            <option value="active">{{ __('Aktif') }}</option>
                            <option value="inactive">{{ __('Tidak Aktif') }}</option>
                            <option value="resigned">{{ __('Resign') }}</option>
                            <option value="terminated">{{ __('PHK') }}</option>
                            <option value="deceased">{{ __('Meninggal') }}</option>
                        </select>
                        <select x-model="filters.department_id" @change="page = 1; fetchEmployees()"
                            class="h-10 rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            <option value="">{{ __('Semua Departemen') }}</option>
                            <template x-for="d in departments" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <button @click="toggleView"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-dim hover:text-ink"
                            x-bind:title="view === 'table' ? '{{ __('Tampilan Kartu') }}' : '{{ __('Tampilan Tabel') }}'">
                            <span class="material-symbols-outlined text-lg" x-text="view === 'table' ? 'grid_view' : 'table_rows'"></span>
                        </button>
                    </x-slot:filters>
                </x-page-toolbar>
            </x-slot:toolbar>

            {{-- Loading --}}
            <div x-show="loading" class="py-16">
                <x-loading-skeleton mode="table" :rows="5" :cols="4" />
            </div>

            {{-- Empty State --}}
            <template x-if="!loading && employees.length === 0">
                <div class="mx-auto max-w-xl rounded-xl border border-outline-variant/40 bg-canvas p-6 text-center shadow-sm">
                    <span class="material-symbols-outlined text-5xl text-on-surface-variant/50">group</span>
                    <h3 class="mt-3 text-sm font-semibold text-ink">{{ __('Tidak ada karyawan') }}</h3>
                    <p class="mt-1 text-sm text-on-surface-variant">{{ __('Coba ubah filter atau kata kunci pencarian') }}</p>
                    @can('manage_employees')
                        <div class="mt-4">
                            <x-button variant="primary" icon="add" @click="openCreateModal()">
                                {{ __('Tambah Karyawan') }}
                            </x-button>
                        </div>
                    @endcan
                </div>
            </template>

            {{-- Desktop Table --}}
            <template x-if="!loading && employees.length > 0 && view === 'table'">
                <div class="hidden overflow-x-auto rounded-xl border border-outline-variant/40 bg-canvas shadow-sm lg:block">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-surface-dim/50 text-on-surface-variant">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Karyawan') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Departemen & Jabatan') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <template x-for="e in employees" :key="e.id">
                                <tr class="transition-colors hover:bg-surface-dim/30">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <template x-if="e.photo_url">
                                                <div class="h-10 w-10 overflow-hidden rounded-full ring-2 ring-ink/10">
                                                    <img :src="e.photo_url" :alt="e.full_name" class="h-full w-full object-cover">
                                                </div>
                                            </template>
                                            <template x-if="!e.photo_url">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-ink/5 text-sm font-semibold text-on-surface-variant ring-2 ring-ink/10" x-text="e.full_name?.charAt(0)?.toUpperCase()"></div>
                                            </template>
                                            <div class="min-w-0">
                                                <a :href="`/admin/employees/${e.id}`" class="text-sm font-medium text-ink hover:underline" x-text="e.full_name" wire:navigate></a>
                                                <p class="text-xs text-on-surface-variant" x-text="`#${e.employee_number}`"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-ink" x-text="e.position?.name || '-'"></p>
                                        <p class="text-xs text-on-surface-variant" x-text="e.department?.name || ''"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                            :class="statusClass(e.status)"
                                            x-text="statusLabel(e.status)">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a :href="`/admin/employees/${e.id}`" wire:navigate
                                               class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-on-surface-variant transition-colors hover:bg-surface-dim hover:text-ink">
                                                <span class="material-symbols-outlined text-base">visibility</span>
                                                <span class="hidden sm:inline">{{ __('Lihat') }}</span>
                                            </a>
                                            @can('manage_employees')
                                                <button @click="openEditModal(e)"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-on-surface-variant transition-colors hover:bg-surface-dim hover:text-ink">
                                                    <span class="material-symbols-outlined text-base">edit</span>
                                                    <span class="hidden sm:inline">{{ __('Edit') }}</span>
                                                </button>
                                                <button @click="openTerminateModal(e)"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-on-surface-variant transition-colors hover:bg-error/5 hover:text-error">
                                                    <span class="material-symbols-outlined text-base">block</span>
                                                    <span class="hidden sm:inline">{{ __('PHK') }}</span>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            {{-- Mobile Cards --}}
            <template x-if="!loading && employees.length > 0 && view === 'grid'">
                <div class="hidden lg:grid lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    <template x-for="e in employees" :key="e.id">
                        <div class="rounded-lg border border-outline-variant/40 bg-canvas p-4 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-start gap-3">
                                <template x-if="e.photo_url">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border-2 border-ink/10 shadow-sm">
                                        <img :src="e.photo_url" :alt="e.full_name" class="h-full w-full object-cover">
                                    </div>
                                </template>
                                <template x-if="!e.photo_url">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-ink/5 text-base font-semibold text-on-surface-variant ring-2 ring-ink/10" x-text="e.full_name?.charAt(0)?.toUpperCase()"></div>
                                </template>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="truncate text-sm font-semibold text-ink">
                                            <a :href="`/admin/employees/${e.id}`" x-text="e.full_name" wire:navigate></a>
                                        </h4>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset shrink-0"
                                            :class="statusClass(e.status)"
                                            x-text="statusLabel(e.status)">
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-on-surface-variant" x-text="`#${e.employee_number} · ${e.position?.name || '-'}`"></p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-outline-variant/20 bg-surface-dim/30 px-3 py-2">
                                    <span class="block text-[0.65rem] font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Departemen') }}</span>
                                    <span class="mt-0.5 block text-sm font-medium text-ink truncate" x-text="e.department?.name || '-'"></span>
                                </div>
                                <div class="rounded-lg border border-outline-variant/20 bg-surface-dim/30 px-3 py-2">
                                    <span class="block text-[0.65rem] font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Masuk') }}</span>
                                    <span class="mt-0.5 block text-sm font-medium text-ink" x-text="e.join_date || '-'"></span>
                                </div>
                            </div>
                            @can('manage_employees')
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button @click="openEditModal(e)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-2 text-xs font-semibold text-ink transition-colors hover:bg-surface-dim">
                                    <span class="material-symbols-outlined text-base">edit</span>
                                    {{ __('Edit') }}
                                </button>
                                <button @click="openTerminateModal(e)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-2 text-xs font-semibold text-ink transition-colors hover:bg-error/5 hover:text-error hover:border-error/20">
                                    <span class="material-symbols-outlined text-base">block</span>
                                    {{ __('PHK') }}
                                </button>
                            </div>
                            @endcan
                        </div>
                    </template>
                </div>
            </template>

            {{-- Mobile card list --}}
            <template x-if="!loading && employees.length > 0">
                <div class="space-y-3 lg:hidden">
                    <template x-for="e in employees" :key="e.id">
                        <div class="rounded-lg border border-outline-variant/40 bg-canvas p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <template x-if="e.photo_url">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border-2 border-ink/10 shadow-sm">
                                        <img :src="e.photo_url" :alt="e.full_name" class="h-full w-full object-cover">
                                    </div>
                                </template>
                                <template x-if="!e.photo_url">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-ink/5 text-base font-semibold text-on-surface-variant ring-2 ring-ink/10" x-text="e.full_name?.charAt(0)?.toUpperCase()"></div>
                                </template>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-ink">
                                                <a :href="`/admin/employees/${e.id}`" x-text="e.full_name" wire:navigate></a>
                                            </h4>
                                            <p class="text-xs text-on-surface-variant" x-text="`#${e.employee_number}`"></p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset shrink-0"
                                            :class="statusClass(e.status)"
                                            x-text="statusLabel(e.status)">
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-on-surface-variant" x-text="e.position?.name || '-'"></p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-outline-variant/20 bg-surface-dim/30 px-3 py-2">
                                    <span class="block text-[0.65rem] font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Departemen') }}</span>
                                    <span class="mt-0.5 block text-sm font-medium text-ink truncate" x-text="e.department?.name || '-'"></span>
                                </div>
                                <div class="rounded-lg border border-outline-variant/20 bg-surface-dim/30 px-3 py-2">
                                    <span class="block text-[0.65rem] font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Masuk') }}</span>
                                    <span class="mt-0.5 block text-sm font-medium text-ink" x-text="e.join_date || '-'"></span>
                                </div>
                            </div>
                            @can('manage_employees')
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <a :href="`/admin/employees/${e.id}`" wire:navigate
                                    class="inline-flex items-center justify-center rounded-lg border border-outline-variant/40 bg-canvas px-2 py-2 text-xs font-medium text-ink transition-colors hover:bg-surface-dim">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                </a>
                                <button @click="openEditModal(e)"
                                    class="inline-flex items-center justify-center rounded-lg border border-outline-variant/40 bg-canvas px-2 py-2 text-xs font-medium text-ink transition-colors hover:bg-surface-dim">
                                    <span class="material-symbols-outlined text-base">edit</span>
                                </button>
                                <button @click="openTerminateModal(e)"
                                    class="inline-flex items-center justify-center rounded-lg border border-outline-variant/40 bg-canvas px-2 py-2 text-xs font-medium text-ink transition-colors hover:bg-error/5 hover:text-error hover:border-error/20">
                                    <span class="material-symbols-outlined text-base">block</span>
                                </button>
                            </div>
                            @endcan
                        </div>
                    </template>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="!loading && employees.length > 0" class="mt-4">
                <div x-show="lastPage > 1" class="flex items-center justify-between rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-4 py-2.5">
                    <button @click="page = Math.max(1, page - 1); fetchEmployees()" x-bind:disabled="page <= 1"
                        class="flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-1.5 text-sm font-medium text-ink transition-colors hover:bg-surface-dim disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                        {{ __('Sebelumnya') }}
                    </button>
                    <span class="text-sm text-on-surface-variant">
                        {{ __('Halaman') }} <span x-text="page"></span> / <span x-text="lastPage"></span>
                        (<span x-text="total"></span> {{ __('total') }})
                    </span>
                    <button @click="page = Math.min(lastPage, page + 1); fetchEmployees()" x-bind:disabled="page >= lastPage"
                        class="flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-1.5 text-sm font-medium text-ink transition-colors hover:bg-surface-dim disabled:opacity-40">
                        {{ __('Selanjutnya') }}
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
                <div class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-lg bg-canvas p-6 shadow-xl">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-ink" x-text="editing ? '{{ __('Edit Karyawan') }}' : '{{ __('Tambah Karyawan') }}'"></h2>
                        <button @click="createModalOpen = false" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-dim hover:text-ink">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Akun') }}</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Nama') }} *</label>
                                    <input type="text" x-model="form.name"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Email') }} *</label>
                                    <input type="email" x-model="form.email"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                            </div>
                            <div x-show="!editing" class="mt-4">
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Kata Sandi') }} *</label>
                                <input type="password" x-model="form.password"
                                    class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                            </div>
                        </div>

                        <hr class="border-outline-variant/50">

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Data Karyawan') }}</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Nomor Karyawan') }} *</label>
                                    <input type="text" x-model="form.employee_number"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Nama Lengkap') }} *</label>
                                    <input type="text" x-model="form.full_name"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('NIK') }} *</label>
                                    <input type="text" x-model="form.nik" maxlength="16"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Telepon') }} *</label>
                                    <input type="text" x-model="form.phone"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Jenis Kelamin') }} *</label>
                                    <select x-model="form.gender"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <option value="L">{{ __('Laki-laki') }}</option>
                                        <option value="P">{{ __('Perempuan') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Status Pernikahan') }} *</label>
                                    <select x-model="form.marital_status"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <option value="single">{{ __('Lajang') }}</option>
                                        <option value="married">{{ __('Menikah') }}</option>
                                        <option value="divorced">{{ __('Cerai') }}</option>
                                        <option value="widowed">{{ __('Duda/Janda') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Gol. Darah') }}</label>
                                    <select x-model="form.blood_type"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
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
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Tanggal Lahir') }} *</label>
                                    <input type="date" x-model="form.birth_date"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                            </div>
                        </div>

                        <hr class="border-outline-variant/50">

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Kepegawaian') }}</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Perusahaan') }} *</label>
                                    <select x-model="form.company_id"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <template x-for="c in lookup.companies" :key="c.id">
                                            <option :value="c.id" x-text="c.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Cabang') }} *</label>
                                    <select x-model="form.branch_id"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <template x-for="b in lookup.branches" :key="b.id">
                                            <option :value="b.id" x-text="b.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Departemen') }} *</label>
                                    <select x-model="form.department_id"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <template x-for="d in lookup.departments" :key="d.id">
                                            <option :value="d.id" x-text="d.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Jabatan') }} *</label>
                                    <select x-model="form.position_id"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <template x-for="p in lookup.positions" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Atasan Langsung') }}</label>
                                    <select x-model="form.parent_id"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Tidak Ada') }}</option>
                                        <template x-for="m in lookup.managers" :key="m.id">
                                            <option :value="m.id" x-text="m.full_name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Jenis Kepegawaian') }} *</label>
                                    <select x-model="form.employment_type"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <option value="permanent">{{ __('Tetap') }}</option>
                                        <option value="contract">{{ __('Kontrak') }}</option>
                                        <option value="probation">{{ __('Percobaan') }}</option>
                                        <option value="intern">{{ __('Magang') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Tipe Gaji') }} *</label>
                                    <select x-model="form.salary_type"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
                                        <option value="monthly">{{ __('Bulanan') }}</option>
                                        <option value="daily">{{ __('Harian') }}</option>
                                        <option value="hourly">{{ __('Per Jam') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Tanggal Masuk') }} *</label>
                                    <input type="date" x-model="form.join_date"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                            </div>
                        </div>

                        <hr class="border-outline-variant/50">

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Pendidikan') }}</p>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Jenjang') }} *</label>
                                    <select x-model="form.education_level"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                        <option value="">{{ __('Pilih...') }}</option>
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
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Institusi') }} *</label>
                                    <input type="text" x-model="form.institution_name"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Tahun Lulus') }} *</label>
                                    <input type="number" x-model="form.graduation_year" min="1950" :max="new Date().getFullYear()"
                                        class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink">
                                </div>
                            </div>
                        </div>

                        <div x-show="formError" x-cloak class="rounded-lg bg-error/10 p-3 text-sm text-error">
                            <p x-text="formError"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-outline-variant/50 pt-4">
                        <button type="button" @click="createModalOpen = false"
                            class="rounded-lg border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Batal') }}
                        </button>
                        <button @click="submitEmployee()" x-bind:disabled="formLoading"
                            class="rounded-lg bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                            <span x-show="!formLoading" x-text="editing ? '{{ __('Perbarui') }}' : '{{ __('Simpan') }}'"></span>
                            <span x-show="formLoading" x-cloak>{{ __('Menyimpan...') }}</span>
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
                <div class="relative z-10 w-full max-w-sm rounded-lg bg-canvas p-6 shadow-xl">
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-error/10">
                            <span class="material-symbols-outlined text-2xl text-error">warning</span>
                        </div>
                        <h3 class="text-lg font-semibold text-ink">{{ __('PHK Karyawan') }}</h3>
                        <p class="mt-1 text-sm text-on-surface-variant">{{ __('Yakin ingin memberhentikan karyawan ini? Tindakan ini tidak dapat dibatalkan.') }}</p>

                        <div class="mt-4 w-full space-y-3 text-left">
                            <div class="rounded-lg bg-warning/10 p-3 text-sm text-warning">
                                <p class="font-medium">{{ __('Perhatian') }}</p>
                                <p class="mt-1 text-xs">{{ __('PHK akan mencabut semua akses sistem dan mengubah status menjadi diberhentikan.') }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Jenis PHK') }} *</label>
                                <select x-model="terminateForm.type"
                                    class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                                    <option value="dismissed">{{ __('PHK') }}</option>
                                    <option value="resign">{{ __('Mengundurkan Diri') }}</option>
                                    <option value="contract_end">{{ __('Kontrak Berakhir') }}</option>
                                    <option value="deceased">{{ __('Meninggal Dunia') }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Alasan') }} *</label>
                                <textarea x-model="terminateForm.reason" rows="3"
                                    class="w-full rounded-lg border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink"
                                    placeholder="{{ __('Jelaskan alasan PHK...') }}"></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Tanggal Efektif') }} *</label>
                                <input type="date" x-model="terminateForm.date"
                                    class="h-10 w-full rounded-lg border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                            </div>

                            <div x-show="terminateError" x-cloak class="rounded-lg bg-error/10 p-3 text-sm text-error">
                                <p x-text="terminateError"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button @click="terminateModalOpen = false"
                            class="rounded-lg border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Batal') }}
                        </button>
                        <button @click="submitTerminate()" x-bind:disabled="terminateLoading"
                            class="rounded-lg bg-error px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                            <span x-show="!terminateLoading">{{ __('Konfirmasi PHK') }}</span>
                            <span x-show="terminateLoading" x-cloak>{{ __('Memproses...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Import --}}
    </div>
</x-layouts::app.sidebar>