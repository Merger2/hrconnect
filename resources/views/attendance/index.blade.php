<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('Attendance') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('View and manage your attendance records') }}</p>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('attendance.clock-in') }}" class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" wire:navigate>
            <span class="material-symbols-outlined text-base">clock</span>
            {{ __('Clock In / Out') }}
        </a>
        <select class="w-48 rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
            <option value="this-month">{{ __('This Month') }}</option>
            <option value="last-month">{{ __('Last Month') }}</option>
            <option value="this-quarter">{{ __('This Quarter') }}</option>
        </select>
    </div>

    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant/50 text-left text-sm font-semibold text-on-surface-variant">
                <th class="py-3 pr-4">{{ __('Date') }}</th>
                <th class="py-3 pr-4">{{ __('Clock In') }}</th>
                <th class="py-3 pr-4">{{ __('Clock Out') }}</th>
                <th class="py-3 pr-4">{{ __('Status') }}</th>
                <th class="py-3">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4 font-medium">Jun 22, 2026</td>
                <td class="py-3 pr-4">08:02 AM</td>
                <td class="py-3 pr-4">05:30 PM</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-semibold text-success">{{ __('Present') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4 font-medium">Jun 21, 2026</td>
                <td class="py-3 pr-4">07:58 AM</td>
                <td class="py-3 pr-4">05:15 PM</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-semibold text-success">{{ __('Present') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
            <tr class="border-b border-outline-variant/30 text-sm text-ink">
                <td class="py-3 pr-4 font-medium">Jun 20, 2026</td>
                <td class="py-3 pr-4">08:12 AM</td>
                <td class="py-3 pr-4">05:45 PM</td>
                <td class="py-3 pr-4"><span class="inline-flex items-center rounded-full bg-warning/15 px-2.5 py-0.5 text-xs font-semibold text-warning">{{ __('Late') }}</span></td>
                <td class="py-3"><button class="rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high">{{ __('View') }}</button></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('This Month') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">18</p>
            <p class="text-sm text-on-surface-variant">{{ __('days worked') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Late Arrivals') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">2</p>
            <p class="text-sm text-on-surface-variant">{{ __('this month') }}</p>
        </div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Overtime') }}</h2>
            <p class="mt-2 text-3xl font-display font-medium text-ink">4.5</p>
            <p class="text-sm text-on-surface-variant">{{ __('hours this month') }}</p>
        </div>
    </div>
</x-layouts::app.sidebar>
