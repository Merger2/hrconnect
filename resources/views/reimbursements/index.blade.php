<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('Reimbursements') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('View and manage your reimbursement claims') }}</p>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('reimbursements.apply') }}" class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" wire:navigate>
            <span class="material-symbols-outlined text-base">add</span>
            {{ __('New Claim') }}
        </a>
    </div>

    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant/50 text-left text-sm font-semibold text-on-surface-variant">
                <th class="py-3 pr-4">{{ __('Category') }}</th>
                <th class="py-3 pr-4">{{ __('Amount') }}</th>
                <th class="py-3 pr-4">{{ __('Date') }}</th>
                <th class="py-3 pr-4">{{ __('Status') }}</th>
                <th class="py-3">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4">Travel</td>
                <td class="py-3 pr-4 font-medium">Rp 850,000</td>
                <td class="py-3 pr-4">Jun 18, 2026</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-warning/15 px-2.5 py-0.5 text-xs font-semibold text-warning">{{ __('Pending') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4">Medical</td>
                <td class="py-3 pr-4 font-medium">Rp 350,000</td>
                <td class="py-3 pr-4">Jun 12, 2026</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-semibold text-success">{{ __('Approved') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4">Supplies</td>
                <td class="py-3 pr-4 font-medium">Rp 125,000</td>
                <td class="py-3 pr-4">Jun 8, 2026</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-error/15 px-2.5 py-0.5 text-xs font-semibold text-error">{{ __('Rejected') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('This Month') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">Rp 1,325,000</p>
            <p class="text-sm text-on-surface-variant">{{ __('total claims') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Approved') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">Rp 350,000</p>
            <p class="text-sm text-on-surface-variant">{{ __('approved amount') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Pending') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">Rp 850,000</p>
            <p class="text-sm text-on-surface-variant">{{ __('awaiting approval') }}</p>
        </div>
    </div>
</x-layouts::app.sidebar>
