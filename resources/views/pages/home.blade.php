<x-app-layout>
    @php($currentUser = request()->user())
    @php($homeCommandCenter = $homeCommandCenter ?? ['attentionCount' => 0, 'actionItems' => [], 'teamItems' => [], 'recentActivities' => []])
    @php($actionSummaryItems = collect($homeCommandCenter['actionItems'] ?? []))
    @php($teamSummaryItems = collect($homeCommandCenter['teamItems'] ?? []))

    <div class="user-page-shell pt-[calc(env(safe-area-inset-top)+0.5rem)]">
        <div class="user-page-container user-page-container--wide px-0">
            <section aria-labelledby="home-page-title" class="user-home-hero user-home-hero--command">
                <div class="user-home-hero__inner">
                    <span class="user-home-hero__glow" aria-hidden="true"></span>
                    <div class="user-home-hero__copy">
                        <p class="user-home-hero__greeting" x-data="liveGreeting()" x-init="init()" x-text="greeting + ','"></p>
                        <h1 id="home-page-title" class="user-home-hero__title">{{ $currentUser->name }}</h1>
                        <p class="user-home-hero__subtitle">
                            {{ $homeCommandCenter['attentionCount'] > 0
                                ? trans_choice('{1} :count priority today|[2,*] :count priorities today', $homeCommandCenter['attentionCount'], ['count' => $homeCommandCenter['attentionCount']])
                                : __('No priorities for today.') }}
                        </p>
                    </div>

                    <div class="user-home-hero__tools">
                        <livewire:shared.notifications-dropdown />
                        <a href="{{ route('profile.show') }}" class="user-home-hero__profile" aria-label="{{ __('Open profile') }}">
                            <img class="h-full w-full object-cover" src="{{ $currentUser->profile_photo_url }}" alt="{{ $currentUser->name }}" />
                        </a>
                    </div>
                </div>
            </section>

            {{-- Date context bar — kartu kaca mengambang di atas hero, live clock --}}
            <div class="home-date-context" x-data="liveClock()" x-init="init()">
                <div class="home-date-context__date">
                    <span class="home-date-context__icon" aria-hidden="true">
                        <x-heroicon-o-calendar />
                    </span>
                    <span class="home-date-context__text">
                        <span class="home-date-context__dayname" x-text="dayName"></span>
                        <span class="home-date-context__sep" aria-hidden="true">•</span>
                        <span class="home-date-context__fulldate" x-text="fullDate"></span>
                    </span>
                </div>
                <div class="home-date-context__clock">
                    <span class="home-date-context__live-dot" aria-hidden="true"></span>
                    <span class="home-date-context__time" x-text="clockTime" aria-live="polite"></span>
                </div>
            </div>

            <div class="user-home-content user-home-content--command">
                <div class="home-layout">
                <section aria-labelledby="attendance-summary-heading" class="home-grid-att">
                    <h2 id="attendance-summary-heading" class="sr-only">{{ __('Today attendance summary') }}</h2>
                    <livewire:user.home-attendance-status />
                </section>

                <section aria-labelledby="my-menu-heading" class="home-grid-qa">
                    <h2 id="my-menu-heading" class="sr-only">{{ __('Quick Access') }}</h2>
                    <livewire:user.quick-actions />
                </section>

                <section aria-labelledby="home-action-needed-heading" class="home-command-panel home-command-panel--compact home-grid-cmd">
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
                                                        <x-heroicon-o-document class="h-4 w-4" />
                                                        @break
                                                    @case('check')
                                                        <x-heroicon-o-check-circle class="h-4 w-4" />
                                                        @break
                                                    @case('clock')
                                                        <x-heroicon-o-clock class="h-4 w-4" />
                                                        @break
                                                    @case('calendar')
                                                        <x-heroicon-o-calendar class="h-4 w-4" />
                                                        @break
                                                    @case('cash')
                                                        <x-heroicon-o-banknotes class="h-4 w-4" />
                                                        @break
                                                    @case('home')
                                                        <x-heroicon-o-home class="h-4 w-4" />
                                                        @break
                                                    @case('swap')
                                                        <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                                                        @break
                                                    @default
                                                        <x-heroicon-o-bell class="h-4 w-4" />
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
                                                        <x-heroicon-o-calendar class="h-4 w-4" />
                                                        @break
                                                    @case('cash')
                                                        <x-heroicon-o-banknotes class="h-4 w-4" />
                                                        @break
                                                    @default
                                                        <x-heroicon-o-check-circle class="h-4 w-4" />
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
                    <section aria-labelledby="home-recent-heading" class="home-command-panel home-command-panel--emerald home-grid-rec">
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

                <section aria-labelledby="happening-now-heading" class="home-section--spaced home-grid-ev">
                    <div class="user-section-heading">
                        <h2 id="happening-now-heading" class="user-section-heading__title">{{ __('Happening Now') }}</h2>
                        <a href="{{ route('notifications') }}" class="user-section-heading__action">{{ __('View All') }}</a>
                    </div>
                    <livewire:user.upcoming-events-widget />
                </section>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('liveGreeting', () => ({
                    greeting: '',
                    init() {
                        this.update();
                        setInterval(() => this.update(), 60000);
                    },
                    update() {
                        const hour = new Date().getHours();
                        if (hour < 11) this.greeting = '{{ __('Good morning') }}';
                        else if (hour < 15) this.greeting = '{{ __('Good afternoon') }}';
                        else this.greeting = '{{ __('Good evening') }}';
                    }
                }));

                Alpine.data('liveClock', () => ({
                    dayName: '',
                    fullDate: '',
                    clockTime: '',
                    init() {
                        this.update();
                        setInterval(() => this.update(), 1000);
                    },
                    update() {
                        const now = new Date();
                        const days = ['{{ __('Sunday') }}','{{ __('Monday') }}','{{ __('Tuesday') }}','{{ __('Wednesday') }}','{{ __('Thursday') }}','{{ __('Friday') }}','{{ __('Saturday') }}'];
                        const months = ['{{ __('January') }}','{{ __('February') }}','{{ __('March') }}','{{ __('April') }}','{{ __('May') }}','{{ __('June') }}','{{ __('July') }}','{{ __('August') }}','{{ __('September') }}','{{ __('October') }}','{{ __('November') }}','{{ __('December') }}'];
                        this.dayName = days[now.getDay()];
                        this.fullDate = now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
                        this.clockTime = String(now.getHours()).padStart(2, '0') + ':' +
                            String(now.getMinutes()).padStart(2, '0') + ':' +
                            String(now.getSeconds()).padStart(2, '0');
                    }
                }));
            });

            if (sessionStorage.getItem('force_reload_next')) {
                sessionStorage.removeItem('force_reload_next');
                window.location.reload();
            }
        </script>
    @endpush
</x-app-layout>
