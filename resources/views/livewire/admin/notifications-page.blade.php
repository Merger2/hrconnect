<x-admin.page-shell
    :title="__('Notifications')"
    :description="__('Review unread alerts, historical notifications, and active announcements in a full-page admin view.')">
    <x-slot name="actions">
        <div class="flex flex-wrap items-center gap-2">
            <x-admin.status-badge tone="primary" pill>
                {{ __('Unread') }}: {{ $notificationCount }}
            </x-admin.status-badge>
            @if($announcementCount > 0)
                <x-admin.status-badge tone="info" pill>
                    {{ __('Announcements') }}: {{ $announcementCount }}
                </x-admin.status-badge>
            @endif
            <x-admin.status-badge tone="neutral" pill>
                {{ __('Inbox') }}: {{ $unreadCount }}
            </x-admin.status-badge>
        </div>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools
            :title="__('Filter Notification Inbox')"
            :description="__('Search notification titles or messages, then focus on announcements, unread items, or the full inbox.')"
        >
            <x-slot name="summary">
                <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-600">
                    {{ __('Unread') }}: {{ $notificationCount }}
                    @if($announcementCount > 0)
                        <span class="ml-2">{{ __('Announcements') }}: {{ $announcementCount }}</span>
                    @endif
                </div>
            </x-slot>

            <div class="md:col-span-2 xl:col-span-7">
                <x-forms.label for="notification-search" value="{{ __('Search inbox') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input
                        id="notification-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search title or message...') }}"
                        class="w-full pl-11"
                    />
                </div>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="notification-content-filter" value="{{ __('View') }}" class="mb-1.5 block" />
                <x-forms.select id="notification-content-filter" wire:model.live="contentFilter" class="w-full">
                    <option value="all">{{ __('All inbox content') }}</option>
                    <option value="notifications">{{ __('Notifications only') }}</option>
                    <option value="announcements">{{ __('Announcements only') }}</option>
                    <option value="unread">{{ __('Unread only') }}</option>
                </x-forms.select>
            </div>

            <div class="xl:col-span-2">
                <x-forms.label for="notification-unread-toggle" value="{{ __('Read State') }}" class="mb-1.5 block" />
                <button
                    id="notification-unread-toggle"
                    type="button"
                    wire:click="$toggle('showUnreadOnly')"
                    aria-pressed="{{ $showUnreadOnly ? 'true' : 'false' }}"
                    aria-controls="admin-notifications-list"
                    class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-xl border px-4 py-3 text-sm font-semibold transition {{ $showUnreadOnly ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-200 bg-white text-slate-700' }}">
                    {{ $showUnreadOnly ? __('Unread only enabled') : __('Include read items') }}
                </button>
            </div>

            <x-slot name="actions">
                @if($notificationCount > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        class="inline-flex min-h-[2.75rem] items-center rounded-xl bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-700 transition hover:bg-primary-100">
                        {{ __('Mark All as Read') }}
                    </button>
                @endif
            </x-slot>
        </x-admin.page-tools>
    </x-slot>

    @if($announcements->isEmpty() && $notifications->isEmpty())
        <x-admin.empty-state
            :framed="true"
            :title="__('No notifications yet')"
            :description="__('New approvals, system messages, and announcements will appear here.')">
            <x-slot name="icon">
                <div class="rounded-xl bg-slate-100 p-4 text-slate-500">
                    <x-heroicon-o-inbox class="h-8 w-8" />
                </div>
            </x-slot>
        </x-admin.empty-state>
    @else
        <div id="admin-notifications-list" class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('Notification History') }}</h2>
                    <p class="sr-only">
                        {{ __('All user-specific notifications are listed here, including read items when the filter allows them.') }}
                    </p>
                </div>

                <div class="divide-y divide-slate-200/70">
                    @forelse($notifications as $notification)
                        @php($targetUrl = normalize_internal_url($notification->data['url'] ?? $notification->data['action_url'] ?? null))
                        <article class="px-5 py-4 transition hover:bg-slate-50/80">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                @if($targetUrl)
                                    <a href="{{ $targetUrl }}"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="block min-w-0 flex-1 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-950">
                                                {{ $notification->data['title'] ?? __('Notification') }}
                                            </h3>
                                            @if(is_null($notification->read_at))
                                                <x-admin.status-badge tone="info" pill>{{ __('Unread') }}</x-admin.status-badge>
                                            @else
                                                <x-admin.status-badge tone="neutral" pill>{{ __('Read') }}</x-admin.status-badge>
                                            @endif
                                        </div>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>
                                    </a>
                                @else
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-950">
                                                {{ $notification->data['title'] ?? __('Notification') }}
                                            </h3>
                                            @if(is_null($notification->read_at))
                                                <x-admin.status-badge tone="info" pill>{{ __('Unread') }}</x-admin.status-badge>
                                            @else
                                                <x-admin.status-badge tone="neutral" pill>{{ __('Read') }}</x-admin.status-badge>
                                            @endif
                                        </div>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>
                                    </div>
                                @endif

                                <time class="text-xs font-medium uppercase tracking-[0.12em] text-slate-400" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                    {{ $notification->created_at->diffForHumans() }}
                                </time>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                @if(is_null($notification->read_at))
                                    <button
                                        type="button"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="inline-flex min-h-[2.75rem] items-center rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100">
                                        {{ __('Mark as Read') }}
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500">
                            {{ __('No notifications found for this filter.') }}
                        </div>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('Announcements') }}</h2>
                    <p class="sr-only">
                        {{ __('Active announcements remain visible until dismissed or expired.') }}
                    </p>
                </div>

                <div class="divide-y divide-slate-200/70">
                    @forelse($announcements as $announcement)
                        <article class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-950">
                                            {{ $announcement->title }}
                                        </h3>
                                        @if($announcement->priority === 'high')
                                            <x-admin.status-badge tone="danger" pill>{{ __('Important') }}</x-admin.status-badge>
                                        @else
                                            <x-admin.status-badge tone="primary" pill>{{ ucfirst($announcement->priority) }}</x-admin.status-badge>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 220) }}
                                    </p>
                                </div>

                                <time class="text-xs font-medium uppercase tracking-[0.12em] text-slate-400" datetime="{{ $announcement->created_at?->toIso8601String() }}">
                                    {{ $announcement->created_at->diffForHumans() }}
                                </time>
                            </div>

                            <div class="mt-3">
                                <button
                                    type="button"
                                    wire:click="dismissAnnouncement({{ $announcement->id }})"
                                    class="inline-flex min-h-[2.75rem] items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:text-red-700">
                                    {{ __('Dismiss Announcement') }}
                                </button>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500">
                            {{ __('No active announcements right now.') }}
                        </div>
                    @endforelse
                </div>
            </x-admin.insight-panel>
        </div>

        @if($notifications->hasPages())
            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</x-admin.page-shell>
