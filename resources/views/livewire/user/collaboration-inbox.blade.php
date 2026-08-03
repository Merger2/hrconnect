<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section
            aria-labelledby="collaboration-inbox-title"
            class="user-page-surface"
            @if (! $realtimeEnabled && $pollingEnabled) wire:poll.{{ $pollInterval }} @endif
        >
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Team Chat')"
                :description="__('Follow project conversations, shared files, and team updates.')"
                title-id="collaboration-inbox-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $realtimeEnabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-800' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-800' }}">
                        <span class="h-2 w-2 rounded-full {{ $realtimeEnabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ $realtimeEnabled ? __('Live') : __('Auto refresh') }}
                    </span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <x-feedback.alert-messages />

                <div class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)]">
                    <aside class="space-y-3">
                        <div class="user-compact-filter">
                            <x-forms.label for="collaboration-user-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                            <x-forms.input id="collaboration-user-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search conversations...') }}" />
                        </div>

                        <div class="space-y-2">
                            @forelse ($threads as $thread)
                                @php
                                    $latestMessage = $thread->messages()->latest()->first();
                                    $isSelected = $selectedThread?->is($thread) ?? false;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectThread({{ $thread->id }})"
                                    class="w-full rounded-[1.15rem] border px-4 py-3 text-left transition {{ $isSelected ? 'border-primary-300 bg-primary-50/80 shadow-sm dark:border-primary-800 dark:bg-primary-950/30' : 'border-slate-200 bg-white/80 hover:border-primary-200 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950/40 dark:hover:border-primary-900 dark:hover:bg-primary-950/20' }}"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-slate-800">
                                            <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <h2 class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ $thread->title }}</h2>
                                                <span class="shrink-0 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $thread->messages_count }}</span>
                                            </div>
                                            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                                {{ $thread->project?->name ?? $thread->company?->name ?? __('Team') }}
                                            </p>
                                            <p class="mt-2 line-clamp-1 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $latestMessage?->body ?? __('No messages yet.') }}
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div class="user-empty-state py-8">
                                    <div class="user-empty-state__icon">
                                        <x-heroicon-o-chat-bubble-left-right class="h-10 w-10 text-gray-300 dark:text-gray-500" />
                                    </div>
                                    <h2 class="user-empty-state__title">{{ __('No conversations yet') }}</h2>
                                    <p class="user-empty-state__copy">{{ __('Your company or project conversations will appear here.') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </aside>

                    <main class="min-h-[32rem] overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white/85 shadow-sm dark:border-slate-800 dark:bg-slate-950/45">
                        @if ($selectedThread)
                            <header class="border-b border-slate-200/80 px-4 py-4 dark:border-slate-800">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <h2 class="truncate text-lg font-bold text-slate-950 dark:text-white">{{ $selectedThread->title }}</h2>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ $selectedThread->members->count() }} {{ __('members') }}
                                            @if ($selectedThread->project)
                                                · {{ $selectedThread->project->name }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex -space-x-2">
                                        @foreach ($selectedThread->members->take(5) as $member)
                                            <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}" class="h-9 w-9 rounded-full border-2 border-white object-cover dark:border-slate-950">
                                        @endforeach
                                    </div>
                                </div>
                            </header>

                            <div class="max-h-[34rem] space-y-3 overflow-y-auto px-4 py-4">
                                @forelse ($messages as $message)
                                    @php($mine = (string) $message->user_id === (string) auth()->id())
                                    <article class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                        <div class="max-w-[86%] rounded-[1.25rem] px-4 py-3 {{ $mine ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-900 dark:bg-slate-900 dark:text-slate-100' }}">
                                            <div class="mb-1 flex items-center justify-between gap-3 text-xs font-semibold {{ $mine ? 'text-primary-50' : 'text-slate-500 dark:text-slate-400' }}">
                                                <span>{{ $message->user?->name ?? __('System') }}</span>
                                                <time datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->format('H:i') }}</time>
                                            </div>
                                            <p class="whitespace-pre-line text-sm leading-relaxed">{{ $message->body }}</p>
                                        </div>
                                    </article>
                                @empty
                                    <div class="user-empty-state py-10">
                                        <div class="user-empty-state__icon">
                                            <x-heroicon-o-chat-bubble-bottom-center-text class="h-10 w-10 text-gray-300 dark:text-gray-500" />
                                        </div>
                                        <h3 class="user-empty-state__title">{{ __('No messages yet.') }}</h3>
                                        <p class="user-empty-state__copy">{{ __('Send the first update to this thread.') }}</p>
                                    </div>
                                @endforelse
                            </div>

                            @if ($files->isNotEmpty())
                                <div class="border-t border-slate-200/80 px-4 py-3 dark:border-slate-800">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Shared files') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($files as $file)
                                            <a href="{{ route('collaboration.files.download', $file) }}" class="inline-flex min-w-max items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-primary-50 hover:text-primary-700 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-800 dark:hover:bg-primary-950/30 dark:hover:text-primary-200">
                                                <x-heroicon-m-paper-clip class="h-4 w-4" />
                                                <span>{{ $file->original_name }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <form wire:submit="postMessage" class="border-t border-slate-200/80 p-4 dark:border-slate-800">
                                <x-forms.input-error for="messageBody" class="mb-2" />
                                <x-forms.input-error for="uploadedFile" class="mb-2" />
                                <div class="mb-3 flex flex-col gap-2 rounded-[1rem] border border-dashed border-slate-200 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-900/40 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <label for="collaboration-uploaded-file" class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Attach file') }}</label>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('PDF, Office, CSV, TXT, or image files up to 12 MB.') }}</p>
                                    </div>
                                    <input id="collaboration-uploaded-file" type="file" wire:model="uploadedFile" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:file:bg-primary-950/50 dark:file:text-primary-200 sm:max-w-xs" accept=".csv,.doc,.docx,.jpeg,.jpg,.pdf,.png,.txt,.webp,.xls,.xlsx">
                                </div>
                                <div class="flex items-end gap-2">
                                    <label for="collaboration-message-body" class="sr-only">{{ __('Message') }}</label>
                                    <textarea id="collaboration-message-body" wire:model="messageBody" rows="2" class="block min-h-[3.25rem] flex-1 resize-none rounded-[1rem] border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="{{ __('Write a short update...') }}"></textarea>
                                    <x-actions.button type="submit" class="min-h-[3.25rem] shrink-0 rounded-2xl px-4">
                                        <x-heroicon-m-paper-airplane class="h-5 w-5" />
                                        <span class="sr-only sm:not-sr-only">{{ __('Send') }}</span>
                                    </x-actions.button>
                                </div>
                            </form>
                        @else
                            <div class="flex min-h-[32rem] items-center justify-center p-6">
                                <div class="user-empty-state max-w-sm">
                                    <div class="user-empty-state__icon">
                                        <x-heroicon-o-chat-bubble-left-right class="h-12 w-12 text-gray-300 dark:text-gray-500" />
                                    </div>
                                    <h2 class="user-empty-state__title">{{ __('No conversations yet') }}</h2>
                                    <p class="user-empty-state__copy">{{ __('Ask an admin to add you to a company or project conversation.') }}</p>
                                </div>
                            </div>
                        @endif
                    </main>
                </div>
            </div>
        </section>
    </div>
</div>
