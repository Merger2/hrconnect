<x-admin.page-shell :title="__('Overtime Management')" :description="__('Review and manage overtime submissions from your team.')" wire:poll.10s>
    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-8">
                <x-forms.label for="overtime-search" value="{{ __('Search overtime requests') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input id="overtime-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search employee, division, or reason...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-4">
                <x-forms.label for="overtime-status-filter" value="{{ __('Approval Status') }}" class="mb-1.5 block" />
                <x-forms.select id="overtime-status-filter" wire:model.live="statusFilter" class="w-full">
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="all">{{ __('All statuses') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    @php
        $allOvertimes = $overtimes->getCollection();
        $pendingOT = $allOvertimes->where('status', 'pending')->count();
        $approvedOT = $allOvertimes->where('status', 'approved')->count();
        $rejectedOT = $allOvertimes->where('status', 'rejected')->count();
    @endphp

    <dl class="flex flex-wrap gap-2 mb-4" role="region" aria-label="{{ __('Overtime Summary') }}">
        <div class="rounded-xl border border-amber-300/70 bg-amber-50 px-3 py-1.5 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-amber-700">{{ __('Pending') }}</dt>
            <dd class="text-sm font-bold text-amber-800">{{ $pendingOT }}</dd>
        </div>
        <div class="rounded-xl border border-emerald-300/70 bg-emerald-50 px-3 py-1.5 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-emerald-700">{{ __('Approved') }}</dt>
            <dd class="text-sm font-bold text-emerald-800">{{ $approvedOT }}</dd>
        </div>
        <div class="rounded-xl border border-rose-300/70 bg-rose-50 px-3 py-1.5 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-rose-700">{{ __('Rejected') }}</dt>
            <dd class="text-sm font-bold text-rose-800">{{ $rejectedOT }}</dd>
        </div>
    </dl>

    <x-admin.panel>

        <div class="p-0">
            @if ($overtimes->isEmpty())
                <div class="p-4">
                    <x-admin.empty-state :title="__('No Overtime Requests')" :description="__('No overtime requests found for this filter.')"
                        class="border-0 bg-transparent shadow-none">
                        <x-slot name="icon">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">
                                <x-heroicon-o-clock class="h-6 w-6 text-gray-300" />
                            </div>
                        </x-slot>
                    </x-admin.empty-state>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($overtimes as $overtime)
                        @php($employee = $overtime->user)
                        <div
                            class="flex flex-col gap-2.5 p-3 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg
                                        @if ($overtime->status === 'approved') bg-green-100
                                        @elseif($overtime->status === 'rejected') bg-red-100
                                        @else bg-yellow-100 @endif">
                                    <span class="h-2.5 w-2.5 rounded-full
                                        @if ($overtime->status === 'approved')
                                            bg-green-600
                                        @elseif($overtime->status === 'rejected')
                                            bg-red-600
                                        @else
                                            bg-yellow-600
                                        @endif"></span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">
                                        {{ $employee?->name ?? __('Deleted employee') }}
                                    </h4>
                                    <p class="text-xs text-gray-500">
                                        {{ $employee?->division?->name ?? '-' }} •
                                        {{ $employee?->jobTitle?->name ?? '-' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-600">
                                        {{ $overtime->date->format('d M Y') }} •
                                        {{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}
                                        <span
                                            class="text-indigo-600 font-semibold">({{ $overtime->duration_text }})</span>
                                    </p>
                                    @if ($overtime->reason)
                                        <p class="sr-only">
                                            {{ $overtime->reason }}</p>
                                    @endif
                                    @if ($overtime->rejection_reason)
                                        <p class="mt-0.5 text-[10px] text-red-500">{{ __('Reason') }}:
                                            {{ $overtime->rejection_reason }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:shrink-0">
                                @if ($overtime->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="approve('{{ $overtime->id }}')"
                                            variant="success" label="{{ __('Approve overtime request') }}">
                                            <x-heroicon-m-check-circle class="h-6 w-6" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="confirmReject('{{ $overtime->id }}')"
                                            variant="danger" label="{{ __('Reject overtime request') }}">
                                            <x-heroicon-m-x-circle class="h-6 w-6" />
                                        </x-actions.icon-button>
                                    </div>
                                @else
                                    <x-admin.status-badge :tone="$overtime->status === 'approved' ? 'success' : 'danger'" pill="true">
                                        {{ __(ucfirst($overtime->status?->value ?? $overtime->status)) }}
                                    </x-admin.status-badge>
                                    @if ($overtime->approvedBy)
                                        <span class="text-[10px] text-slate-500">{{ __('by') }}
                                            {{ $overtime->approvedBy->name }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div
                    class="border-t border-gray-200/60 bg-gray-50 px-4 py-3">
                    {{ $overtimes->links() }}
                </div>
            @endif
        </div>
    </x-admin.panel>

    {{-- Rejection Modal --}}
    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">
            {{ __('Reject Overtime Request') }}
        </x-slot>

        <x-slot name="content">
            <p class="sr-only">
                {{ __('Please provide a reason for rejection:') }}
            </p>
            <x-forms.textarea wire:model="rejectionReason" rows="3" class="w-full"
                placeholder="{{ __('Reason...') }}" />
            <x-forms.input-error for="rejectionReason" class="mt-2" />
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="cancelReject" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ms-3" wire:click="reject" wire:loading.attr="disabled">
                {{ __('Reject') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
