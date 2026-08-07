<div>
    <x-admin.page-shell :title="__('Divisions')" :description="__('Manage company divisions and divisions.')">
        <x-slot name="actions">
            <x-actions.button wire:click="showCreating" label="{{ __('Add Division') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
                <span>{{ __('Add Division') }}</span>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <x-forms.label for="division-search" value="{{ __('Search divisions') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                        </span>
                        <x-forms.input id="division-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by division name...') }}" class="w-full pl-11" />
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <x-forms.label for="division-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                    <x-forms.select id="division-per-page" wire:model.live="perPage" class="w-full">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        <x-admin.panel>
            <div
                class="flex flex-col gap-2 border-b border-gray-200/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ __('Division Directory') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($divisions->count())
                            {{ __('Showing :from-:to of :total divisions.', ['from' => $divisions->firstItem(), 'to' => $divisions->lastItem(), 'total' => $divisions->total()]) }}
                        @else
                            {{ __('No divisions match the current selection.') }}
                        @endif
                    </p>
                </div>

                <x-admin.status-badge tone="primary">{{ __('Master data') }}</x-admin.status-badge>
            </div>

            @if ($divisions->count())
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Division Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($divisions as $division)
                                <tr class="group transition-colors hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $division->name }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ __('Used for division grouping and reporting.') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <x-actions.icon-button wire:click="edit({{ $division->id }})"
                                                variant="primary"
                                                label="{{ __('Edit division') }}: {{ $division->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button
                                                wire:click="confirmDeletion({{ $division->id }})"
                                                variant="danger"
                                                label="{{ __('Delete division') }}: {{ $division->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 divide-y divide-gray-200 lg:hidden">
                    @foreach ($divisions as $division)
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-slate-950">
                                        {{ $division->name }}</h3>
                                    <p class="sr-only">
                                        {{ __('Used for division grouping and reporting.') }}</p>
                                </div>

                                <x-admin.status-badge tone="primary">{{ __('Division') }}</x-admin.status-badge>
                            </div>

                            <div
                                class="mt-3 flex flex-wrap justify-end gap-2 border-t border-gray-100 pt-3">
                                <x-actions.button type="button" wire:click="edit({{ $division->id }})"
                                    variant="soft-primary" size="sm"
                                    label="{{ __('Edit division') }}: {{ $division->name }}">
                                    {{ __('Edit') }}
                                </x-actions.button>
                                <x-actions.button type="button"
                                    wire:click="confirmDeletion({{ $division->id }})"
                                    variant="soft-danger" size="sm"
                                    label="{{ __('Delete division') }}: {{ $division->name }}">
                                    {{ __('Delete') }}
                                </x-actions.button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($divisions->hasPages())
                    <div
                        class="border-t border-gray-200/60 bg-gray-50 px-4 py-2.5">
                        {{ $divisions->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <x-admin.empty-state :title="filled($search) ? __('No matching divisions found') : __('No divisions found')" :description="filled($search)
                    ? __('Try changing the keyword to see more results.')
                    : __('Create divisions to organize employees, approvals, and reports.')"
                    class="m-4 border-0 bg-transparent p-4 shadow-none">
                    <x-slot name="icon">
                        <x-heroicon-o-building-office class="h-12 w-12 text-slate-300" />
                    </x-slot>

                    <x-slot name="actions">
                        <x-actions.button type="button" wire:click="showCreating">
                            {{ __('Create Division') }}
                        </x-actions.button>
                    </x-slot>
                </x-admin.empty-state>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Division') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete"
                wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('New Division') }}</x-slot>
        <x-slot name="content">
            <form wire:submit="create">
                <div>
                    <x-forms.label for="create_name" value="{{ __('Division Name') }}" />
                    <x-forms.input id="create_name" class="mt-1 block w-full" type="text" wire:model="name" />
                    <x-forms.input-error for="name" class="mt-2" />
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('creating')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="create"
                wire:loading.attr="disabled">{{ __('Save') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">{{ __('Edit Division') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="update">
                <div>
                    <x-forms.label for="edit_name" value="{{ __('Division Name') }}" />
                    <x-forms.input id="edit_name" class="mt-1 block w-full" type="text" wire:model="name" />
                    <x-forms.input-error for="name" class="mt-2" />
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('editing')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="update"
                wire:loading.attr="disabled">{{ __('Update') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
