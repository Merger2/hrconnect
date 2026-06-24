<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('Overtime') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('View and manage your overtime records') }}</p>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('overtimes.apply') }}" class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" wire:navigate>
            <span class="material-symbols-outlined text-base">add</span>
            {{ __('Request Overtime') }}
        </a>
    </div>

    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant/50 text-left text-sm font-semibold text-on-surface-variant">
                <th class="py-3 pr-4">{{ __('Date') }}</th>
                <th class="py-3 pr-4">{{ __('Hours') }}</th>
                <th class="py-3 pr-4">{{ __('Description') }}</th>
                <th class="py-3 pr-4">{{ __('Status') }}</th>
                <th class="py-3">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4 font-medium">Jun 19, 2026</td>
                <td class="py-3 pr-4">2.0</td>
                <td class="py-3 pr-4">Project deployment support</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-semibold text-success">{{ __('Approved') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4 font-medium">Jun 15, 2026</td>
                <td class="py-3 pr-4">1.5</td>
                <td class="py-3 pr-4">System maintenance</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-warning/15 px-2.5 py-0.5 text-xs font-semibold text-warning">{{ __('Pending') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4 font-medium">Jun 10, 2026</td>
                <td class="py-3 pr-4">1.0</td>
                <td class="py-3 pr-4">Urgent client request</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-semibold text-success">{{ __('Approved') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('This Month') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">4.5</p>
            <p class="text-sm text-on-surface-variant">{{ __('total hours') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Pending') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">1.5</p>
            <p class="text-sm text-on-surface-variant">{{ __('hours awaiting approval') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Approved') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">3.0</p>
            <p class="text-sm text-on-surface-variant">{{ __('hours approved') }}</p>
        </div>
    </div>
</x-layouts::app.sidebar>
