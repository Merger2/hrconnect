<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('Leave Requests') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('View and manage your leave applications') }}</p>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('leaves.apply') }}" class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" wire:navigate>
            <span class="material-symbols-outlined text-base">add</span>
            {{ __('Apply Leave') }}
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-4 text-center">
            <p class="text-xs text-on-surface-variant">{{ __('Annual Leave') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">8 / 12</p>
            <p class="text-xs text-on-surface-variant">{{ __('days remaining') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-4 text-center">
            <p class="text-xs text-on-surface-variant">{{ __('Sick Leave') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">3 / 6</p>
            <p class="text-xs text-on-surface-variant">{{ __('days remaining') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-4 text-center">
            <p class="text-xs text-on-surface-variant">{{ __('Personal Leave') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">2 / 3</p>
            <p class="text-xs text-on-surface-variant">{{ __('days remaining') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-4 text-center">
            <p class="text-xs text-on-surface-variant">{{ __('Pending') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">1</p>
            <p class="text-xs text-on-surface-variant">{{ __('awaiting approval') }}</p>
        </div>
    </div>

    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant/50 text-left text-sm font-semibold text-on-surface-variant">
                <th class="py-3 pr-4">{{ __('Type') }}</th>
                <th class="py-3 pr-4">{{ __('From') }}</th>
                <th class="py-3 pr-4">{{ __('To') }}</th>
                <th class="py-3 pr-4">{{ __('Days') }}</th>
                <th class="py-3 pr-4">{{ __('Status') }}</th>
                <th class="py-3">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4">Annual</td>
                <td class="py-3 pr-4">Jul 10, 2026</td>
                <td class="py-3 pr-4">Jul 14, 2026</td>
                <td class="py-3 pr-4">3</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-warning/15 px-2.5 py-0.5 text-xs font-semibold text-warning">{{ __('Pending') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4">Sick</td>
                <td class="py-3 pr-4">Jun 5, 2026</td>
                <td class="py-3 pr-4">Jun 5, 2026</td>
                <td class="py-3 pr-4">1</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-semibold text-success">{{ __('Approved') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
        </tbody>
    </table>
</x-layouts::app.sidebar>
