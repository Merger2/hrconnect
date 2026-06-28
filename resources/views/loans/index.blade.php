<x-layouts::app.sidebar>
    <x-page-shell title="{{ __('Loan Management') }}" subtitle="{{ __('Employee loan applications and repayment tracking') }}">
        <x-slot:actions>
            <x-button variant="primary" icon="add">
                {{ __('Apply Loan') }}
            </x-button>
        </x-slot:actions>

        <x-loading-skeleton type="card" rows="5" />
    </x-page-shell>
</x-layouts::app.sidebar>
