<x-app-layout>
    @php($currentUser = request()->user())
    @php($homeCommandCenter = $homeCommandCenter ?? ['attentionCount' => 0, 'actionItems' => [], 'teamItems' => [], 'recentActivities' => []])
    @php($actionSummaryItems = collect($homeCommandCenter['actionItems'] ?? []))
    @php($teamSummaryItems = collect($homeCommandCenter['teamItems'] ?? []))

    <div class="user-page-shell pt-0">
        <div class="user-page-container user-page-container--wide px-0">
            <section aria-labelledby="home-page-title" class="user-home-hero user-home-hero--command">
                <div class="user-home-hero__inner">
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

            {{-- Date context bar --}}
            <div class="home-date-context" x-data="liveClock()" x-init="init()">
                <div class="home-date-context__date">
                    <span class="home-date-context__dayname" x-text="dayName"></span>
                    <span class="home-date-context__sep" aria-hidden="true">•</span>
                    <span class="home-date-context__fulldate" x-text="fullDate"></span>
                </div>
                <div class="home-date-context__time" x-text="clockTime" aria-live="polite"></div>
            </div>

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
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
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
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
                                                        @break
                                                    @case('clipboard')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" /></svg>
                                                        @break
                                                    @case('document')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                                        @break
                                                    @case('check')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        @break
                                                    @case('clock')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        @break
                                                    @case('calendar')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                                        @break
                                                    @case('cash')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 0 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v9.5m-10 0h10" /></svg>
                                                        @break
                                                    @case('home')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                                        @break
                                                    @case('swap')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                                        @break
                                                    @default
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
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
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                                        @break
                                                    @case('cash')
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 0 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v9.5m-10 0h10" /></svg>
                                                        @break
                                                    @default
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
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

                <section aria-labelledby="happening-now-heading" class="home-section--spaced">
                    <div class="user-section-heading">
                        <h2 id="happening-now-heading" class="user-section-heading__title">{{ __('Happening Now') }}</h2>
                        <a href="{{ route('notifications') }}" class="user-section-heading__action">{{ __('View All') }}</a>
                    </div>
                    <livewire:user.upcoming-events-widget />
                </section>
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
