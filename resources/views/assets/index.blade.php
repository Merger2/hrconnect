<x-layouts::app.sidebar>
    <x-page-shell title="{{ __('Asset Management') }}" subtitle="{{ __('Manage company assets and equipment') }}">
        <x-slot:actions>
            <x-button variant="primary" icon="add">
                {{ __('Add Asset') }}
            </x-button>
        </x-slot:actions>

        <x-loading-skeleton type="card" rows="5" />
    </x-page-shell>
</x-layouts::app.sidebar>
