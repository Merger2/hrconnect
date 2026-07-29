<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Attendance') }}
        </h2>
    </x-slot>

    <div class="user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <section aria-labelledby="attendance-history-title" class="user-page-surface">
                <x-user.page-header
                    :back-href="route('home')"
                    :title="__('Attendance History')"
                    title-id="attendance-history-title">
                    <x-slot name="icon">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </x-slot>
                </x-user.page-header>

                <div class="user-page-body">
                    <livewire:user.attendance-history-component />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
