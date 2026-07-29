<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Kelola Penggajian</h1>
            <p class="text-sm text-zinc-500">Generate, publish, dan kelola payroll periode.</p>
        </div>
    </div>

<<<<<<< HEAD
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Bruto') }}</dt>
                <dd class="ess-stat__value">Rp {{ number_format($summaryCards['total_gross'], 0, ',', '.') }}</dd>
            </div>
            <div class="ess-stat" style="border-color: var(--color-success);">
                <dt class="ess-stat__label text-success">{{ __('Neto') }}</dt>
                <dd class="ess-stat__value text-success">Rp {{ number_format($summaryCards['total_net'], 0, ',', '.') }}</dd>
            </div>
            <div class="ess-stat" style="border-color: var(--color-error);">
                <dt class="ess-stat__label text-error">{{ __('Potongan') }}</dt>
                <dd class="ess-stat__value text-error">Rp {{ number_format($summaryCards['total_deduction'], 0, ',', '.') }}</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Status') }}</dt>
                <dd class="mt-1 flex flex-wrap items-center gap-1">
                    <x-status-badge :tone="'neutral'" :pill="true">{{ $summaryCards['draft_count'] }} Draft</x-status-badge>
                    <x-status-badge :tone="'info'" :pill="true">{{ $summaryCards['published_count'] }} Pub</x-status-badge>
                    <x-status-badge :tone="'success'" :pill="true">{{ $summaryCards['paid_count'] }} Paid</x-status-badge>
                </dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Karyawan') }}</dt>
                <dd class="ess-stat__value">{{ $summaryCards['employee_count'] }}</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Periode') }}</dt>
                <dd class="text-sm font-bold text-ink">{{ \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') }} {{ $year }}</dd>
            </div>
        </dl>

        <div class="lg:hidden space-y-3">
            @forelse ($payrolls as $payroll)
                <article class="rounded-2xl border border-outline-variant bg-surface-container-low p-4">
                    <div class="flex items-start gap-3">
                        @can('process_payroll')
                            <input type="checkbox" wire:model.live="selectedPayrolls" value="{{ $payroll->id }}" class="mt-1 h-4 w-4 rounded border-outline-variant text-ink focus:ring-ink/20">
                        @endcan
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-dim">
                            <span class="material-symbols-outlined text-xl text-on-surface-variant">person</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-ink">{{ $payroll->employee?->full_name ?? '-' }}</h3>
                            <p class="truncate text-xs text-on-surface-variant">
                                {{ $payroll->employee?->employee_number ?? '-' }} &middot; {{ $payroll->employee?->position?->name ?? '-' }}
                            </p>
                        </div>
                        <x-status-badge :tone="$payroll->status->color()" :pill="true">{{ $payroll->status->value }}</x-status-badge>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <dt class="text-[11px] text-on-surface-variant">{{ __('Gaji Pokok') }}</dt>
                            <dd class="mt-0.5 font-medium text-ink">Rp {{ number_format($payroll->basic_salary ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-on-surface-variant">{{ __('Lembur') }}</dt>
                            <dd class="mt-0.5 text-ink">Rp {{ number_format($payroll->overtime_pay ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-on-surface-variant">{{ __('Potongan') }}</dt>
                            <dd class="mt-0.5 text-error">-Rp {{ number_format($payroll->total_deduction ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-on-surface-variant">{{ __('Bersih') }}</dt>
                            <dd class="mt-0.5 font-semibold text-ink">Rp {{ number_format($payroll->net_salary ?? 0, 0, ',', '.') }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3 flex items-center justify-end gap-1">
                        @can('view_payrolls')
                            <x-button variant="ghost" size="sm" wire:click="showDetail({{ $payroll->id }})" icon="visibility">
                                {{ __('Detail') }}
                            </x-button>
                        @endcan
                        @can('process_payroll')
                            @if ($payroll->status === \App\Enums\PayrollStatus::DRAFT)
                                <x-button variant="primary" size="sm" wire:click="publish({{ $payroll->id }})" icon="publish">
                                    {{ __('Terbitkan') }}
                                </x-button>
                            @endif
                            @if ($payroll->status === \App\Enums\PayrollStatus::PUBLISHED)
                                <x-button variant="success" size="sm" wire:click="pay({{ $payroll->id }})" icon="paid">
                                    {{ __('Bayar') }}
                                </x-button>
                            @endif
                        @endcan
                    </div>
                </article>
            @empty
                <x-empty-state
                    :title="__('Tidak ada data payroll untuk periode ini.')"
                    :description="__('Klik Generate Payroll untuk memproses penggajian.')"
                    framed
                >
                    <x-slot name="icon">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/50">calculate</span>
                    </x-slot>
                    @can('process_payroll')
                        <x-slot name="actions">
                            <x-button variant="primary" wire:click="openGenerateModal" icon="calculate">
                                {{ __('Generate Sekarang') }}
                            </x-button>
                        </x-slot>
                    @endcan
                </x-empty-state>
            @endforelse
        </div>

        <div class="hidden lg:block overflow-hidden rounded-xl border border-outline-variant shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-dim">
                        <tr>
                            @can('process_payroll')
                            <th scope="col" class="w-10 px-4 py-3 text-center">
                                <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-outline-variant text-ink focus:ring-ink/20">
                            </th>
                            @endcan
                            <th scope="col" class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Karyawan') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Gaji Pokok') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Lembur') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Tunjangan') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Potongan') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Bersih') }}</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50 bg-canvas">
                        @forelse ($payrolls as $payroll)
                            <tr class="transition-colors hover:bg-surface-dim/40">
                                @can('process_payroll')
                                <td class="px-4 py-3.5 text-center">
                                    <input type="checkbox" wire:model.live="selectedPayrolls" value="{{ $payroll->id }}" class="h-4 w-4 rounded border-outline-variant text-ink focus:ring-ink/20">
                                </td>
                                @endcan
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-dim">
                                            <span class="material-symbols-outlined text-xl text-on-surface-variant">person</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-ink">{{ $payroll->employee?->full_name ?? '-' }}</div>
                                            <div class="text-xs text-on-surface-variant">
                                                {{ $payroll->employee?->employee_number ?? '-' }} &middot; {{ $payroll->employee?->position?->name ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-on-surface-variant">
                                    Rp {{ number_format($payroll->basic_salary ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-on-surface-variant">
                                    Rp {{ number_format($payroll->overtime_pay ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-on-surface-variant">
                                    Rp {{ number_format($payroll->total_allowance ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-error">
                                    -Rp {{ number_format($payroll->total_deduction ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-ink">
                                    Rp {{ number_format($payroll->net_salary ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <x-status-badge :tone="$payroll->status->color()" :pill="true">{{ $payroll->status->value }}</x-status-badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('view_payrolls')
                                            <x-button variant="ghost" size="sm" wire:click="showDetail({{ $payroll->id }})" icon="visibility">
                                                {{ __('Detail') }}
                                            </x-button>
                                        @endcan
                                        @can('process_payroll')
                                            @if ($payroll->status === \App\Enums\PayrollStatus::DRAFT)
                                                <x-button variant="primary" size="sm" wire:click="publish({{ $payroll->id }})" icon="publish">
                                                    {{ __('Terbitkan') }}
                                                </x-button>
                                            @endif
                                            @if ($payroll->status === \App\Enums\PayrollStatus::PUBLISHED)
                                                <x-button variant="success" size="sm" wire:click="pay({{ $payroll->id }})" icon="paid">
                                                    {{ __('Bayar') }}
                                                </x-button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/30">calculate</span>
                                        <p class="mt-3 text-sm text-on-surface-variant">{{ __('Tidak ada data payroll untuk periode ini.') }}</p>
                                        @can('process_payroll')
                                            <x-button variant="primary" size="sm" class="mt-3" wire:click="openGenerateModal" icon="add">
                                                {{ __('Generate Payroll') }}
                                            </x-button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payrolls->hasPages())
            <div class="border-t border-outline-variant/50 bg-canvas px-4 py-3">
                {{ $payrolls->links() }}
            </div>
            @endif
=======
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Cari karyawan</label>
            <input type="text" wire:model.debounce.300ms="search" class="w-full rounded-md border-zinc-300" placeholder="Nama / NIP">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Periode</label>
            <input type="month" wire:model.live="periodFilter" class="w-full rounded-md border-zinc-300">
>>>>>>> main
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select wire:model.live="statusFilter" class="w-full rounded-md border-zinc-300">
                <option value="">Semua</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-600">
                <tr>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Karyawan</th>
                    <th class="px-4 py-3 text-right">Gross</th>
                    <th class="px-4 py-3 text-right">Net</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white">
                @forelse($payrolls as $payroll)
                    <tr>
                        <td class="px-4 py-3">{{ $payroll->period }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $payroll->employee?->full_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $payroll->employee?->employee_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                {{ match($payroll->status?->value) {
                                    'draft' => 'bg-zinc-100 text-zinc-800',
                                    'submitted' => 'bg-amber-100 text-amber-800',
                                    'verified' => 'bg-blue-100 text-blue-800',
                                    'approved' => 'bg-emerald-100 text-emerald-800',
                                    'paid' => 'bg-teal-100 text-teal-800',
                                    default => 'bg-zinc-100 text-zinc-800'
                                } }}">
                                {{ $payroll->status?->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                @if($payroll->status === \App\Enums\PayrollStatus::DRAFT)
                                    <button wire:click="submit({{ $payroll->id }})" class="text-amber-600 hover:text-amber-700 text-xs font-semibold">Ajukan</button>
                                @endif

                                @if($payroll->status === \App\Enums\PayrollStatus::SUBMITTED)
                                    <button wire:click="verify({{ $payroll->id }})" class="text-blue-600 hover:text-blue-700 text-xs font-semibold">Verifikasi</button>
                                    <button wire:click="confirmReject({{ $payroll->id }})" class="text-red-600 hover:text-red-700 text-xs font-semibold">Tolak</button>
                                @endif

                                @if($payroll->status === \App\Enums\PayrollStatus::VERIFIED)
                                    <button wire:click="approve({{ $payroll->id }})" class="text-emerald-600 hover:text-emerald-700 text-xs font-semibold">Setujui</button>
                                    <button wire:click="confirmReject({{ $payroll->id }})" class="text-red-600 hover:text-red-700 text-xs font-semibold">Tolak</button>
                                @endif

                                @if($payroll->status === \App\Enums\PayrollStatus::APPROVED)
                                    <button wire:click="markPaid({{ $payroll->id }})" class="text-teal-600 hover:text-teal-700 text-xs font-semibold">Tandai Ditransfer</button>
                                    <button wire:click="downloadPayslip({{ $payroll->id }})" class="text-zinc-700 hover:text-black text-xs font-semibold">Payslip</button>
                                @endif

                                @if($payroll->status === \App\Enums\PayrollStatus::PAID)
                                    <button wire:click="downloadPayslip({{ $payroll->id }})" class="text-zinc-700 hover:text-black text-xs font-semibold">Payslip</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-zinc-500">Belum ada data payroll.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payrolls->links() }}
    </div>

    @if($rejectingPayrollId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md space-y-4">
                <h3 class="text-lg font-semibold">Tolak Payroll</h3>
                <p class="text-sm text-zinc-500">Berikan alasan penolakan payroll ini.</p>
                <textarea wire:model="rejectionReason" rows="3" class="w-full rounded-md border-zinc-300" placeholder="Alasan penolakan..."></textarea>
                @error('rejectionReason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-2">
                    <button wire:click="cancelReject" class="px-4 py-2 text-sm rounded-md border border-zinc-300 hover:bg-zinc-50">Batal</button>
                    <button wire:click="reject" class="px-4 py-2 text-sm rounded-md bg-red-600 text-white hover:bg-red-700">Tolak Payroll</button>
                </div>
            </div>
        </div>
    @endif
</div>
