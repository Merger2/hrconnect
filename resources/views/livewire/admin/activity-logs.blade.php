<x-admin.page-shell
    :title="__('Activity Logs')"
    :description="__('Review login history and audit trails across users, admins, and superadmins.')"
>
    @php
        $canExportActivityLogs = auth()->user()->can('exportActivityLogs');
    @endphp

    <x-slot name="toolbar">
        <x-admin.page-tools
            :title="__('Filter Audit Logs')"
            :description="__('Search account activity and tighten the audit window with actor and date filters.')"
            grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-6"
        >
            <x-slot name="summary">
                <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-600">
                    {{ trans_choice(':count log listed|:count logs listed', $logs->total(), ['count' => $logs->total()]) }}
                </div>
            </x-slot>

            <x-slot name="actions">
                @if (! $canExportActivityLogs)
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                        {{ __('Read-only audit access') }}
                    </span>
                @else
                    <x-actions.button
                        href="{{ route('admin.activity-logs.export', ['search' => $search, 'start_date' => $dateStart ?: null, 'end_date' => $dateEnd ?: null, 'actor_group' => $actorGroup]) }}"
                        target="_system"
                        rel="noopener noreferrer"
                        variant="success"
                    >
                        <x-heroicon-o-arrow-down-tray class="-ml-1 mr-2 h-4 w-4" />
                        {{ __('Export Excel') }}
                    </x-actions.button>
                @endif
            </x-slot>

            <div class="lg:col-span-3">
                <label for="activity-log-search" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Search audit logs') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                    </div>
                    <x-forms.input id="activity-log-search" type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search user, action, or detail...') }}"
                        class="block w-full pl-10" />
                </div>
            </div>

            <div class="lg:col-span-1">
                <label for="activity-log-actor-group" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Actor') }}</label>
                <x-forms.select id="activity-log-actor-group" wire:model.live="actorGroup" class="block w-full">
                    <option value="all">{{ __('All') }}</option>
                    <option value="user">{{ __('Users') }}</option>
                    <option value="admin">{{ __('Admins') }}</option>
                    <option value="superadmin">{{ __('Superadmins') }}</option>
                </x-forms.select>
            </div>

            <div class="lg:col-span-1">
                <label for="activity-log-start-date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Start Date') }}</label>
                <div wire:ignore>
                    <x-forms.input id="activity-log-start-date" type="date" wire:model.live="dateStart"
                        value="{{ $dateStart }}"
                        class="block w-full" />
                </div>
            </div>

            <div class="lg:col-span-1">
                <label for="activity-log-end-date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('End Date') }}</label>
                <div wire:ignore>
                    <x-forms.input id="activity-log-end-date" type="date" wire:model.live="dateEnd"
                        value="{{ $dateEnd }}"
                        class="block w-full" />
                </div>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div wire:poll.5s class="mb-6">
        <x-admin.import-export-run-list
            :runs="$recentExportRuns"
            :title="__('Audit log export jobs')"
            :description="__('Activity log exports run in the background so large audit windows do not block the page.')"
            :empty="__('No audit log export jobs yet.')"
        />
    </div>

    <x-admin.panel class="ring-1 ring-gray-950/5">
                {{-- Desktop: table hanya di lg ke atas, mobile pakai kartu --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('User') }}
                                </th>
                                <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Action') }}
                                </th>
                                <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('IP Address') }}
                                </th>
                                <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Time') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($logs as $log)
                                <tr class="transition-colors hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">
                                                {{ substr($log->user->name ?? '?', 0, 1) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $log->user->name ?? __('Unknown') }}</div>
                                                <div class="text-xs text-gray-500">{{ $log->user->nip ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 font-medium">{{ $log->action }}</div>
                                        <div class="text-xs text-gray-500">{{ $log->description }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            {{ $log->ip_address ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span>{{ $log->created_at->diffForHumans() }}</span>
                                            <span class="text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        <x-admin.empty-state :title="__('No activity logs found.')" class="border-0 bg-transparent p-0 shadow-none">
                                            <x-slot name="icon">
                                                <x-heroicon-o-exclamation-circle class="h-12 w-12 text-gray-300" />
                                            </x-slot>
                                        </x-admin.empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile: kartu stacked --}}
                <div class="divide-y divide-gray-100 lg:hidden">
                    @forelse($logs as $log)
                        <div class="flex items-start gap-3 px-4 py-3">
                            <div class="h-9 w-9 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">
                                {{ substr($log->user->name ?? '?', 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-900">{{ $log->user->name ?? __('Unknown') }}</div>
                                        <div class="text-xs text-gray-500">{{ $log->user->nip ?? '-' }}</div>
                                    </div>
                                    <span class="shrink-0 text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <div class="mt-2 text-sm font-medium text-gray-900">{{ $log->action }}</div>
                                <div class="mt-0.5 text-xs text-gray-500">{{ $log->description }}</div>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        {{ $log->ip_address ?? '-' }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-gray-500">
                            <x-admin.empty-state :title="__('No activity logs found.')" class="border-0 bg-transparent p-0 shadow-none">
                                <x-slot name="icon">
                                    <x-heroicon-o-exclamation-circle class="h-12 w-12 text-gray-300" />
                                </x-slot>
                            </x-admin.empty-state>
                        </div>
                    @endforelse
                </div>

        <div class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-3">
            {{ $logs->links() }}
        </div>
    </x-admin.panel>
</x-admin.page-shell>
