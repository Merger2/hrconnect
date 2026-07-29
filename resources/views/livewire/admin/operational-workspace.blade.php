<x-admin.page-shell
    :title="__('Operations Workspace')"
    :description="__('Manage clients, projects, tasks, and field-work checklists in one company-scoped workspace.')"
    :show-description="true"
>
    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-3 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <x-forms.label for="operations-search" value="{{ __('Search workspace') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input id="operations-search" type="search" wire:model.live.debounce.300ms="search" class="w-full pl-11" placeholder="{{ __('Search clients, branches, or projects...') }}" />
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800 sm:text-sm md:grid-cols-4">
                    @foreach ([
                        'projects' => __('Projects'),
                        'tasks' => __('Tasks'),
                        'clients' => __('Clients'),
                        'branches' => __('Branches'),
                    ] as $tab => $label)
                        <button
                            type="button"
                            wire:click="$set('activeTab', '{{ $tab }}')"
                            class="rounded-lg px-2.5 py-2 transition sm:px-3 {{ $activeTab === $tab ? 'bg-white text-primary-700 shadow-sm dark:bg-slate-950 dark:text-primary-300' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="order-2 space-y-4 xl:order-1">
            @if ($activeTab === 'projects')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Projects') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3 p-4">
                        @forelse ($projects as $project)
                            @php
                                $financial = $projectFinancials[$project->id] ?? [
                                    'pipeline' => 0,
                                    'invoiced' => 0,
                                    'paid' => 0,
                                    'outstanding' => 0,
                                    'estimated_margin' => 0,
                                ];
                            @endphp
                            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ $project->name }}</h3>
                                            <x-admin.status-badge tone="success">{{ __(str($project->status)->headline()->toString()) }}</x-admin.status-badge>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ $project->company?->name }}
                                            @if ($project->client)
                                                · {{ $project->client->name }}
                                            @endif
                                            @if ($project->branch)
                                                · {{ $project->branch->name }}
                                            @endif
                                        </p>
                                        @if ($project->description)
                                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $project->description }}</p>
                                        @endif
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ __('Tasks: :count', ['count' => $project->tasks_count]) }}
                                    </div>
                                </div>
                                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm lg:grid-cols-4">
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Pipeline') }}</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">Rp{{ number_format($financial['pipeline'], 0, ',', '.') }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Invoiced') }}</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">Rp{{ number_format($financial['invoiced'], 0, ',', '.') }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Paid') }}</dt>
                                        <dd class="mt-1 font-semibold text-emerald-600 dark:text-emerald-300">Rp{{ number_format($financial['paid'], 0, ',', '.') }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outstanding') }}</dt>
                                        <dd class="mt-1 font-semibold {{ $financial['outstanding'] > 0 ? 'text-amber-600 dark:text-amber-300' : 'text-slate-900 dark:text-white' }}">
                                            Rp{{ number_format($financial['outstanding'], 0, ',', '.') }}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        @empty
                            <x-admin.empty-state :title="__('No projects yet')" :description="__('Create your first project from the active action panel.')" class="border-0 bg-transparent shadow-none">
                                <x-slot name="icon">
                                    <x-heroicon-o-rectangle-stack class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                                </x-slot>
                            </x-admin.empty-state>
                        @endforelse
                    </div>
                </x-admin.panel>
            @elseif ($activeTab === 'tasks')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Tasks & Checklists') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3 p-4">
                        @forelse ($projects as $project)
                            @foreach ($project->tasks as $task)
                                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ $task->title }}</h3>
                                                <x-admin.status-badge :tone="$task->status === \App\Models\ProjectTask::STATUS_DONE ? 'success' : ($task->status === \App\Models\ProjectTask::STATUS_IN_PROGRESS ? 'primary' : 'neutral')">
                                                    {{ __(str($task->status)->replace('_', ' ')->headline()->toString()) }}
                                                </x-admin.status-badge>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                {{ $project->name }}
                                                @if ($task->assignee)
                                                    · {{ __('Assigned to :name', ['name' => $task->assignee->name]) }}
                                                @endif
                                                @if ($task->due_date)
                                                    · {{ __('Due :date', ['date' => $task->due_date->format('d M Y')]) }}
                                                @endif
                                            </p>
                                        </div>

                                        @if ($canManage)
                                            <div class="flex flex-wrap gap-2">
                                                <x-actions.button type="button" wire:click="updateTaskStatus({{ $task->id }}, '{{ \App\Models\ProjectTask::STATUS_IN_PROGRESS }}')" variant="soft-primary" size="sm">{{ __('Start') }}</x-actions.button>
                                                <x-actions.button type="button" wire:click="updateTaskStatus({{ $task->id }}, '{{ \App\Models\ProjectTask::STATUS_DONE }}')" variant="soft-success" size="sm">{{ __('Done') }}</x-actions.button>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($task->checklistItems->isNotEmpty())
                                        <div class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-2">
                                            @foreach ($task->checklistItems as $item)
                                                <button
                                                    type="button"
                                                    wire:click="toggleChecklistItem({{ $item->id }})"
                                                    @disabled(! $canManage)
                                                    class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-left text-sm ring-1 ring-slate-200 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-slate-950 dark:ring-slate-800 dark:hover:bg-slate-900"
                                                >
                                                    @if ($item->is_done)
                                                        <x-heroicon-m-check-circle class="h-5 w-5 shrink-0 text-emerald-500" />
                                                    @else
                                                        <x-heroicon-m-circle-stack class="h-5 w-5 shrink-0 text-slate-400" />
                                                    @endif
                                                    <span class="{{ $item->is_done ? 'text-slate-400 line-through' : 'text-slate-700 dark:text-slate-200' }}">{{ $item->title }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($task->visitEvidences->isNotEmpty())
                                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                                            <div class="flex items-center justify-between gap-3">
                                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Visit Evidence') }}</h4>
                                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $task->visitEvidences->count() }}</span>
                                            </div>
                                            <div class="mt-3 space-y-2">
                                                @foreach ($task->visitEvidences->take(3) as $evidence)
                                                    <div class="flex flex-col gap-2 rounded-lg bg-white p-3 text-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 md:flex-row md:items-center md:justify-between">
                                                        <div class="min-w-0">
                                                            <p class="font-semibold text-slate-800 dark:text-slate-100">
                                                                {{ $evidence->user?->name ?? __('Unknown user') }}
                                                                · {{ $evidence->visited_at?->format('d M Y H:i') }}
                                                            </p>
                                                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                                                {{ $evidence->notes ?: __('No notes') }}
                                                            </p>
                                                        </div>
                                                        @can('downloadPhoto', $evidence)
                                                            @if ($evidence->photo_path)
                                                                <a href="{{ route('operations.visit-evidence.photo', $evidence) }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-primary-700 ring-1 ring-primary-200 hover:bg-primary-50 dark:text-primary-200 dark:ring-primary-800 dark:hover:bg-primary-950/40">
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
                                </article>
                            @endforeach
                        @empty
                            <x-admin.empty-state :title="__('No tasks yet')" :description="__('Create a project and add tasks from the active action panel.')" class="border-0 bg-transparent shadow-none">
                                <x-slot name="icon">
                                    <x-heroicon-o-check-badge class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                                </x-slot>
                            </x-admin.empty-state>
                        @endforelse
                    </div>
                </x-admin.panel>
            @elseif ($activeTab === 'clients')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Clients') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2">
                        @forelse ($clients as $client)
                            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                <h3 class="font-semibold text-slate-950 dark:text-white">{{ $client->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $client->company?->name }}</p>
                                @if ($client->contact_name || $client->contact_phone)
                                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $client->contact_name }} {{ $client->contact_phone ? '· '.$client->contact_phone : '' }}</p>
                                @endif
                            </article>
                        @empty
                            <x-admin.empty-state :title="__('No clients yet')" :description="__('Create clients from the active action panel.')" class="border-0 bg-transparent shadow-none" />
                        @endforelse
                    </div>
                </x-admin.panel>
            @else
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Branches & Locations') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2">
                        @forelse ($branches as $branch)
                            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ $branch->name }}</h3>
                                    <x-admin.status-badge tone="primary">{{ __(str($branch->type)->headline()->toString()) }}</x-admin.status-badge>
                                </div>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $branch->company?->name }}</p>
                                @if ($branch->address)
                                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $branch->address }}</p>
                                @endif
                            </article>
                        @empty
                            <x-admin.empty-state :title="__('No branches yet')" :description="__('Create company branches, stores, or field locations from the active action panel.')" class="border-0 bg-transparent shadow-none" />
                        @endforelse
                    </div>
                </x-admin.panel>
            @endif
        </div>

        <div class="order-1 space-y-4 xl:order-2">
            @if ($canManage)
                <x-admin.panel class="border-primary-200 bg-primary-50/60 dark:border-primary-900/60 dark:bg-primary-950/20">
                    <div class="space-y-1 p-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-800 dark:text-primary-200">{{ __('Quick action') }}</p>
                        <p class="text-sm leading-5 text-primary-700 dark:text-primary-100">
                            {{ __('The operations form follows the selected tab so each workflow stays focused.') }}
                        </p>
                    </div>
                </x-admin.panel>

                @if ($activeTab === 'projects')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Create Project') }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('Start from company, client, location, and manager so tasks have clear ownership.') }}</p>
                    </div>
                    <form wire:submit.prevent="createProject" class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <x-forms.label for="project-company" value="{{ __('Company') }}" />
                            <x-forms.select id="project-company" wire:model.live="projectCompanyId" class="w-full" placeholder="{{ __('Choose company') }}">
                                <option value="">{{ __('Choose company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="projectCompanyId" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="project-name" value="{{ __('Project name') }}" />
                            <x-forms.input id="project-name" wire:model.live="projectName" placeholder="{{ __('e.g. Store rollout Q2') }}" />
                            <x-forms.input-error for="projectName" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <x-forms.label for="project-client" value="{{ __('Client') }}" />
                                <x-forms.select id="project-client" wire:model.live="projectClientId" class="w-full" placeholder="{{ __('No client') }}">
                                    <option value="">{{ __('No client') }}</option>
                                    @foreach ($projectClientOptions as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </x-forms.select>
                                <x-forms.input-error for="projectClientId" />
                            </div>

                            <div class="space-y-1.5">
                                <x-forms.label for="project-branch" value="{{ __('Branch / location') }}" />
                                <x-forms.select id="project-branch" wire:model.live="projectBranchId" class="w-full" placeholder="{{ __('No branch') }}">
                                    <option value="">{{ __('No branch') }}</option>
                                    @foreach ($projectBranchOptions as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </x-forms.select>
                                <x-forms.input-error for="projectBranchId" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="project-manager" value="{{ __('Project manager') }}" />
                            <x-forms.select id="project-manager" wire:model.live="projectManagerId" class="w-full" placeholder="{{ __('No manager') }}">
                                <option value="">{{ __('No manager') }}</option>
                                @foreach ($projectManagerOptions as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="projectManagerId" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="project-description" value="{{ __('Description') }}" />
                            <x-forms.textarea id="project-description" wire:model.live="projectDescription" rows="3" placeholder="{{ __('Scope, location notes, or handover context.') }}" />
                            <x-forms.input-error for="projectDescription" />
                        </div>

                        <x-actions.button type="submit" class="w-full">
                            <x-heroicon-m-plus class="h-5 w-5" />
                            <span>{{ __('Create Project') }}</span>
                        </x-actions.button>
                    </form>
                </x-admin.panel>

                @elseif ($activeTab === 'tasks')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Create Task') }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('Assign work, priority, due date, and checklist items without leaving the task tab.') }}</p>
                    </div>
                    <form wire:submit.prevent="createTask" class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <x-forms.label for="task-project" value="{{ __('Project') }}" />
                            <x-forms.select id="task-project" wire:model.live="taskProjectId" class="w-full" placeholder="{{ __('Choose project') }}">
                                <option value="">{{ __('Choose project') }}</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }} · {{ $project->company?->name }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="taskProjectId" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="task-title" value="{{ __('Task title') }}" />
                            <x-forms.input id="task-title" wire:model.live="taskTitle" placeholder="{{ __('e.g. Visit client location') }}" />
                            <x-forms.input-error for="taskTitle" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="task-assignee" value="{{ __('Assignee') }}" />
                            <x-forms.select id="task-assignee" wire:model.live="taskAssignedTo" class="w-full" placeholder="{{ __('Unassigned') }}">
                                <option value="">{{ __('Unassigned') }}</option>
                                @foreach ($taskAssigneeOptions as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="taskAssignedTo" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <x-forms.label for="task-priority" value="{{ __('Priority') }}" />
                                <x-forms.select id="task-priority" wire:model.live="taskPriority" class="w-full" placeholder="{{ __('Priority') }}">
                                    <option value="{{ \App\Models\ProjectTask::PRIORITY_LOW }}">{{ __('Low') }}</option>
                                    <option value="{{ \App\Models\ProjectTask::PRIORITY_NORMAL }}">{{ __('Normal') }}</option>
                                    <option value="{{ \App\Models\ProjectTask::PRIORITY_HIGH }}">{{ __('High') }}</option>
                                </x-forms.select>
                                <x-forms.input-error for="taskPriority" />
                            </div>

                            <div class="space-y-1.5">
                                <x-forms.label for="task-due-date" value="{{ __('Due date') }}" />
                                <x-forms.input id="task-due-date" type="date" wire:model.live="taskDueDate" placeholder="{{ __('Optional') }}" />
                                <x-forms.input-error for="taskDueDate" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="task-checklist" value="{{ __('Checklist') }}" />
                            <x-forms.textarea id="task-checklist" wire:model.live="taskChecklist" rows="4" placeholder="{{ __('One checklist item per line, e.g. Take location photo') }}" />
                            <x-forms.input-error for="taskChecklist" />
                        </div>

                        <x-actions.button type="submit" class="w-full">
                            <x-heroicon-m-check class="h-5 w-5" />
                            <span>{{ __('Create Task') }}</span>
                        </x-actions.button>
                    </form>
                </x-admin.panel>

                @elseif ($activeTab === 'clients')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Create Client') }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('Save client contacts once so projects, quotations, and invoices can reuse them.') }}</p>
                    </div>
                    <form wire:submit.prevent="createClient" class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <x-forms.label for="client-company" value="{{ __('Company') }}" />
                            <x-forms.select id="client-company" wire:model.live="clientCompanyId" class="w-full" placeholder="{{ __('Choose company') }}">
                                <option value="">{{ __('Choose company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="clientCompanyId" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="client-name" value="{{ __('Client name') }}" />
                            <x-forms.input id="client-name" wire:model.live="clientName" placeholder="{{ __('e.g. PT Client Utama') }}" />
                            <x-forms.input-error for="clientName" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <x-forms.label for="client-contact-name" value="{{ __('Contact name') }}" />
                                <x-forms.input id="client-contact-name" wire:model.live="clientContactName" placeholder="{{ __('Optional') }}" />
                                <x-forms.input-error for="clientContactName" />
                            </div>
                            <div class="space-y-1.5">
                                <x-forms.label for="client-contact-phone" value="{{ __('Contact phone') }}" />
                                <x-forms.input id="client-contact-phone" wire:model.live="clientContactPhone" placeholder="{{ __('08xxxxxxxxxx') }}" inputmode="tel" />
                                <x-forms.input-error for="clientContactPhone" />
                            </div>
                        </div>

                        <x-actions.button type="submit" variant="soft-primary" class="w-full">{{ __('Create Client') }}</x-actions.button>
                    </form>
                </x-admin.panel>

                @elseif ($activeTab === 'branches')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Create Branch') }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('Add stores, offices, warehouses, or field locations for scoped operations.') }}</p>
                    </div>
                    <form wire:submit.prevent="createBranch" class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <x-forms.label for="branch-company" value="{{ __('Company') }}" />
                            <x-forms.select id="branch-company" wire:model.live="branchCompanyId" class="w-full" placeholder="{{ __('Choose company') }}">
                                <option value="">{{ __('Choose company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="branchCompanyId" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="branch-name" value="{{ __('Location name') }}" />
                            <x-forms.input id="branch-name" wire:model.live="branchName" placeholder="{{ __('e.g. Bandung Store') }}" />
                            <x-forms.input-error for="branchName" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="branch-type" value="{{ __('Location type') }}" />
                            <x-forms.select id="branch-type" wire:model.live="branchType" class="w-full" placeholder="{{ __('Choose type') }}">
                                @foreach ($branchTypes as $type)
                                    <option value="{{ $type }}">{{ __(str($type)->headline()->toString()) }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="branchType" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="branch-address" value="{{ __('Address') }}" />
                            <x-forms.textarea id="branch-address" wire:model.live="branchAddress" rows="3" placeholder="{{ __('Street, city, or field location notes.') }}" />
                            <x-forms.input-error for="branchAddress" />
                        </div>

                        <x-actions.button type="submit" variant="soft-success" class="w-full">{{ __('Create Branch') }}</x-actions.button>
                    </form>
                </x-admin.panel>
                @endif
            @else
                <x-admin.alert tone="info">
                    {{ __('You can view this workspace, but need manage permission to create or update operational records.') }}
                </x-admin.alert>
            @endif
        </div>
    </div>
</x-admin.page-shell>
