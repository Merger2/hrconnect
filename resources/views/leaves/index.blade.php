<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Leave Requests') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('View and manage your leave applications') }}</flux:subheading>

    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('leaves.apply') }}" wire:navigate icon="plus">
            {{ __('Apply Leave') }}
        </flux:button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <flux:card class="text-center">
            <p class="text-xs text-muted">{{ __('Annual Leave') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">8 / 12</p>
            <p class="text-xs text-muted">{{ __('days remaining') }}</p>
        </flux:card>
        <flux:card class="text-center">
            <p class="text-xs text-muted">{{ __('Sick Leave') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">3 / 6</p>
            <p class="text-xs text-muted">{{ __('days remaining') }}</p>
        </flux:card>
        <flux:card class="text-center">
            <p class="text-xs text-muted">{{ __('Personal Leave') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">2 / 3</p>
            <p class="text-xs text-muted">{{ __('days remaining') }}</p>
        </flux:card>
        <flux:card class="text-center">
            <p class="text-xs text-muted">{{ __('Pending') }}</p>
            <p class="mt-1 text-2xl font-display font-medium text-ink">1</p>
            <p class="text-xs text-muted">{{ __('awaiting approval') }}</p>
        </flux:card>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable>{{ __('Type') }}</flux:table.column>
            <flux:table.column sortable>{{ __('From') }}</flux:table.column>
            <flux:table.column sortable>{{ __('To') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Days') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <flux:table.row>
                <flux:table.cell>Annual</flux:table.cell>
                <flux:table.cell>Jul 10, 2026</flux:table.cell>
                <flux:table.cell>Jul 14, 2026</flux:table.cell>
                <flux:table.cell>3</flux:table.cell>
                <flux:table.cell><flux:badge color="warning">{{ __('Pending') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell>Sick</flux:table.cell>
                <flux:table.cell>Jun 5, 2026</flux:table.cell>
                <flux:table.cell>Jun 5, 2026</flux:table.cell>
                <flux:table.cell>1</flux:table.cell>
                <flux:table.cell><flux:badge color="success">{{ __('Approved') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
        </flux:table.rows>
    </flux:table>
</x-layouts::app.sidebar>
