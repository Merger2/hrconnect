<x-layouts::app.sidebar>
    <div x-data="assetsIndex()">
        {{-- Header --}}
        <div class="mb-3 flex flex-col gap-2.5 border-b border-outline-variant/50 pb-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold tracking-tight text-ink">{{ __('Asset Management') }}</h1>
                <p class="text-sm text-on-surface-variant">{{ __('Manage company assets and equipment') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <x-button variant="primary" @click="openCreateModal" icon="add">
                    {{ __('Add Asset') }}
                </x-button>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="mb-3 rounded-xl border border-outline-variant bg-canvas p-2.5 shadow-sm">
            <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Status') }}</label>
                    <select x-model="statusFilter" @change="fetchAssets()"
                            class="block w-full rounded-lg border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-inset focus:ring-ink">
                        <option value="">{{ __('All') }}</option>
                        <option value="available">{{ __('Available') }}</option>
                        <option value="assigned">{{ __('Assigned') }}</option>
                        <option value="disposed">{{ __('Disposed') }}</option>
                    </select>
                </div>
                <div class="xl:col-span-3">
                    <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Search') }}</label>
                    <input type="text" x-model="search" @input.debounce="fetchAssets()"
                           placeholder="{{ __('Name or serial number...') }}"
                           class="block w-full rounded-lg border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-inset focus:ring-ink">
                </div>
                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Category') }}</label>
                    <select x-model="categoryFilter" @change="fetchAssets()"
                            class="block w-full rounded-lg border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-inset focus:ring-ink">
                        <option value="">{{ __('All') }}</option>
                        <option value="elektronik">{{ __('Electronics') }}</option>
                        <option value="furniture">{{ __('Furniture') }}</option>
                        <option value="kendaraan">{{ __('Vehicle') }}</option>
                        <option value="peralatan">{{ __('Equipment') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Summary stats --}}
        <dl class="mb-4 flex flex-wrap gap-2" x-show="!loading">
            <div class="rounded-lg border border-outline-variant bg-surface-container-low px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-on-surface-variant">{{ __('Total') }}</dt>
                <dd class="text-sm font-bold text-ink" x-text="total">0</dd>
            </div>
            <div class="rounded-lg border border-success/30 bg-success/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-success">{{ __('Available') }}</dt>
                <dd class="text-sm font-bold text-success" x-text="available">0</dd>
            </div>
            <div class="rounded-lg border border-info/30 bg-info/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-info">{{ __('Assigned') }}</dt>
                <dd class="text-sm font-bold text-info" x-text="assigned">0</dd>
            </div>
            <div class="rounded-lg border border-error/30 bg-error/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-error">{{ __('Disposed') }}</dt>
                <dd class="text-sm font-bold text-error" x-text="disposed">0</dd>
            </div>
        </dl>

        {{-- Loading --}}
        <div x-show="loading" class="flex items-center justify-center gap-2 py-16 text-sm text-on-surface-variant">
            <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
            {{ __('Memuat...') }}
        </div>

        {{-- Content Panel --}}
        <x-app.panel>
            {{-- Desktop Table --}}
            <div x-show="!loading" class="hidden overflow-x-auto lg:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-surface-dim text-on-surface-variant">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Serial Number') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Category') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="asset in records" :key="asset.id">
                            <tr class="transition-colors hover:bg-surface-dim">
                                <td class="px-4 py-3 text-ink" x-text="asset.name"></td>
                                <td class="px-4 py-3 text-ink" x-text="asset.serial_number"></td>
                                <td class="px-4 py-3 text-on-surface-variant" x-text="asset.category || '-'"></td>
                                <td class="px-4 py-3">
                                    <x-status-badge x-show="asset.status === 'available'" tone="success" pill>{{ __('Available') }}</x-status-badge>
                                    <x-status-badge x-show="asset.status === 'assigned'" tone="info" pill>{{ __('Assigned') }}</x-status-badge>
                                    <x-status-badge x-show="asset.status === 'disposed'" tone="error" pill>{{ __('Disposed') }}</x-status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="openHandoverModal(asset)"
                                            x-show="asset.status === 'available'"
                                            class="rounded-lg px-2 py-1 text-xs font-medium text-primary transition-colors hover:bg-primary/5">
                                        {{ __('Handover') }}
                                    </button>
                                    <button @click="deleteAsset(asset.id)"
                                            x-show="asset.status !== 'assigned'"
                                            class="rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                                        {{ __('Delete') }}
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="records.length === 0">
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">inventory_2</span>
                                        <p class="text-sm font-medium text-ink">{{ __('Belum ada aset') }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ __('Tambahkan aset untuk mulai') }}</p>
                                        <div class="mt-2">
                                            <x-button variant="primary" @click="openCreateModal" icon="add" size="sm">{{ __('Add Asset') }}</x-button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div x-show="!loading" class="space-y-3 lg:hidden">
                <template x-for="asset in records" :key="asset.id">
                    <div class="user-list-card p-4">
                        <div class="mb-1 flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-ink" x-text="asset.name"></p>
                                <div class="mt-0.5 space-x-2 text-xs text-on-surface-variant">
                                    <span x-text="asset.serial_number"></span>
                                    <span x-text="asset.category || '-'"></span>
                                </div>
                            </div>
                            <x-status-badge x-show="asset.status === 'available'" tone="success" pill>{{ __('Available') }}</x-status-badge>
                            <x-status-badge x-show="asset.status === 'assigned'" tone="info" pill>{{ __('Assigned') }}</x-status-badge>
                            <x-status-badge x-show="asset.status === 'disposed'" tone="error" pill>{{ __('Disposed') }}</x-status-badge>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button @click="openHandoverModal(asset)"
                                    x-show="asset.status === 'available'"
                                    class="rounded-lg px-2 py-1 text-xs font-medium text-primary transition-colors hover:bg-primary/5">
                                {{ __('Handover') }}
                            </button>
                            <button @click="deleteAsset(asset.id)"
                                    x-show="asset.status !== 'assigned'"
                                    class="rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="records.length === 0">
                    <div class="flex flex-col items-center gap-2 py-12">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">inventory_2</span>
                        <p class="text-sm font-medium text-ink">{{ __('Belum ada aset') }}</p>
                    </div>
                </template>
            </div>
        </x-app.panel>

        {{-- Create Modal --}}
        <template x-teleport="body">
            <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center"
                 x-cloak x-trap.noscroll="showCreateModal">
                <div class="fixed inset-0 bg-black/40" @click="showCreateModal = false"></div>
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-canvas p-6 shadow-xl">
                    <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('New Asset') }}</h2>

                    <div class="space-y-5">
                        <x-forms.input name="name" label="{{ __('Name') }}" x-model="form.name" required />
                        <x-forms.input name="serial_number" label="{{ __('Serial Number') }}" x-model="form.serial_number" required />
                        <x-forms.input name="code" label="{{ __('Code') }}" x-model="form.code" />

                        <x-forms.select name="category" x-model="form.category"
                            :options="['' => __('Select...'), 'elektronik' => __('Electronics'), 'furniture' => __('Furniture'), 'kendaraan' => __('Vehicle'), 'peralatan' => __('Equipment')]" />
                    </div>

                    <x-sections.section-border />

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button @click="showCreateModal = false; formError = ''"
                                class="rounded-lg border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="submitAsset" :disabled="submitting"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!submitting">{{ __('Save') }}</span>
                            <span x-show="submitting" class="flex items-center gap-1">
                                <span class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                                {{ __('Menyimpan...') }}
                            </span>
                        </button>
                    </div>
                    <p x-show="formError" class="mt-2 text-sm text-error" x-text="formError"></p>
                </div>
            </div>
        </template>

        {{-- Handover Modal --}}
        <template x-teleport="body">
            <div x-show="showHandoverModal" class="fixed inset-0 z-50 flex items-center justify-center"
                 x-cloak x-trap.noscroll="showHandoverModal">
                <div class="fixed inset-0 bg-black/40" @click="showHandoverModal = false"></div>
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-canvas p-6 shadow-xl">
                    <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Handover Asset') }}: <span x-text="handoverAsset?.name"></span></h2>

                    <div class="space-y-5">
                        <x-forms.input name="employee_id" label="{{ __('Employee ID') }}" type="number" x-model="handoverForm.employee_id" required />
                        <x-forms.input name="handover_date" label="{{ __('Handover Date') }}" type="date" x-model="handoverForm.handover_date" required />
                        <x-forms.input name="condition" label="{{ __('Condition') }}" x-model="handoverForm.condition" required />
                    </div>

                    <x-sections.section-border />

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button @click="showHandoverModal = false; handoverError = ''"
                                class="rounded-lg border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="submitHandover" :disabled="submitting"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!submitting">{{ __('Handover') }}</span>
                            <span x-show="submitting" class="flex items-center gap-1">
                                <span class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                                {{ __('Mengirim...') }}
                            </span>
                        </button>
                    </div>
                    <p x-show="handoverError" class="mt-2 text-sm text-error" x-text="handoverError"></p>
                </div>
            </div>
        </template>
    </div>


</x-layouts::app.sidebar>
