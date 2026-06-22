<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Reimbursements') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('View and manage your reimbursement claims') }}</flux:subheading>

    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('reimbursements.apply') }}" wire:navigate icon="plus">
            {{ __('New Claim') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable>{{ __('Category') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Amount') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Date') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <flux:table.row>
                <flux:table.cell>Travel</flux:table.cell>
                <flux:table.cell class="font-medium">Rp 850,000</flux:table.cell>
                <flux:table.cell>Jun 18, 2026</flux:table.cell>
                <flux:table.cell><flux:badge color="warning">{{ __('Pending') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell>Medical</flux:table.cell>
                <flux:table.cell class="font-medium">Rp 350,000</flux:table.cell>
                <flux:table.cell>Jun 12, 2026</flux:table.cell>
                <flux:table.cell><flux:badge color="success">{{ __('Approved') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
            <flux:table.row>
                <flux:table.cell>Supplies</flux:table.cell>
                <flux:table.cell class="font-medium">Rp 125,000</flux:table.cell>
                <flux:table.cell>Jun 8, 2026</flux:table.cell>
                <flux:table.cell><flux:badge color="danger">{{ __('Rejected') }}</flux:badge></flux:table.cell>
                <flux:table.cell><flux:button variant="ghost" size="sm">{{ __('View') }}</flux:button></flux:table.cell>
            </flux:table.row>
        </flux:table.rows>
    </flux:table>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:heading size="sm">{{ __('This Month') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">Rp 1,325,000</p>
            <p class="text-sm text-muted">{{ __('total claims') }}</p>
        </flux:card>
        <flux:card>
            <flux:heading size="sm">{{ __('Approved') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">Rp 350,000</p>
            <p class="text-sm text-muted">{{ __('approved amount') }}</p>
        </flux:card>
        <flux:card>
            <flux:heading size="sm">{{ __('Pending') }}</flux:heading>
            <p class="mt-2 text-3xl font-display font-medium text-ink">Rp 850,000</p>
            <p class="text-sm text-muted">{{ __('awaiting approval') }}</p>
        </flux:card>
    </div>
</x-layouts::app.sidebar>
