<x-app-layout>
    @php($currentUser = request()->user())
    @php($homeCommandCenter = $homeCommandCenter ?? ['attentionCount' => 0, 'actionItems' => [], 'teamItems' => [], 'recentActivities' => []])
    @php($hour = now()->hour)
    @php($greeting = $hour < 11 ? __('Good morning') : ($hour < 15 ? __('Good afternoon') : __('Good evening')))
    @php($actionSummaryItems = collect($homeCommandCenter['actionItems'] ?? []))
    @php($teamSummaryItems = collect($homeCommandCenter['teamItems'] ?? []))

    <section aria-labelledby="home-page-title" class="user-home-hero user-home-hero--command">
        <div class="user-home-hero__inner">
            <div class="user-home-hero__copy">
                <p class="text-lg font-medium text-slate-600 dark:text-slate-300">{{ $greeting }},</p>
                <h1 id="home-page-title" class="user-home-hero__title !mt-0">{{ $currentUser->name }}</h1>
                <p class="user-home-hero__subtitle">
                    {{ $homeCommandCenter['attentionCount'] > 0
                        ? trans_choice('{1} :count priority today|[2,*] :count priorities today', $homeCommandCenter['attentionCount'], ['count' => $homeCommandCenter['attentionCount']])
                        : __('No priorities for today.') }}
                </p>
            </div>

            <div class="user-home-hero__tools">
                <a href="{{ route('profile.show') }}" class="user-home-hero__profile" aria-label="{{ __('Open profile') }}">
                    <img class="h-full w-full object-cover" src="{{ $currentUser->profile_photo_url }}" alt="{{ $currentUser->name }}" />
                </a>
            </div>
        </div>
    </section>

    <div class="user-home-content user-home-content--command">
        <section aria-labelledby="attendance-summary-heading">
            <h2 id="attendance-summary-heading" class="sr-only">{{ __('Today attendance summary') }}</h2>
            <livewire:user.home-attendance-status />
        </section>

        <section aria-labelledby="my-menu-heading">
            <h2 id="my-menu-heading" class="sr-only">{{ __('Quick Access') }}</h2>
            <livewire:user.quick-actions />
        </section>

        <section aria-labelledby="home-action-needed-heading" class="home-command-panel home-command-panel--compact">
            <div class="home-command-panel__header">
                <div>
                    <p class="home-command-panel__eyebrow">{{ __('Action Needed') }}</p>
                    <h2 id="home-action-needed-heading" class="home-command-panel__title">{{ __('Follow up without hunting menus') }}</h2>
                </div>
                @if($homeCommandCenter['attentionCount'] > 0)
                    <span class="home-command-panel__count">{{ $homeCommandCenter['attentionCount'] }}</span>
                @endif
            </div>

            @if($actionSummaryItems->isEmpty() && $teamSummaryItems->isEmpty())
                <div class="home-empty-strip">
                    <span class="home-empty-strip__icon" aria-hidden="true">
                        <x-heroicon-o-check-circle class="h-5 w-5" />
                    </span>
                    <div>
                        <h3>{{ __('Nothing urgent right now') }}</h3>
                        <p>{{ __('Your attendance, requests, and tasks are clear.') }}</p>
                    </div>
                </div>
            @else
                @if($actionSummaryItems->isNotEmpty())
                    <div class="home-compact-group">
                        <p class="home-compact-group__label">{{ __('My follow-ups') }}</p>
                        <div class="home-compact-actions" role="list">
                            @foreach($actionSummaryItems as $item)
                                <a href="{{ $item['href'] }}" class="home-compact-action home-compact-action--{{ $item['tone'] }}" role="listitem" aria-label="{{ $item['label'] }}: {{ $item['description'] }}">
                                    <span class="home-compact-action__icon" aria-hidden="true">
                                        @switch($item['icon'])
                                            @case('face')
                                                <x-heroicon-o-face-smile class="h-4 w-4" />
                                                @break
                                            @case('clipboard')
                                                <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                                                @break
                                            @case('document')
                                                <x-heroicon-o-document-text class="h-4 w-4" />
                                                @break
                                            @case('check')
                                                <x-heroicon-o-check-badge class="h-4 w-4" />
                                                @break
                                            @case('clock')
                                                <x-heroicon-o-clock class="h-4 w-4" />
                                                @break
                                            @case('calendar')
                                                <x-heroicon-o-calendar-days class="h-4 w-4" />
                                                @break
                                            @case('cash')
                                                <x-heroicon-o-banknotes class="h-4 w-4" />
                                                @break
                                            @case('home')
                                                <x-heroicon-o-home-modern class="h-4 w-4" />
                                                @break
                                            @case('swap')
                                                <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                                                @break
                                            @default
                                                <x-heroicon-o-bell-alert class="h-4 w-4" />
                                        @endswitch
                                    </span>
                                    <span class="home-compact-action__label">{{ $item['label'] }}</span>
                                    @if($item['count'])
                                        <span class="home-compact-action__count">{{ $item['count'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($teamSummaryItems->isNotEmpty())
                    <div class="home-compact-group">
                        <p class="home-compact-group__label">{{ __('Team') }}</p>
                        <div class="home-compact-actions" role="list">
                            @foreach($teamSummaryItems as $item)
                                <a href="{{ $item['href'] }}" class="home-compact-action home-compact-action--{{ $item['tone'] }}" role="listitem" aria-label="{{ $item['label'] }}: {{ $item['description'] }}">
                                    <span class="home-compact-action__icon" aria-hidden="true">
                                        @switch($item['icon'])
                                            @case('calendar')
                                                <x-heroicon-o-calendar-days class="h-4 w-4" />
                                                @break
                                            @case('cash')
                                                <x-heroicon-o-banknotes class="h-4 w-4" />
                                                @break
                                            @default
                                                <x-heroicon-o-check-badge class="h-4 w-4" />
                                        @endswitch
                                    </span>
                                    <span class="home-compact-action__label">{{ $item['label'] }}</span>
                                    <span class="home-compact-action__count">{{ $item['count'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </section>

        @if(! empty($homeCommandCenter['recentActivities']))
            <section aria-labelledby="home-recent-heading" class="home-command-panel">
                <div class="home-command-panel__header">
                    <div>
                        <p class="home-command-panel__eyebrow">{{ __('Recent Activity') }}</p>
                        <h2 id="home-recent-heading" class="home-command-panel__title">{{ __('Latest request status') }}</h2>
                    </div>
                </div>

                <div class="home-activity-list">
                    @foreach($homeCommandCenter['recentActivities'] as $activity)
                        <a href="{{ $activity['href'] }}" class="home-activity-item">
                            <span class="home-activity-item__dot home-activity-item__dot--{{ $activity['tone'] }}" aria-hidden="true"></span>
                            <span class="home-activity-item__body">
                                <strong>{{ $activity['label'] }}</strong>
                                <span>{{ $activity['description'] }}</span>
                            </span>
                            <span class="home-activity-item__status home-activity-item__status--{{ $activity['tone'] }}">
                                {{ $activity['status'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="happening-now-heading">
            <div class="user-section-heading">
                <h2 id="happening-now-heading" class="user-section-heading__title">{{ __('Happening Now') }}</h2>
                <a href="{{ route('notifications') }}" class="user-section-heading__action">{{ __('View All') }}</a>
            </div>
            <livewire:user.upcoming-events-widget />
        </section>
    </div>

    @push('scripts')
        <script>
            if (sessionStorage.getItem('force_reload_next')) {
                sessionStorage.removeItem('force_reload_next');
                window.location.reload();
            }
        </script>
    @endpush
</x-app-layout>
