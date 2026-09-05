<x-admin.page-shell :title="__('Reimbursement Requests')" :description="__('Manage and approve employee expense claims.')">
    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-8">
                <x-forms.label for="reimbursement-search" value="{{ __('Search reimbursement requests') }}"
                    class="mb-1.5 block" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <x-forms.input id="reimbursement-search" wire:model.live.debounce.300ms="search" type="search"
                        placeholder="{{ __('Search employee, type, or description...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-4">
                <x-forms.label for="reimbursement-status-filter" value="{{ __('Approval Status') }}"
                    class="mb-1.5 block" />
                <x-forms.select id="reimbursement-status-filter" wire:model.live="statusFilter" class="w-full">
                    <option value="all">{{ __('All statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="pending_finance">{{ __('Pending Finance') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    @php
        $allClaims = $reimbursements->getCollection();
        $claimStatusOf = fn ($c) => $c->status?->value ?? $c->status;
        $pendingClaims = $allClaims->filter(fn ($c) => in_array($claimStatusOf($c), ['pending', 'pending_finance']))->count();
        $approvedClaims = $allClaims->filter(fn ($c) => $claimStatusOf($c) === 'approved')->count();
        $rejectedClaims = $allClaims->filter(fn ($c) => $claimStatusOf($c) === 'rejected')->count();
        $totalAmount = $allClaims->sum('amount');
    @endphp

    <dl class="flex flex-wrap gap-2 mb-4" role="region" aria-label="{{ __('Reimbursement Summary') }}">
        <div class="rounded-xl border border-amber-300/70 bg-amber-50 px-3 py-1.5 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-amber-700">{{ __('Pending') }}</dt>
            <dd class="text-sm font-bold text-amber-800">{{ $pendingClaims }}</dd>
        </div>
        <div class="rounded-xl border border-emerald-300/70 bg-emerald-50 px-3 py-1.5 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-emerald-700">{{ __('Approved') }}</dt>
            <dd class="text-sm font-bold text-emerald-800">{{ $approvedClaims }}</dd>
        </div>
        <div class="rounded-xl border border-rose-300/70 bg-rose-50 px-3 py-1.5 flex items-center gap-2">
            <dt class="text-xs font-semibold uppercase text-rose-700">{{ __('Rejected') }}</dt>
            <dd class="text-sm font-bold text-rose-800">{{ $rejectedClaims }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 bg-white px-3 py-1.5">
            <dt class="text-xs font-semibold uppercase text-slate-600">{{ __('Total') }}</dt>
            <dd class="text-sm font-bold text-slate-900">Rp {{ number_format($totalAmount, 0, ',', '.') }}</dd>
        </div>
    </dl>

    <x-admin.panel>
        <div class="space-y-3 p-4 lg:hidden">
            @forelse($reimbursements as $claim)
                @php
                    $claimStatus = $claim->status?->value ?? $claim->status;
                    $employee = $claim->user;
                    $employeeName = $employee?->name ?? __('Deleted employee');
                    $employeeEmail = $employee?->email ?? __('Employee record not found');
                    $canApprove = in_array($claimStatus, ['pending', 'pending_finance'], true)
                        && Auth::user()?->can('approve', $claim);
                @endphp
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 overflow-hidden rounded-full bg-gray-100">
                            @if ($employee)
                                <img src="{{ $employee->profile_photo_url }}" alt="{{ $employeeName }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-gray-400">
                                    <x-heroicon-o-user class="h-5 w-5" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-gray-900">{{ $employeeName }}</h3>
                            <p class="truncate text-xs text-gray-500">{{ $employeeEmail }}</p>
                        </div>
                        <x-admin.status-badge :tone="$claimStatus === 'approved' ? 'success' : ($claimStatus === 'rejected' ? 'danger' : ($claimStatus === 'pending_finance' ? 'accent' : 'warning'))">
                            {{ $claim->status instanceof \BackedEnum ? $claim->status->label() : __(ucfirst((string) $claimStatus)) }}
                        </x-admin.status-badge>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500">{{ __('Date') }}</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ \Carbon\Carbon::parse($claim->date)->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">{{ __('Amount') }}</dt>
                            <dd class="mt-1 font-semibold text-gray-900">Rp {{ number_format($claim->amount, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">{{ __('Type') }}</dt>
                            <dd class="mt-1 capitalize text-gray-700">{{ __($claim->type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">{{ __('Attachment') }}</dt>
                            <dd class="mt-1">
                                @if ($claim->attachment)
                                    <a href="{{ route('reimbursement.attachment.download', $claim) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-primary-600">
                                        <x-heroicon-m-paper-clip class="h-4 w-4" /> {{ __('View') }}
                                    </a>
                                @else
                                    <span class="text-slate-500 text-xs">{{ __('No File') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($claim->description)
                        <p class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-600">{{ $claim->description }}</p>
                    @endif
                    @if ($claim->accounting_journal_entry_id)
                        <p class="mt-3 text-xs font-semibold text-emerald-600">
                            {{ __('Posted to journal #:id', ['id' => $claim->accounting_journal_entry_id]) }}
                        </p>
                    @endif

                    <div class="mt-4 flex justify-end gap-2">
                        @if ($canApprove)
                            <x-actions.icon-button wire:click="approve('{{ $claim->id }}')" wire:confirm="{{ __('Approve this claim?') }}" variant="success" label="{{ __('Approve reimbursement claim from') }} {{ $employeeName }}">
                                <x-heroicon-m-check-circle class="h-5 w-5" />
                            </x-actions.icon-button>
                            <x-actions.icon-button wire:click="reject('{{ $claim->id }}')" wire:confirm="{{ __('Reject this claim?') }}" variant="danger" label="{{ __('Reject reimbursement claim from') }} {{ $employeeName }}">
                                <x-heroicon-m-x-circle class="h-5 w-5" />
                            </x-actions.icon-button>
                        @else
                            <span class="text-xs text-slate-500">{{ __('Completed') }}</span>
                        @endif
                    </div>
                </article>
            @empty
                <x-admin.empty-state :title="__('No requests found')" class="border border-dashed border-gray-200 bg-white">
                    <x-slot name="icon">
                        <x-heroicon-o-currency-dollar class="h-12 w-12 text-gray-300" />
                    </x-slot>
                </x-admin.empty-state>
            @endforelse
        </div>

        <div class="hidden lg:block">
            <table class="w-full whitespace-nowrap text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Amount') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Description') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Attachment') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reimbursements as $claim)
                        @php
                            $claimStatus = $claim->status?->value ?? $claim->status;
                            $employee = $claim->user;
                            $employeeName = $employee?->name ?? __('Deleted employee');
                            $employeeEmail = $employee?->email ?? __('Employee record not found');
                        @endphp
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 overflow-hidden rounded-full bg-gray-100">
                                        @if ($employee)
                                            <img src="{{ $employee->profile_photo_url }}"
                                                alt="{{ $employeeName }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-gray-400">
                                                <x-heroicon-o-user class="h-5 w-5" />
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $employeeName }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $employeeEmail }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ \Carbon\Carbon::parse($claim->date)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 capitalize text-gray-600">
                                {{ __($claim->type) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                Rp {{ number_format($claim->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                                {{ $claim->description }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if ($claim->attachment)
                                    <a href="{{ route('reimbursement.attachment.download', $claim) }}" target="_blank"
                                        rel="noopener noreferrer"
                                        class="wcag-touch-target flex items-center gap-1 rounded text-primary-600 transition-colors hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2">
                                        <x-heroicon-m-paper-clip class="h-4 w-4" />
                                        <span>{{ __('View') }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-500 text-xs">{{ __('No File') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.status-badge :tone="$claimStatus === 'approved' ? 'success' : ($claimStatus === 'rejected' ? 'danger' : ($claimStatus === 'pending_finance' ? 'accent' : 'warning'))">
                                    {{ $claim->status instanceof \BackedEnum ? $claim->status->label() : __(ucfirst((string) $claimStatus)) }}
                                </x-admin.status-badge>
                                @if ($claim->accounting_journal_entry_id)
                                    <div class="mt-1 text-[10px] font-semibold text-emerald-600">
                                        {{ __('Journal #:id', ['id' => $claim->accounting_journal_entry_id]) }}
                                    </div>
                                @endif
                                @if ($claimStatus !== 'pending')
                                    <div class="mt-1 flex flex-col gap-0.5 w-[140px]">
                                        @if ($claim->head_approved_by)
                                            <span
                                                class="text-[10px] text-gray-500 flex items-center gap-1">
                                                <x-heroicon-o-user class="w-3 h-3 text-purple-500 shrink-0" />
                                                <span class="truncate">Head:
                                                    {{ $claim->headApprover->name ?? '-' }}</span>
                                            </span>
                                        @endif
                                        @if ($claim->finance_approved_by || $claim->approved_by)
                                            <span
                                                class="text-[10px] text-gray-500 flex items-center gap-1">
                                                <x-heroicon-o-user class="w-3 h-3 text-green-500 shrink-0" />
                                                <span class="truncate">Finance:
                                                    {{ $claim->financeApprover->name ?? ($claim->approvedBy->name ?? '-') }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @php
                                    $canApprove = in_array($claimStatus, ['pending', 'pending_finance'], true)
                                        && Auth::user()?->can('approve', $claim);
                                @endphp
                                @if ($canApprove)
                                    <div class="flex items-center justify-end gap-2">
                                        <x-actions.icon-button wire:click="approve('{{ $claim->id }}')"
                                            wire:confirm="{{ __('Approve this claim?') }}" variant="success"
                                            label="{{ __('Approve reimbursement claim from') }} {{ $employeeName }}">
                                            <x-heroicon-m-check-circle class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="reject('{{ $claim->id }}')"
                                            wire:confirm="{{ __('Reject this claim?') }}" variant="danger"
                                            label="{{ __('Reject reimbursement claim from') }} {{ $employeeName }}">
                                            <x-heroicon-m-x-circle class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">{{ __('Completed') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <x-heroicon-o-currency-dollar
                                        class="h-12 w-12 text-gray-300 mb-3" />
                                    <p class="font-medium">{{ __('No requests found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reimbursements->hasPages())
            <div
                class="border-t border-gray-200/60 bg-gray-50 px-4 py-2.5">
                {{ $reimbursements->links() }}
            </div>
        @endif
    </x-admin.panel>
</x-admin.page-shell>
