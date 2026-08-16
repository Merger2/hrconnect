<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="hr-tasks-title" class="user-page-surface" @if($pollingEnabled) wire:poll.visible.20s @endif>
            <x-user.page-header
                :back-href="route('home')"
                :title="__('HR Tasks')"
                :description="__('Complete onboarding and offboarding follow-ups assigned to you.')"
                title-id="hr-tasks-title" module="hr">
                <x-slot name="icon">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <x-feedback.alert-messages />

                <div class="user-compact-filter mb-4">
                    <div class="user-filter-grid">
                        <div>
                            <x-forms.label for="hr-task-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                            <x-forms.input id="hr-task-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search task or employee...') }}" />
                        </div>
                        <div>
                            <x-forms.label for="hr-task-status" value="{{ __('Status') }}" class="mb-1.5 block" />
                            <x-forms.select id="hr-task-status" wire:model.live="statusFilter">
                                <option value="all">{{ __('All statuses') }}</option>
                                @foreach ($statuses as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse ($tasks as $task)
                        @php
                            $taskTone = match ($task->status) {
                                \App\Models\HrChecklistTask::STATUS_DONE => 'bg-emerald-100 text-emerald-800',
                                \App\Models\HrChecklistTask::STATUS_SKIPPED => 'bg-gray-100 text-gray-700',
                                \App\Models\HrChecklistTask::STATUS_BLOCKED => 'bg-red-100 text-red-800',
                                default => $task->isOverdue() ? 'bg-amber-100 text-amber-800' : 'bg-primary-100 text-primary-800',
                            };
                        @endphp
                        <article class="user-list-card overflow-hidden">
                            <div class="border-b border-slate-200/70 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $task->case->template->typeLabel() }}</p>
                                        <h2 class="mt-1 text-lg font-bold leading-tight text-gray-950">{{ __($task->title) }}</h2>
                                        <p class="mt-1 text-sm text-gray-600">{{ __($task->description ?? '') }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $taskTone }}">
                                        {{ $task->statusLabel() }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4 p-4">
                                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Employee') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ $task->case->user->name }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Due Date') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ $task->due_date?->translatedFormat('d M Y') ?? '-' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Effective Date') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ $task->case->effective_date->translatedFormat('d M Y') }}</dd>
                                    </div>
                                </dl>

                                @if ($task->attachment_path)
                                    @can('downloadAttachment', $task)
                                        <a href="{{ route('hr-checklist.task-attachment.download', $task) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/20 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-primary-600">
                                            <x-heroicon-m-paper-clip class="h-4 w-4" />
                                            {{ $task->attachment_original_name ?? __('Download attachment') }}
                                        </a>
                                    @endcan
                                @endif

                                @can('update', $task)
                                    <div class="space-y-3">
                                        <x-forms.label for="task-note-{{ $task->id }}" value="{{ __('Note') }}" />
                                        <x-forms.textarea id="task-note-{{ $task->id }}" wire:model="taskNotes.{{ $task->id }}" rows="2" placeholder="{{ __('Add a short note...') }}" />
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'done')" variant="soft-success" size="sm">{{ __('Done') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'blocked')" variant="soft-warning" size="sm">{{ __('Blocked') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'skipped')" variant="soft-primary" size="sm">{{ __('Skip') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'pending')" variant="ghost" size="sm">{{ __('Reopen') }}</x-actions.button>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-clipboard-document-check class="h-12 w-12 text-gray-300" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No HR tasks found') }}</h3>
                            <p class="user-empty-state__copy">{{ __('Assigned checklist tasks will appear here when HR starts an onboarding or offboarding case.') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
