<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Attendance') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('View and manage your attendance records') }}</flux:subheading>

    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('attendance.clock-in') }}" wire:navigate icon="clock">
            {{ __('Clock In / Out') }}
        </flux:button>
        <flux:select class="w-48">
            <option value="this-month">{{ __('This Month') }}</option>
            <option value="last-month">{{ __('Last Month') }}</option>
            <option value="this-quarter">{{ __('This Quarter') }}</option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable>{{ __('Date') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Clock In') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Clock Out') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <flux:table.row>
                <flux:table.cell class="font-medium">Jun 22, 2026</flux:table.cell>
                <flux:table.cell>08:02 AM</flux:table.cell>
                <flux:table.cell>05:30 PM</flux:table.cell>
                <flux:table.cell><flux:badge color="success">{{ __('Present') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell class="font-medium">Jun 21, 2026</flux:table.cell>
                <flux:table.cell>07:58 AM</flux:table.cell>
                <flux:table.cell>05:15 PM</flux:table.cell>
                <flux:table.cell><flux:badge color="success">{{ __('Present') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell class="font-medium">Jun 20, 2026</flux:table.cell>
                <flux:table.cell>08:12 AM</flux:table.cell>
                <flux:table.cell>05:45 PM</flux:table.cell>
                <flux:table.cell><flux:badge color="warning">{{ __('Late') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
        </flux:table.rows>
    </flux:table>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:heading size="sm">{{ __('This Month') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">18</p>
            <p class="text-sm text-muted">{{ __('days worked') }}</p>
        </flux:card>
        <flux:card>
            <flux:heading size="sm">{{ __('Late Arrivals') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">2</p>
            <p class="text-sm text-muted">{{ __('this month') }}</p>
        </flux:card>
        <flux:card>
            <flux:heading size="sm">{{ __('Overtime') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">4.5</p>
            <p class="text-sm text-muted">{{ __('hours this month') }}</p>
        </flux:card>
    </div>
</x-layouts::app.sidebar>
