<x-admin.page-shell
    :title="__('Custom Form Builder')"
    :description="__('Create company-scoped forms for HR, operations, visit reports, surveys, and internal requests.')"
    :show-description="true"
>
    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-3 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <x-forms.label for="custom-form-search" value="{{ __('Search forms') }}" class="mb-1.5 block" />
                <x-forms.input id="custom-form-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search title or category...') }}" />
            </div>
            <div class="lg:col-span-5">
                <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800 sm:text-sm">
                    @foreach ([
                        'templates' => __('Templates'),
                        'submissions' => __('Submissions'),
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

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="order-2 space-y-4 xl:order-1">
            @if ($activeTab === 'templates')
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Form Templates') }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-4 lg:grid-cols-2">
                        @forelse ($templates as $template)
                            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-950 dark:text-white">{{ $template->title }}</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $template->company?->name }} · {{ __(str($template->category)->headline()->toString()) }}</p>
                                    </div>
                                    <x-admin.status-badge :tone="$template->is_active ? 'success' : 'neutral'">
                                        {{ $template->is_active ? __('Active') : __('Inactive') }}
                                    </x-admin.status-badge>
                                </div>
                                @if ($template->description)
                                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $template->description }}</p>
                                @endif
                                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Fields') }}</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ count($template->fields ?? []) }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950/50">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Submissions') }}</dt>
                                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $template->submissions_count }}</dd>
                                    </div>
                                </dl>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach (($template->fields ?? []) as $field)
                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $field['label'] }} · {{ $field['type'] }}
                                        </span>
                                    @endforeach
                                </div>
                                @if ($canManage)
                                    <x-actions.button type="button" wire:click="toggleTemplate({{ $template->id }})" variant="soft-secondary" size="sm" class="mt-4">
                                        {{ $template->is_active ? __('Disable') : __('Enable') }}
                                    </x-actions.button>
                                @endif
                                @if (($template->metadata['automation']['type'] ?? null) === 'project_task')
                                    <div class="mt-3 rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 dark:bg-primary-950/40 dark:text-primary-200">
                                        {{ __('Auto-task enabled') }}
                                    </div>
                                @endif
                            </article>
                        @empty
                            <x-admin.empty-state :title="__('No custom forms yet')" :description="__('Create a form template from the panel on the right.')" class="border-0 bg-transparent shadow-none" />
                        @endforelse
                    </div>
                </x-admin.panel>
            @else
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Recent Submissions') }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-4">
                        @forelse ($submissions as $submission)
                            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-950 dark:text-white">{{ $submission->template?->title }}</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ $submission->submitter?->name ?? __('Unknown user') }} · {{ $submission->created_at?->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                    <x-admin.status-badge tone="success">{{ __(str($submission->status)->headline()->toString()) }}</x-admin.status-badge>
                                </div>
                                <dl class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @foreach (($submission->payload ?? []) as $key => $value)
                                        <div class="rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-950/50">
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __(str($key)->replace('_', ' ')->headline()->toString()) }}</dt>
                                            <dd class="mt-1 text-slate-900 dark:text-white">{{ is_array($value) ? json_encode($value) : ($value ?: '-') }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </article>
                        @empty
                            <x-admin.empty-state :title="__('No submissions yet')" :description="__('User submissions will appear here.')" class="border-0 bg-transparent shadow-none" />
                        @endforelse
                    </div>
                </x-admin.panel>
            @endif
        </div>

        <div class="order-1 space-y-4 xl:order-2">
            @if ($canManage)
                <x-admin.panel>
                    <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Create Template') }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ __('Build reusable forms with fields, automation, and company scope from one focused panel.') }}</p>
                    </div>
                    <form wire:submit.prevent="createTemplate" class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <x-forms.label for="template-company" value="{{ __('Company') }}" />
                            <x-forms.select id="template-company" wire:model.live="templateCompanyId" class="w-full" placeholder="{{ __('Choose company') }}">
                                <option value="">{{ __('Choose company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="templateCompanyId" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="template-title" value="{{ __('Form title') }}" />
                            <x-forms.input id="template-title" wire:model.live="templateTitle" placeholder="{{ __('e.g. Visit Report') }}" />
                            <x-forms.input-error for="templateTitle" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="template-category" value="{{ __('Category') }}" />
                            <x-forms.select id="template-category" wire:model.live="templateCategory" class="w-full" placeholder="{{ __('Choose category') }}">
                                <option value="general">{{ __('General') }}</option>
                                <option value="operations">{{ __('Operations') }}</option>
                                <option value="hr">{{ __('HR') }}</option>
                                <option value="visit">{{ __('Visit') }}</option>
                                <option value="survey">{{ __('Survey') }}</option>
                                <option value="finance">{{ __('Finance') }}</option>
                            </x-forms.select>
                            <x-forms.input-error for="templateCategory" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="template-description" value="{{ __('Description') }}" />
                            <x-forms.textarea id="template-description" wire:model.live="templateDescription" rows="3" placeholder="{{ __('Who should use this form and when?') }}" />
                            <x-forms.input-error for="templateDescription" />
                        </div>

                        <div class="space-y-1.5">
                            <x-forms.label for="field-lines" value="{{ __('Fields') }}" />
                            <x-forms.textarea id="field-lines" wire:model.live="fieldLines" rows="7" placeholder="{{ __('Label|type|required|options') }}" />
                            <x-forms.input-error for="fieldLines" />
                        </div>

                        <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-950/50 dark:text-slate-300">
                            {{ __('Format: Label|type|required|options. Types: :types', ['types' => implode(', ', $fieldTypes)]) }}
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/50">
                            <label class="flex items-start gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="automationEnabled" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900">
                                <span>
                                    {{ __('Create operational task after submission') }}
                                    <span class="mt-1 block text-xs font-normal text-slate-500 dark:text-slate-400">{{ __('Useful for visit reports, follow-ups, surveys, or field requests that need action.') }}</span>
                                </span>
                            </label>

                            @if ($automationEnabled)
                                <div class="mt-3 space-y-3">
                                    <div class="space-y-1.5">
                                        <x-forms.label for="automation-project" value="{{ __('Project') }}" />
                                        <x-forms.select id="automation-project" wire:model.live="automationProjectId" class="w-full" placeholder="{{ __('Choose project') }}">
                                            <option value="">{{ __('Choose project') }}</option>
                                            @foreach ($automationProjectOptions as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endforeach
                                        </x-forms.select>
                                    </div>
                                    <x-forms.input-error for="automationProjectId" />
                                    <div class="space-y-1.5">
                                        <x-forms.label for="automation-task-title" value="{{ __('Task title') }}" />
                                        <x-forms.input id="automation-task-title" wire:model.live="automationTaskTitle" placeholder="{{ __('Optional') }}" />
                                        <x-forms.input-error for="automationTaskTitle" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <x-forms.label for="automation-task-priority" value="{{ __('Priority') }}" />
                                        <x-forms.select id="automation-task-priority" wire:model.live="automationTaskPriority" class="w-full" placeholder="{{ __('Priority') }}">
                                            <option value="{{ \App\Models\ProjectTask::PRIORITY_LOW }}">{{ __('Low') }}</option>
                                            <option value="{{ \App\Models\ProjectTask::PRIORITY_NORMAL }}">{{ __('Normal') }}</option>
                                            <option value="{{ \App\Models\ProjectTask::PRIORITY_HIGH }}">{{ __('High') }}</option>
                                        </x-forms.select>
                                        <x-forms.input-error for="automationTaskPriority" />
                                    </div>
                                </div>
                            @endif
                        </div>
                        <x-actions.button type="submit" class="w-full">{{ __('Create Template') }}</x-actions.button>
                    </form>
                </x-admin.panel>
            @else
                <x-admin.alert tone="info">
                    {{ __('You can view custom forms, but need manage permission to create or disable templates.') }}
                </x-admin.alert>
            @endif
        </div>
    </div>
</x-admin.page-shell>
