<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Kelola Penggajian</h1>
            <p class="text-sm text-zinc-500">Generate, publish, dan kelola payroll periode.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Cari karyawan</label>
            <input type="text" wire:model.debounce.300ms="search" class="w-full rounded-md border-zinc-300" placeholder="Nama / NIP">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Periode</label>
            <input type="month" wire:model.live="periodFilter" class="w-full rounded-md border-zinc-300">
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
