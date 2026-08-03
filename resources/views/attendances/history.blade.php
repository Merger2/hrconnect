<x-app-layout>
    <div class="user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <section aria-labelledby="attendance-history-title" class="user-page-surface">
                <x-user.page-header
                    :back-href="route('home')"
                    :title="__('Attendance History')"
                    title-id="attendance-history-title">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </x-slot>
                </x-user.page-header>

                <div class="user-page-body">
                    <livewire:user.attendance-history-component />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
