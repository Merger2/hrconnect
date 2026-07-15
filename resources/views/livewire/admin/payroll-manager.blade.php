<div x-data="{ showGenerateModal: @entangle('showGenerateModal'), showDetailModal: @entangle('showDetailModal'), detailPayroll: @entangle('detailPayroll') }">
    <x-page-shell :title="__('Manajemen Penggajian')" :description="__('Generate dan kelola pembayaran karyawan.')">
        <x-slot name="actions">
            @can('process_payroll')
            <x-button variant="primary" icon="calculate" wire:click="openGenerateModal">
                {{ __('Generate Payroll') }}
            </x-button>
            @endcan
        </x-slot>

        <x-slot name="toolbar">
            <div class="grid gap-3 md:grid-cols-5 lg:grid-cols-12">
                <div class="md:col-span-2 lg:col-span-5">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Cari Karyawan') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant/50">
                            <span class="material-symbols-outlined text-lg">search</span>
                        </span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Nama atau NIP...') }}"
                            class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 pl-11 pr-4 text-sm text-ink placeholder:text-on-surface-variant/40 focus:border-ink focus:ring-1 focus:ring-ink/20"
                        >
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Bulan') }}</label>
                    <select wire:model.live="month" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 pl-3 pr-10 text-sm text-ink focus:border-ink focus:ring-1 focus:ring-ink/20">
                        @foreach ([1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m => $label)
                            <option value="{{ $m }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Tahun') }}</label>
                    <select wire:model.live="year" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 pl-3 pr-10 text-sm text-ink focus:border-ink focus:ring-1 focus:ring-ink/20">
                        @foreach (range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-3">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Status') }}</label>
                    <select wire:model.live="statusFilter" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 pl-3 pr-10 text-sm text-ink focus:border-ink focus:ring-1 focus:ring-ink/20">
                        <option value="all">{{ __('Semua Status') }}</option>
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="published">{{ __('Diterbitkan') }}</option>
                        <option value="paid">{{ __('Dibayar') }}</option>
                    </select>
                </div>
            </div>
        </x-slot>

        @if (count($selectedPayrolls) > 0 && $selectedActionState['has_actions'])
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-outline-variant bg-surface-container-low px-4 py-3">
            <span class="text-sm font-medium text-on-surface-variant">
                {{ trans_choice(':count terpilih', count($selectedPayrolls)) }}
            </span>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                @if ($selectedActionState['can_publish'])
                    <x-button variant="primary" size="sm" wire:click="bulkPublish" icon="publish"
                        {{-- wire:confirm="{{ __('Terbitkan semua payroll draft yang dipilih?') }}" --}}>
                        {{ __('Terbitkan Terpilih') }}
                    </x-button>
                @endif
                @if ($selectedActionState['can_pay'])
                    <x-button variant="success" size="sm" wire:click="bulkPay" icon="paid"
                        {{-- wire:confirm="{{ __('Tandai sebagai sudah dibayar?') }}" --}}>
                        {{ __('Bayar Terpilih') }}
                    </x-button>
                @endif
            </div>
        </div>
        @endif

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
        </div>
    </x-page-shell>

    <x-confirm-modal
        name="generate-payroll"
        :title="__('Generate Payroll')"
        variant="info"
        icon="calculate"
        wire:model="showGenerateModal"
    >
        <x-slot name="default">
            <p>{{ __('Generate payroll untuk') }} <strong>{{ \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') }} {{ $year }}</strong>?</p>
            <p class="sr-only">{{ __('Ini akan menghitung gaji, lembur, dan potongan untuk semua karyawan yang memenuhi syarat.') }}</p>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" @click="showGenerateModal = false" wire:loading.attr="disabled">
                {{ __('Batal') }}
            </x-button>
            <x-button variant="primary" wire:click="generate" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">{{ __('Generate') }}</span>
                <span wire:loading wire:target="generate">{{ __('Memproses...') }}</span>
            </x-button>
        </x-slot>
    </x-confirm-modal>

    <template x-teleport="body">
        <template x-if="showDetailModal && detailPayroll">
            <div class="fixed inset-0 z-[90] overflow-y-auto" x-transition>
                <div class="flex min-h-[100dvh] items-start justify-center px-4 py-[calc(1rem+env(safe-area-inset-top))] text-center sm:items-center sm:px-6">
                    <div class="fixed inset-0 z-0 bg-black/50 backdrop-blur-sm transition-opacity"
                        @click="showDetailModal = false; $wire.closeDetail()"></div>
                    <div class="relative z-10 w-full overflow-hidden rounded-2xl bg-canvas text-left shadow-lg transition-all sm:my-8 sm:max-w-lg"
                        style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
                        @click.stop
                        role="dialog" aria-modal="true" aria-labelledby="payroll-detail-title">
                        <div class="flex items-center justify-between border-b border-outline-variant px-6 py-4">
                            <div>
                                <h3 id="payroll-detail-title" class="text-lg font-bold text-ink" x-text="detailPayroll.name"></h3>
                                <p class="text-sm text-on-surface-variant">
                                    <span x-text="detailPayroll.employee_number"></span> &middot; <span x-text="detailPayroll.position"></span>
                                </p>
                            </div>
                            <button type="button" @click="showDetailModal = false; $wire.closeDetail()"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-surface-dim">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        <div class="space-y-4 overflow-y-auto px-6 py-4" style="max-height: calc(100dvh - 12rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));">
                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">{{ __('Periode') }}</span>
                                <span class="font-medium text-ink" x-text="detailPayroll.period"></span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">{{ __('Gaji Pokok') }}</span>
                                <span class="font-medium text-ink" x-text="'Rp ' + Number(detailPayroll.basic_salary).toLocaleString('id-ID')"></span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">{{ __('Tunjangan Jabatan') }}</span>
                                <span class="font-medium text-ink" x-text="'Rp ' + Number(detailPayroll.total_allowance).toLocaleString('id-ID')"></span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">{{ __('Lembur') }}</span>
                                <span class="font-medium text-ink" x-text="'Rp ' + Number(detailPayroll.overtime_pay).toLocaleString('id-ID')"></span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">{{ __('Penghasilan Bruto') }}</span>
                                <span class="font-semibold text-ink" x-text="'Rp ' + Number(detailPayroll.gross_salary).toLocaleString('id-ID')"></span>
                            </div>

                            <div class="border-t border-outline-variant pt-4">
                                <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-error">{{ __('Potongan') }}</h4>

                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-on-surface-variant">{{ __('PPh21') }}</span>
                                    <span class="text-error" x-text="'-Rp ' + Number(detailPayroll.pph21).toLocaleString('id-ID')"></span>
                                </div>
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-on-surface-variant">{{ __('BPJS Kesehatan') }}</span>
                                    <span class="text-error" x-text="'-Rp ' + Number(detailPayroll.bpjs_health).toLocaleString('id-ID')"></span>
                                </div>
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-on-surface-variant">{{ __('BPJS Ketenagakerjaan') }}</span>
                                    <span class="text-error" x-text="'-Rp ' + Number(detailPayroll.bpjs_employment).toLocaleString('id-ID')"></span>
                                </div>
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-on-surface-variant">{{ __('Denda Kehadiran') }}</span>
                                    <span class="text-error" x-text="'-Rp ' + Number(detailPayroll.attendance_penalty).toLocaleString('id-ID')"></span>
                                </div>
                                @if (!empty($detailPayroll['loan_deduction']))
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-on-surface-variant">{{ __('Cicilan Pinjaman') }}</span>
                                    <span class="text-error" x-text="'-Rp ' + Number(detailPayroll.loan_deduction).toLocaleString('id-ID')"></span>
                                </div>
                                @endif

                                <div class="mt-2 flex justify-between border-t border-outline-variant/50 pt-2 text-sm font-bold">
                                    <span class="text-on-surface-variant">{{ __('Total Potongan') }}</span>
                                    <span class="text-error" x-text="'-Rp ' + Number(detailPayroll.total_deduction).toLocaleString('id-ID')"></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-2xl border border-success/40 bg-success/5 p-4">
                                <span class="text-sm font-bold uppercase tracking-wider text-success">{{ __('Gaji Bersih') }}</span>
                                <span class="text-xl font-bold text-success" x-text="'Rp ' + Number(detailPayroll.net_salary).toLocaleString('id-ID')"></span>
                            </div>
                        </div>
                        <div class="flex justify-end border-t border-outline-variant bg-surface-dim/30 px-6 py-3">
                            <x-button variant="secondary" @click="showDetailModal = false; $wire.closeDetail()">
                                {{ __('Tutup') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </template>
</div>
