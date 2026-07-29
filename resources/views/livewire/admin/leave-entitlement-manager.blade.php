<x-admin.page-shell
    :title="__('Leave Entitlements')"
    :description="__('Assign annual leave allocation, carry-over, and expiry per employee.')"
    :show-description="true"
>
    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-3 lg:grid-cols-12">
            <div class="lg:col-span-12">
                <x-forms.label for="leave-entitlement-search" value="{{ __('Search employee') }}" class="mb-1.5 block" />
                <x-forms.input
                    id="leave-entitlement-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search name, NIP, or email...') }}"
                />
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <x-admin.panel class="order-2 xl:order-1">
            <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Employee Leave Allocation') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Expired entitlement keeps history visible but blocks new annual leave requests beyond the expiry date.') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 p-4">
                @forelse ($entitlements as $entitlement)
                    @php
                        $totalAllocated = (float) $entitlement->allocated_days + (float) $entitlement->carried_over_days;
                        $isExpired = $entitlement->expires_at && $entitlement->expires_at->endOfDay()->isPast();
                    @endphp
                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="font-semibold text-slate-950 dark:text-white">{{ $entitlement->user?->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $entitlement->user?->company?->name ?? __('No company') }} · {{ $entitlement->leaveType?->name }} · {{ $entitlement->year }}
                                </p>
                            </div>
                            <x-admin.status-badge :tone="$isExpired ? 'danger' : 'success'">
                                {{ $isExpired ? __('Expired') : __('Active') }}
                            </x-admin.status-badge>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Allocated') }}</p>
                                <p class="mt-1 font-bold text-slate-950 dark:text-white">{{ number_format((float) $entitlement->allocated_days, 2, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Carry-over') }}</p>
                                <p class="mt-1 font-bold text-slate-950 dark:text-white">{{ number_format((float) $entitlement->carried_over_days, 2, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Total') }}</p>
                                <p class="mt-1 font-bold text-slate-950 dark:text-white">{{ number_format($totalAllocated, 2, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Expires') }}</p>
                                <p class="mt-1 font-bold text-slate-950 dark:text-white">{{ $entitlement->expires_at?->translatedFormat('d M Y') ?? __('No expiry') }}</p>
                            </div>
                        </div>

                        @if ($entitlement->notes)
                            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $entitlement->notes }}</p>
                        @endif
                    </article>
                @empty
                    <x-admin.empty-state :title="__('No leave entitlements yet')" :description="__('Create annual leave allocation from the active action panel.')" class="border-0 bg-transparent shadow-none" />
                @endforelse
            </div>

            @if ($entitlements->hasPages())
                <div class="border-t border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    {{ $entitlements->links() }}
                </div>
            @endif
        </x-admin.panel>

        <x-admin.panel class="order-1 xl:order-2">
            <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Assign Entitlement') }}</h2>
                <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('Select the employee and allocation period, then set carry-over and expiry in one focused form.') }}</p>
            </div>
            <form wire:submit.prevent="save" class="space-y-3 p-4">
                <x-forms.select id="leave-entitlement-user" wire:model.live="userId" class="w-full" aria-label="{{ __('Employee') }}">
                    <option value="">{{ __('Employee') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }} · {{ $employee->email }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.input-error for="userId" />

                <x-forms.input type="number" min="2020" max="2100" wire:model.live="year" placeholder="{{ __('Year') }}" />
                <x-forms.input-error for="year" />

                <x-forms.input type="number" min="0" max="366" step="0.5" wire:model.live="allocatedDays" placeholder="{{ __('Allocated days') }}" />
                <x-forms.input-error for="allocatedDays" />

                <x-forms.input type="number" min="0" max="366" step="0.5" wire:model.live="carriedOverDays" placeholder="{{ __('Carry-over days') }}" />
                <x-forms.input-error for="carriedOverDays" />

                <x-forms.input type="date" wire:model.live="expiresAt" />
                <x-forms.input-error for="expiresAt" />

                <x-forms.textarea wire:model.live="notes" rows="3" placeholder="{{ __('Notes optional') }}" />
                <x-forms.input-error for="notes" />

                <x-actions.button type="submit" class="w-full">{{ __('Save Entitlement') }}</x-actions.button>
            </form>
        </x-admin.panel>
    </div>
</x-admin.page-shell>
