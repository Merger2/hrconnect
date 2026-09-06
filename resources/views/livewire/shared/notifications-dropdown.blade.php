@php($allNotificationsUrl = auth()->user()->can('manageAdminNotifications') ? route('admin.notifications') : route('notifications'))
@php($notificationPollInterval = \App\Support\AnnouncementRefresh::pollInterval())

<div class="notifications-dropdown relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false" @if (! \App\Support\AnnouncementRefresh::broadcastingEnabled()) wire:poll.visible.{{ $notificationPollInterval }} @endif>
    <button type="button" @click="open = ! open" class="notifications-trigger topbar-tool topbar-tool--icon relative"
        :aria-expanded="open.toString()" aria-haspopup="menu" aria-controls="notifications-panel">
        <span class="sr-only">{{ __('View notifications') }}</span>

        <x-heroicon-o-bell class="h-5 w-5" />

        @if($unreadCount > 0)
            <span class="notifications-trigger__badge">
                {{ $unreadCount > 99 ? '99' : $unreadCount }}
            </span>
        @endif
    </button>

    <div id="notifications-panel" x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="notifications-panel"
        style="display: none;">

        <div class="notifications-panel__header">
            <div class="min-w-0">
                <h3>{{ __('Notifications') }}</h3>
                <p>{{ __('Latest updates and approvals') }}</p>
            </div>

            @if($unreadCount > 0)
                <span class="notifications-panel__count">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>

        <div class="notifications-panel__list">
            @if($items->isEmpty())
                <div class="notifications-empty">
                    <x-heroicon-o-inbox class="h-8 w-8" />
                    <strong>{{ __('No new notifications') }}</strong>
                </div>
            @else
                @foreach($items as $item)
                    @if($item['type'] === 'notification')
                        @php($notification = $item['data'])
                        @php($targetUrl = normalize_internal_url($notification->data['url'] ?? $notification->data['action_url'] ?? null) ?? '#')
                        @php($notificationTitle = trim((string) ($notification->data['title'] ?? __('Notification'))))
                        @php($notificationMessage = trim((string) ($notification->data['message'] ?? '')))

                        <article class="notifications-row">
                            <a href="{{ $targetUrl }}" wire:click="markAsRead('{{ $notification->id }}')" @click="open = false" class="notifications-row__main">
                                <span class="notifications-row__icon {{ is_null($notification->read_at) ? 'notifications-row__icon--unread' : '' }}" aria-hidden="true">
                                    <x-heroicon-o-bell-alert class="h-4 w-4" />
                                </span>

                                <span class="notifications-row__content">
                                    <strong class="notifications-row__title">{{ $notificationTitle !== '' ? $notificationTitle : __('Notification') }}</strong>
                                    @if($notificationMessage !== '')
                                        <span class="notifications-row__message">{{ $notificationMessage }}</span>
                                    @endif
                                    <span class="notifications-row__time">
                                        <x-heroicon-o-clock class="h-3.5 w-3.5" />
                                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                                    </span>
                                </span>
                            </a>

                            <div class="notifications-row__actions">
                                @if(is_null($notification->read_at))
                                    <button type="button" wire:click.stop="markAsRead('{{ $notification->id }}')" @click="open = false" class="notifications-row__action" title="{{ __('Mark as read') }}" aria-label="{{ __('Mark as read') }}">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                    </button>
                                @endif
                                <button type="button" wire:click.stop="markAsRead('{{ $notification->id }}')" @click="open = false" class="notifications-row__action notifications-row__action--danger" title="{{ __('Dismiss') }}" aria-label="{{ __('Dismiss') }}">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                        </article>
                    @else
                        @php($announcement = $item['data'])
                        <article class="notifications-row notifications-row--announcement">
                            <div class="notifications-row__main">
                                <span class="notifications-row__icon notifications-row__icon--announcement" aria-hidden="true">
                                    <x-heroicon-o-megaphone class="h-4 w-4" />
                                </span>

                                <span class="notifications-row__content">
                                    <strong class="notifications-row__title">{{ $announcement->title }}</strong>
                                    <span class="notifications-row__message">{{ Str::limit(strip_tags($announcement->content), 110) }}</span>
                                    <span class="notifications-row__time">
                                        <x-heroicon-o-clock class="h-3.5 w-3.5" />
                                        <time datetime="{{ $announcement->created_at?->toIso8601String() }}">{{ $announcement->created_at->diffForHumans() }}</time>
                                    </span>
                                    @if($announcement->priority === 'high')
                                        <span class="notifications-row__important">{{ __('Important') }}</span>
                                    @endif
                                </span>
                            </div>

                            <div class="notifications-row__actions">
                                <button type="button" wire:click="dismiss({{ $announcement->id }})" @click="open = false" class="notifications-row__action notifications-row__action--danger" title="{{ __('Dismiss') }}" aria-label="{{ __('Dismiss') }}">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                        </article>
                    @endif
                @endforeach
            @endif
        </div>

        <div class="notifications-panel__footer">
            <a href="{{ $allNotificationsUrl }}"
                @click="open = false"
                class="notifications-panel__all">
                {{ __('Show All') }}
            </a>
        </div>
    </div>
</div>