<div>
    <x-admin.page-shell :title="__('Kelola Penggajian')" :description="__('Generate, publish, dan kelola payroll periode.')">
        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="md:col-span-2">
                    <x-forms.label for="payroll-search" class="mb-1.5 block">{{ __('Cari karyawan') }}</x-forms.label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400" aria-hidden="true">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                        </span>
                        <x-forms.input id="payroll-search" type="search" wire:model.debounce.300ms="search"
                            placeholder="Nama / NIP" class="w-full pl-11" />
                    </div>
                </div>
                <div>
                    <x-forms.label for="payroll-period" class="mb-1.5 block">{{ __('Periode') }}</x-forms.label>
                    <x-forms.input id="payroll-period" type="month" wire:model.live="periodFilter" class="w-full" />
                </div>
                <div>
                    <x-forms.label for="payroll-status" class="mb-1.5 block">{{ __('Status') }}</x-forms.label>
                    <x-forms.select id="payroll-status" wire:model.live="statusFilter" class="w-full">
                        <option value="">Semua</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-forms.select>
                </div>
            </x-admin.page-tools>
        </x-slot>

        @php
            $pageItems = collect($payrolls->items());
            $actionableCount = $pageItems->whereIn('status', [
                \App\Enums\PayrollStatus::DRAFT,
                \App\Enums\PayrollStatus::SUBMITTED,
                \App\Enums\PayrollStatus::VERIFIED,
            ])->count();
        @endphp

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <span class="absolute inset-x-0 top-0 h-1 bg-module-payroll" aria-hidden="true"></span>
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-module-payroll/10 text-module-payroll">
                        <x-heroicon-o-banknotes class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Total Payroll</p>
                        <p class="truncate text-lg font-bold text-slate-950">{{ number_format($payrolls->total(), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-slate-500">Semua status &amp; filter</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <span class="absolute inset-x-0 top-0 h-1 bg-module-payroll" aria-hidden="true"></span>
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-module-payroll/10 text-module-payroll">
                        <x-heroicon-o-currency-dollar class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Total Gross</p>
                        <p class="truncate text-lg font-bold text-slate-950">Rp {{ number_format($pageItems->sum('gross_salary'), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-slate-500">Halaman ini</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <span class="absolute inset-x-0 top-0 h-1 bg-module-payroll" aria-hidden="true"></span>
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-module-payroll/10 text-module-payroll">
                        <x-heroicon-o-wallet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Total Net</p>
                        <p class="truncate text-lg font-bold text-slate-950">Rp {{ number_format($pageItems->sum('net_salary'), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-slate-500">Halaman ini</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm">
                <span class="absolute inset-x-0 top-0 h-1 bg-module-payroll" aria-hidden="true"></span>
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-module-payroll/10 text-module-payroll">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Perlu Tindakan</p>
                        <p class="truncate text-lg font-bold text-slate-950">{{ $actionableCount }}</p>
                        <p class="text-[11px] text-slate-500">Draft / Diajukan / Diverifikasi</p>
                    </div>
                </div>
            </div>
        </div>

        <x-admin.panel>
            {{-- Desktop: table hanya di lg ke atas, mobile pakai kartu --}}
            <div class="hidden overflow-x-auto admin-table-scroll lg:block">
                <table class="min-w-full text-sm">
                    <thead class="bg-module-payroll/5 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Periode</th>
                            <th class="px-4 py-3 text-left font-semibold">Karyawan</th>
                            <th class="px-4 py-3 text-right font-semibold">Gross</th>
                            <th class="px-4 py-3 text-right font-semibold">Net</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($payrolls as $payroll)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700">{{ $payroll->period }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $payroll->employee?->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $payroll->employee?->employee_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <x-admin.status-badge :tone="match($payroll->status?->value) {
                                        'draft' => 'neutral',
                                        'submitted' => 'warning',
                                        'verified' => 'info',
                                        'approved' => 'success',
                                        'paid' => 'primary',
                                        default => 'neutral'
                                    }">
                                        {{ $payroll->status?->label() }}
                                    </x-admin.status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($payroll->status === \App\Enums\PayrollStatus::DRAFT)
                                            <x-actions.button type="button" wire:click="submit({{ $payroll->id }})" variant="soft-primary" size="sm">
                                                {{ __('Ajukan') }}
                                            </x-actions.button>
                                        @endif

                                        @if($payroll->status === \App\Enums\PayrollStatus::SUBMITTED)
                                            <x-actions.button type="button" wire:click="verify({{ $payroll->id }})" variant="soft-primary" size="sm">
                                                {{ __('Verifikasi') }}
                                            </x-actions.button>
                                            <x-actions.button type="button" wire:click="confirmReject({{ $payroll->id }})" variant="soft-danger" size="sm">
                                                {{ __('Tolak') }}
                                            </x-actions.button>
                                        @endif

                                        @if($payroll->status === \App\Enums\PayrollStatus::VERIFIED)
                                            <x-actions.button type="button" wire:click="approve({{ $payroll->id }})" variant="soft-success" size="sm">
                                                {{ __('Setujui') }}
                                            </x-actions.button>
                                            <x-actions.button type="button" wire:click="confirmReject({{ $payroll->id }})" variant="soft-danger" size="sm">
                                                {{ __('Tolak') }}
                                            </x-actions.button>
                                        @endif

                                        @if($payroll->status === \App\Enums\PayrollStatus::APPROVED)
                                            <x-actions.button type="button" wire:click="markPaid({{ $payroll->id }})" variant="soft-success" size="sm">
                                                {{ __('Tandai Ditransfer') }}
                                            </x-actions.button>
                                            <x-actions.button href="{{ route('payslip.download', $payroll) }}" variant="secondary" size="sm">
                                                {{ __('Payslip') }}
                                            </x-actions.button>
                                        @endif

                                        @if($payroll->status === \App\Enums\PayrollStatus::PAID)
                                            <x-actions.button href="{{ route('payslip.download', $payroll) }}" variant="secondary" size="sm">
                                                {{ __('Payslip') }}
                                            </x-actions.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada data payroll.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile: kartu stacked --}}
            <div class="divide-y divide-slate-100 lg:hidden">
                @forelse($payrolls as $payroll)
                    <div class="px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md bg-module-payroll/10 px-2 py-0.5 text-xs font-semibold text-module-payroll">{{ $payroll->period }}</span>
                                </div>
                                <div class="mt-2 font-medium text-slate-900">{{ $payroll->employee?->full_name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $payroll->employee?->employee_number ?? '-' }}</div>
                            </div>
                            <x-admin.status-badge :tone="match($payroll->status?->value) {
                                'draft' => 'neutral',
                                'submitted' => 'warning',
                                'verified' => 'info',
                                'approved' => 'success',
                                'paid' => 'primary',
                                default => 'neutral'
                            }">
                                {{ $payroll->status?->label() }}
                            </x-admin.status-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <dt class="text-[11px] font-medium text-slate-500">Gross</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</dd>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <dt class="text-[11px] font-medium text-slate-500">Net</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if($payroll->status === \App\Enums\PayrollStatus::DRAFT)
                                <x-actions.button type="button" wire:click="submit({{ $payroll->id }})" variant="soft-primary" size="sm">
                                    {{ __('Ajukan') }}
                                </x-actions.button>
                            @endif

                            @if($payroll->status === \App\Enums\PayrollStatus::SUBMITTED)
                                <x-actions.button type="button" wire:click="verify({{ $payroll->id }})" variant="soft-primary" size="sm">
                                    {{ __('Verifikasi') }}
                                </x-actions.button>
                                <x-actions.button type="button" wire:click="confirmReject({{ $payroll->id }})" variant="soft-danger" size="sm">
                                    {{ __('Tolak') }}
                                </x-actions.button>
                            @endif

                            @if($payroll->status === \App\Enums\PayrollStatus::VERIFIED)
                                <x-actions.button type="button" wire:click="approve({{ $payroll->id }})" variant="soft-success" size="sm">
                                    {{ __('Setujui') }}
                                </x-actions.button>
                                <x-actions.button type="button" wire:click="confirmReject({{ $payroll->id }})" variant="soft-danger" size="sm">
                                    {{ __('Tolak') }}
                                </x-actions.button>
                            @endif

                            @if($payroll->status === \App\Enums\PayrollStatus::APPROVED)
                                <x-actions.button type="button" wire:click="markPaid({{ $payroll->id }})" variant="soft-success" size="sm">
                                    {{ __('Tandai Ditransfer') }}
                                </x-actions.button>
                                <x-actions.button href="{{ route('payslip.download', $payroll) }}" variant="secondary" size="sm">
                                    {{ __('Payslip') }}
                                </x-actions.button>
                            @endif

                            @if($payroll->status === \App\Enums\PayrollStatus::PAID)
                                <x-actions.button href="{{ route('payslip.download', $payroll) }}" variant="secondary" size="sm">
                                    {{ __('Payslip') }}
                                </x-actions.button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-slate-500">Belum ada data payroll.</div>
                @endforelse
            </div>
        </x-admin.panel>

        <div>
            {{ $payrolls->links() }}
        </div>
    </x-admin.page-shell>

    @if($rejectingPayrollId)
        <template x-teleport="body">
            <div class="jetstream-modal fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]" role="dialog" aria-modal="true" aria-labelledby="payroll-reject-title">
                <div class="fixed inset-0 z-0 bg-gray-500 opacity-75" wire:click="cancelReject"></div>
                <div class="relative z-10 w-full transform rounded-xl bg-white p-6 shadow-xl transition-all sm:mx-auto sm:max-w-md"
                    style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
                    x-trap.inert.noscroll="true">
                    <h3 id="payroll-reject-title" class="text-lg font-semibold">Tolak Payroll</h3>
                    <p class="text-sm text-zinc-500">Berikan alasan penolakan payroll ini.</p>
                    <textarea wire:model="rejectionReason" rows="3" class="w-full rounded-md border-zinc-300" placeholder="Alasan penolakan..."></textarea>
                    @error('rejectionReason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    <div class="flex justify-end gap-2">
                        <button wire:click="cancelReject" class="px-4 py-2 text-sm rounded-md border border-zinc-300 hover:bg-zinc-50">Batal</button>
                        <button wire:click="reject" class="px-4 py-2 text-sm rounded-md bg-red-600 text-white hover:bg-red-700">Tolak Payroll</button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
