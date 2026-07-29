@php($backRoute = route('home'))
@php($activeFilter = $showUnreadOnly ? 'unread' : $contentFilter)

<div class="user-page-shell notification-center-page">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="notifications-title" class="user-page-surface" wire:poll.visible.20s>
            <x-user.page-header
                :back-href="$backRoute"
                :title="__('Notifications')"
                title-id="notifications-title">
                <x-slot name="icon">
                    <x-heroicon-o-bell class="h-5 w-5" />
                </x-slot>

                <x-slot name="actions">
                    <span class="notification-center-count" aria-label="{{ __('Unread notifications') }}">
                        {{ $unreadCount }}
                    </span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body notification-center-body">
                <div class="notification-center-hero">
                    <div class="notification-center-hero__icon" aria-hidden="true">
                        <x-heroicon-o-bell-alert class="h-6 w-6" />
                    </div>
                    <div class="notification-center-hero__copy">
                        <p>{{ __('Latest updates and approvals') }}</p>
                        <strong>{{ __('Unread notifications') }}: {{ $unreadCount }}</strong>
                    </div>
                    @if($notificationCount > 0)
                        <button type="button" wire:click="markAllAsRead" class="notification-center-hero__action" aria-label="{{ __('Mark All as Read') }}">
                            <x-heroicon-o-check-circle class="h-4 w-4" />
                            <span>{{ __('Mark All as Read') }}</span>
                        </button>
                    @endif
                </div>

                <div class="notification-center-filters" aria-label="{{ __('Filter notifications') }}">
                    <label class="notification-center-search" for="notifications-search">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5" aria-hidden="true" />
                        <input
                            id="notifications-search"
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search notifications') }}"
                        >
                    </label>

                    <div class="notification-center-tabs" role="tablist" aria-label="{{ __('Notification type') }}">
                        @foreach ([
                            'all' => __('All'),
                            'unread' => __('Unread'),
                            'notifications' => __('Notifications'),
                            'announcements' => __('Announcements'),
                        ] as $filter => $label)
                            <button
                                type="button"
                                role="tab"
                                wire:click="setContentFilter('{{ $filter }}')"
                                class="notification-center-tab {{ $activeFilter === $filter ? 'notification-center-tab--active' : '' }}"
                                aria-selected="{{ $activeFilter === $filter ? 'true' : 'false' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($announcements->isEmpty() && $notifications->count() === 0)
                    <div class="notification-center-empty">
                        <div class="notification-center-empty__icon" aria-hidden="true">
                            <x-heroicon-o-inbox class="h-8 w-8" />
                        </div>
                        @if($search !== '' || $activeFilter !== 'all')
                            <h2>{{ __('No notifications found for this filter.') }}</h2>
                            <p>{{ __('Try adjusting your filters or search.') }}</p>
                        @else
                            <h2>{{ __('No new notifications') }}</h2>
                            <p>{{ __('We\'ll let you know when something important arrives.') }}</p>
                        @endif
                    </div>
                @else
                    <div id="notifications-list" class="notification-center-list">
                        @foreach($announcements as $announcement)
                            <article class="notification-center-item notification-center-item--announcement">
                                <div class="notification-center-item__icon" aria-hidden="true">
                                    <x-heroicon-o-megaphone class="h-5 w-5" />
                                </div>

                                <div class="notification-center-item__content">
                                    <div class="notification-center-item__heading">
                                        <h3>{{ $announcement->title }}</h3>
                                        @if($announcement->priority === 'high')
                                            <span>{{ __('Important') }}</span>
                                        @endif
                                    </div>
                                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 150) }}</p>
                                    <time datetime="{{ $announcement->created_at?->toIso8601String() }}">
                                        {{ $announcement->created_at->diffForHumans() }}
                                    </time>
                                </div>

                                <button
                                    type="button"
                                    wire:click="dismissAnnouncement({{ $announcement->id }})"
                                    class="notification-center-item__button notification-center-item__button--danger"
                                    aria-label="{{ __('Dismiss') }}">
                                    <x-heroicon-o-x-mark class="h-5 w-5" />
                                </button>
                            </article>
                        @endforeach

                        @foreach($notifications as $notification)
                            @php($targetUrl = normalize_internal_url($notification->data['url'] ?? $notification->data['action_url'] ?? null))
                            @php($notificationTitle = trim((string) ($notification->data['title'] ?? __('Notification'))))
                            @php($notificationMessage = trim((string) ($notification->data['message'] ?? '')))

                            <article class="notification-center-item {{ is_null($notification->read_at) ? 'notification-center-item--unread' : '' }}">
                                <div class="notification-center-item__icon" aria-hidden="true">
                                    <x-heroicon-o-bell class="h-5 w-5" />
                                </div>

                                @if($targetUrl)
                                    <a href="{{ $targetUrl }}" wire:click="markAsRead('{{ $notification->id }}')" class="notification-center-item__content">
                                        <div class="notification-center-item__heading">
                                            <h3>{{ $notificationTitle !== '' ? $notificationTitle : __('Notification') }}</h3>
                                            @if(is_null($notification->read_at))
                                                <span>{{ __('Unread') }}</span>
                                            @endif
                                        </div>
                                        @if($notificationMessage !== '')
                                            <p>{{ $notificationMessage }}</p>
                                        @endif
                                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </time>
                                    </a>
                                @else
                                    <div class="notification-center-item__content">
                                        <div class="notification-center-item__heading">
                                            <h3>{{ $notificationTitle !== '' ? $notificationTitle : __('Notification') }}</h3>
                                            @if(is_null($notification->read_at))
                                                <span>{{ __('Unread') }}</span>
                                            @endif
                                        </div>
                                        @if($notificationMessage !== '')
                                            <p>{{ $notificationMessage }}</p>
                                        @endif
                                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </time>
                                    </div>
                                @endif

                                @if(is_null($notification->read_at))
                                    <button
                                        type="button"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="notification-center-item__button"
                                        aria-label="{{ __('Mark as read') }}">
                                        <x-heroicon-o-check class="h-5 w-5" />
                                    </button>
                                @endif
                            </article>
                        @endforeach

                    </div>
                @endif

                @if($notifications->hasPages())
                    <div class="notification-center-pagination">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
