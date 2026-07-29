<x-admin.page-shell
    :title="__('Companies')"
    :description="__('Manage branch, store, partner, or tenant scopes before enabling broader multi-company access.')"
    :show-description="true"
>
    <x-slot name="actions">
        <x-actions.button type="button" wire:click="create" variant="soft-primary" label="{{ __('Reset company form') }}">
            <x-heroicon-m-arrow-path class="h-5 w-5" />
            <span>{{ __('Reset') }}</span>
        </x-actions.button>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-3 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <x-forms.label for="company-search" value="{{ __('Search companies') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input
                        id="company-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        class="w-full pl-11"
                        placeholder="{{ __('Search company, branch, store, or partner...') }}"
                    />
                </div>
            </div>

            <div class="lg:col-span-3">
                <x-forms.label for="company-status-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.select
                    id="company-status-filter"
                    wire:model.live="statusFilter"
                    class="w-full"
                    aria-label="{{ __('Status') }}"
                >
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="{{ \App\Models\Company::STATUS_ACTIVE }}">{{ __('Active') }}</option>
                    <option value="{{ \App\Models\Company::STATUS_SUSPENDED }}">{{ __('Suspended') }}</option>
                </x-forms.select>
            </div>

            <div class="lg:col-span-2">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 px-3 py-2 text-xs font-semibold text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200">
                    {{ __('Isolation active through user company scope.') }}
                </div>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(340px,0.55fr)]">
        <div class="space-y-4">
            <x-admin.panel>
                <div class="border-b border-gray-200/70 px-4 py-3 dark:border-gray-700/70">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Company Directory') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Users assigned here are constrained by existing multi-company policies and queries.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 p-4">
                    @forelse ($companies as $company)
                        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ $company->name }}</h3>
                                        <x-admin.status-badge :tone="$company->status === \App\Models\Company::STATUS_ACTIVE ? 'success' : 'warning'">
                                            {{ $company->status === \App\Models\Company::STATUS_ACTIVE ? __('Active') : __('Suspended') }}
                                        </x-admin.status-badge>
                                    </div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $company->slug }}</p>
                                    <div class="flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ __('Users: :count', ['count' => $company->users_count]) }}</span>
                                        @if (data_get($company->metadata, 'segment'))
                                            <span>{{ __('Segment: :segment', ['segment' => data_get($company->metadata, 'segment')]) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <x-actions.button type="button" wire:click="edit({{ $company->id }})" variant="soft-primary" size="sm">
                                        <x-heroicon-m-pencil-square class="h-4 w-4" />
                                        <span>{{ __('Edit') }}</span>
                                    </x-actions.button>

                                    @if ($company->status === \App\Models\Company::STATUS_ACTIVE)
                                        <x-actions.button type="button" wire:click="updateStatus({{ $company->id }}, '{{ \App\Models\Company::STATUS_SUSPENDED }}')" variant="soft-warning" size="sm">
                                            <x-heroicon-m-pause class="h-4 w-4" />
                                            <span>{{ __('Suspend') }}</span>
                                        </x-actions.button>
                                    @else
                                        <x-actions.button type="button" wire:click="updateStatus({{ $company->id }}, '{{ \App\Models\Company::STATUS_ACTIVE }}')" variant="soft-success" size="sm">
                                            <x-heroicon-m-play class="h-4 w-4" />
                                            <span>{{ __('Activate') }}</span>
                                        </x-actions.button>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-950/50">
                                <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                    {{ __('Assigned users') }}
                                </h4>

                                <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @forelse ($company->users as $assignedUser)
                                        <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $assignedUser->name }}</p>
                                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $assignedUser->email }}</p>
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="unassignUser({{ $assignedUser->id }})"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:hover:bg-red-950/40 dark:hover:text-red-300 dark:focus:ring-offset-slate-900"
                                                aria-label="{{ __('Remove user from company') }}: {{ $assignedUser->name }}"
                                            >
                                                <x-heroicon-m-x-mark class="h-5 w-5" />
                                            </button>
                                        </div>
                                    @empty
                                        <p class="rounded-lg bg-white px-3 py-2 text-sm text-slate-500 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-800">
                                            {{ __('No users assigned yet.') }}
                                        </p>
                                    @endforelse
                                </div>
                            </div>
                        </article>
                    @empty
                        <x-admin.empty-state
                            :title="__('No companies found')"
                            :description="__('Create the first company, branch, store, or partner scope to start tenant isolation.')"
                            class="border-0 bg-transparent shadow-none"
                        >
                            <x-slot name="icon">
                                <x-heroicon-o-building-office-2 class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                            </x-slot>
                        </x-admin.empty-state>
                    @endforelse
                </div>
            </x-admin.panel>
        </div>

        <div class="space-y-4">
            <x-admin.panel>
                <div class="border-b border-gray-200/70 px-4 py-3 dark:border-gray-700/70">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">
                        {{ $editingCompanyId ? __('Edit Company') : __('Create Company') }}
                    </h2>
                </div>

                <form wire:submit.prevent="save" class="space-y-4 p-4">
                    <div>
                        <x-forms.label for="company-name" value="{{ __('Company / Branch Name') }}" />
                        <x-forms.input id="company-name" type="text" wire:model.live="name" class="mt-1 block w-full" placeholder="{{ __('PT / CV / Store / Partner name') }}" />
                        <x-forms.input-error for="name" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="company-segment" value="{{ __('Segment') }}" />
                        <x-forms.input id="company-segment" type="text" wire:model.live="segment" class="mt-1 block w-full" placeholder="{{ __('Branch, store, partner, vendor...') }}" />
                        <x-forms.input-error for="segment" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="company-status" value="{{ __('Status') }}" />
                        <x-forms.select
                            id="company-status"
                            wire:model.live="status"
                            class="mt-1 w-full"
                            aria-label="{{ __('Status') }}"
                        >
                            <option value="{{ \App\Models\Company::STATUS_ACTIVE }}">{{ __('Active') }}</option>
                            <option value="{{ \App\Models\Company::STATUS_SUSPENDED }}">{{ __('Suspended') }}</option>
                        </x-forms.select>
                        <x-forms.input-error for="status" class="mt-2" />
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <x-actions.button type="submit" class="w-full sm:w-auto">
                            <x-heroicon-m-check class="h-5 w-5" />
                            <span>{{ $editingCompanyId ? __('Save Changes') : __('Create Company') }}</span>
                        </x-actions.button>
                        <x-actions.button type="button" wire:click="create" variant="secondary" class="w-full sm:w-auto">
                            {{ __('Cancel') }}
                        </x-actions.button>
                    </div>
                </form>
            </x-admin.panel>

            <x-admin.panel>
                <div class="border-b border-gray-200/70 px-4 py-3 dark:border-gray-700/70">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Assign User Scope') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Assign admins or employees to one company scope. Superadmins stay global.') }}
                    </p>
                </div>

                <form wire:submit.prevent="assignUser" class="space-y-4 p-4">
                    <div>
                        <x-forms.label for="assign-company" value="{{ __('Company') }}" />
                        <x-forms.select
                            id="assign-company"
                            wire:model.live="selectedCompanyId"
                            class="mt-1 w-full"
                            aria-label="{{ __('Company') }}"
                        >
                            <option value="">{{ __('Choose company') }}</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="selectedCompanyId" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="assign-user" value="{{ __('User') }}" />
                        <x-forms.select
                            id="assign-user"
                            wire:model.live="selectedUserId"
                            class="mt-1 w-full"
                            aria-label="{{ __('User') }}"
                        >
                            <option value="">{{ __('Choose user') }}</option>
                            @foreach ($assignableUsers as $assignableUser)
                                <option value="{{ $assignableUser->id }}">
                                    {{ $assignableUser->name }} - {{ $assignableUser->email }}
                                    @if ($assignableUser->company)
                                        ({{ $assignableUser->company->name }})
                                    @else
                                        ({{ __('No company') }})
                                    @endif
                                </option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="selectedUserId" class="mt-2" />
                    </div>

                    <x-actions.button type="submit" class="w-full">
                        <x-heroicon-m-user-plus class="h-5 w-5" />
                        <span>{{ __('Assign User') }}</span>
                    </x-actions.button>
                </form>
            </x-admin.panel>
        </div>
    </div>
</x-admin.page-shell>
