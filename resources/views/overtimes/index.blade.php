<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Overtime') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('View and manage your overtime records') }}</flux:subheading>

    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('overtimes.apply') }}" wire:navigate icon="plus">
            {{ __('Request Overtime') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable>{{ __('Date') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Hours') }}</flux:table.column>
            <flux:table.column>{{ __('Description') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <flux:table.row>
                <flux:table.cell class="font-medium">Jun 19, 2026</flux:table.cell>
                <flux:table.cell>2.0</flux:table.cell>
                <flux:table.cell>Project deployment support</flux:table.cell>
                <flux:table.cell><flux:badge color="success">{{ __('Approved') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell class="font-medium">Jun 15, 2026</flux:table.cell>
                <flux:table.cell>1.5</flux:table.cell>
                <flux:table.cell>System maintenance</flux:table.cell>
                <flux:table.cell><flux:badge color="warning">{{ __('Pending') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell class="font-medium">Jun 10, 2026</flux:table.cell>
                <flux:table.cell>1.0</flux:table.cell>
                <flux:table.cell>Urgent client request</flux:table.cell>
                <flux:table.cell><flux:badge color="success">{{ __('Approved') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
        </flux:table.rows>
    </flux:table>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:heading size="sm">{{ __('This Month') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">4.5</p>
            <p class="text-sm text-muted">{{ __('total hours') }}</p>
        </flux:card>
        <flux:card>
            <flux:heading size="sm">{{ __('Pending') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">1.5</p>
            <p class="text-sm text-muted">{{ __('hours awaiting approval') }}</p>
        </flux:card>
        <flux:card>
            <flux:heading size="sm">{{ __('Approved') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">3.0</p>
            <p class="text-sm text-muted">{{ __('hours approved') }}</p>
        </flux:card>
    </div>
</x-layouts::app.sidebar>
