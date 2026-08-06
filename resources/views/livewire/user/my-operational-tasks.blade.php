<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-operational-tasks-title" class="user-page-surface" @if($pollingEnabled) wire:poll.visible.20s @endif>
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Operational Tasks')"
                :description="__('Follow assigned client, project, and field-work tasks with checklist and visit evidence.')"
                title-id="my-operational-tasks-title" module="reports">
                <x-slot name="icon">
                    <x-heroicon-o-briefcase class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <x-feedback.alert-messages />

                <div class="user-compact-filter mb-4">
                    <div class="user-filter-grid">
                        <div>
                            <x-forms.label for="operational-task-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                            <x-forms.input id="operational-task-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search task or project...') }}" />
                        </div>
                        <div>
                            <x-forms.label for="operational-task-status" value="{{ __('Status') }}" class="mb-1.5 block" />
                            <x-forms.select id="operational-task-status" wire:model.live="statusFilter">
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
                                \App\Models\ProjectTask::STATUS_DONE => 'bg-emerald-100 text-emerald-800',
                                \App\Models\ProjectTask::STATUS_IN_PROGRESS => 'bg-primary-100 text-primary-800',
                                default => 'bg-slate-100 text-slate-800',
                            };
                        @endphp
                        <article class="user-list-card overflow-hidden">
                            <div class="border-b border-slate-200/70 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ $task->project?->name }}
                                            @if ($task->project?->client)
                                                · {{ $task->project->client->name }}
                                            @endif
                                        </p>
                                        <h2 class="mt-1 text-lg font-bold leading-tight text-gray-950">{{ $task->title }}</h2>
                                        @if ($task->project?->branch)
                                            <p class="mt-1 text-sm text-gray-600">{{ $task->project->branch->name }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $taskTone }}">
                                        {{ __(str($task->status)->replace('_', ' ')->headline()->toString()) }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4 p-4">
                                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                                    <div class="rounded-xl bg-slate-50/70 p-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Priority') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ __(str($task->priority)->headline()->toString()) }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50/70 p-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Due Date') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ $task->due_date?->translatedFormat('d M Y') ?? '-' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50/70 p-3">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Visit Evidence') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ $task->visitEvidences->count() }}</dd>
                                    </div>
                                </dl>

                                @if ($task->checklistItems->isNotEmpty())
                                    <div class="space-y-2">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Checklist') }}</h3>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            @foreach ($task->checklistItems as $item)
                                                <button
                                                    type="button"
                                                    wire:click="toggleChecklistItem({{ $item->id }})"
                                                    class="flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2 text-left text-sm ring-1 ring-gray-200 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-600"
                                                >
                                                    @if ($item->is_done)
                                                        <x-heroicon-m-check-circle class="h-5 w-5 shrink-0 text-emerald-500" />
                                                    @else
                                                        <x-heroicon-m-circle-stack class="h-5 w-5 shrink-0 text-gray-400" />
                                                    @endif
                                                    <span class="{{ $item->is_done ? 'text-gray-400 line-through' : 'text-gray-700' }}">{{ $item->title }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <x-actions.button type="button" wire:click="updateTaskStatus({{ $task->id }}, '{{ \App\Models\ProjectTask::STATUS_IN_PROGRESS }}')" variant="soft-primary" size="sm">{{ __('Start') }}</x-actions.button>
                                    <x-actions.button type="button" wire:click="updateTaskStatus({{ $task->id }}, '{{ \App\Models\ProjectTask::STATUS_DONE }}')" variant="soft-success" size="sm">{{ __('Done') }}</x-actions.button>
                                    <x-actions.button type="button" wire:click="updateTaskStatus({{ $task->id }}, '{{ \App\Models\ProjectTask::STATUS_TODO }}')" variant="ghost" size="sm">{{ __('Reopen') }}</x-actions.button>
                                </div>

                                <form wire:submit.prevent="submitVisitEvidence({{ $task->id }})" class="user-upload-dropzone">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ __('Submit Visit Evidence') }}</h3>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        <x-forms.input type="number" step="0.0000001" wire:model="visitLatitude.{{ $task->id }}" placeholder="{{ __('Latitude') }}" />
                                        <x-forms.input type="number" step="0.0000001" wire:model="visitLongitude.{{ $task->id }}" placeholder="{{ __('Longitude') }}" />
                                        <x-forms.input type="number" wire:model="visitAccuracy.{{ $task->id }}" placeholder="{{ __('Accuracy meters') }}" />
                                    </div>
                                    <x-forms.textarea wire:model="visitNotes.{{ $task->id }}" rows="2" class="mt-3" placeholder="{{ __('Notes from the location...') }}" />
                                    <div class="mt-3">
                                        <x-forms.file-input
                                            id="visit-photo-{{ $task->id }}"
                                            wire:model="visitPhotos.{{ $task->id }}"
                                            accept="image/jpeg,image/png,image/webp"
                                            button-label="{{ __('Choose visit photo') }}"
                                            empty-text="{{ __('No photo selected') }}"
                                        />
                                        <x-forms.input-error for="visitPhotos.{{ $task->id }}" class="mt-2" />
                                    </div>
                                    <x-actions.button type="submit" class="mt-3 w-full sm:w-auto">
                                        <x-heroicon-m-camera class="h-5 w-5" />
                                        <span>{{ __('Submit Evidence') }}</span>
                                    </x-actions.button>
                                </form>

                                @if ($task->visitEvidences->isNotEmpty())
                                    <div class="rounded-[1.15rem] border border-slate-200/70 bg-white/55 p-3">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Recent Evidence') }}</h3>
                                        <div class="mt-3 space-y-2">
                                            @foreach ($task->visitEvidences->take(3) as $evidence)
                                                <div class="flex flex-col gap-2 rounded-xl bg-gray-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="font-semibold text-gray-800">{{ $evidence->visited_at?->translatedFormat('d M Y H:i') }}</p>
                                                        <p class="text-xs text-gray-500">{{ $evidence->notes ?: __('No notes') }}</p>
                                                    </div>
                                                    @can('downloadPhoto', $evidence)
                                                        @if ($evidence->photo_path)
                                                            <a href="{{ route('operations.visit-evidence.photo', $evidence) }}" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-primary-700 ring-1 ring-primary-200 hover:bg-primary-50">
                                                                <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                                                                <span>{{ __('Photo') }}</span>
                                                            </a>
                                                        @endif
                                                    @endcan
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-briefcase class="h-12 w-12 text-gray-300" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No operational tasks found') }}</h3>
                            <p class="user-empty-state__copy">{{ __('Assigned client, project, and field-work tasks will appear here.') }}</p>
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
