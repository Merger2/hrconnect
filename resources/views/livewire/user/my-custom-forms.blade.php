<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-custom-forms-title" class="user-page-surface" @if($pollingEnabled) wire:poll.visible.20s @endif>
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Forms')"
                :description="__('Submit company forms for HR, operations, visits, and internal requests.')"
                title-id="my-custom-forms-title">
                <x-slot name="icon">
                    <x-heroicon-o-clipboard-document-list class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700 dark:bg-primary-950/40 dark:text-primary-200">
                        {{ __('Available: :count', ['count' => $templates->count()]) }}
                    </span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body grid grid-cols-1 gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
        <div class="space-y-3">
            @forelse ($templates as $template)
                <button
                    type="button"
                    wire:click="selectTemplate({{ $template->id }})"
                    class="w-full rounded-[1.05rem] border p-4 text-left shadow-none transition {{ (int) $selectedTemplateId === $template->id ? 'border-primary-300 bg-primary-50 dark:border-primary-700 dark:bg-primary-950/30' : 'border-slate-200/70 bg-white/72 hover:border-primary-200 dark:border-slate-800/80 dark:bg-slate-900/60 dark:hover:border-primary-800' }}"
                >
                    <p class="font-semibold text-slate-950 dark:text-white">{{ $template->title }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __(str($template->category)->headline()->toString()) }} · {{ $template->company?->name }}</p>
                    @if ($template->description)
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $template->description }}</p>
                    @endif
                </button>
            @empty
                <div class="user-empty-state min-h-[10rem]">
                    {{ __('No forms are available for your company yet.') }}
                </div>
            @endforelse
        </div>

        <div class="space-y-4">
            @if ($selectedTemplate)
                <form wire:submit.prevent="submit" class="user-native-form p-4 sm:p-5">
                    <div class="border-b border-slate-200 pb-4 dark:border-slate-800">
                        <h2 class="text-base font-bold text-slate-950 dark:text-white">{{ $selectedTemplate->title }}</h2>
                        @if ($selectedTemplate->description)
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $selectedTemplate->description }}</p>
                        @endif
                    </div>

                    <div class="mt-4 space-y-4">
                        @foreach (($selectedTemplate->fields ?? []) as $field)
                            <div>
                                <x-forms.label :for="'custom-form-'.$field['key']" :value="$field['label'].(($field['required'] ?? false) ? ' *' : '')" class="mb-1.5 block" />
                                @if ($field['type'] === \App\Models\CustomFormTemplate::TYPE_TEXTAREA)
                                    <x-forms.textarea :id="'custom-form-'.$field['key']" wire:model.live="responseValues.{{ $field['key'] }}" rows="3" />
                                @elseif ($field['type'] === \App\Models\CustomFormTemplate::TYPE_SELECT)
                                    <x-forms.select id="custom-form-{{ $field['key'] }}" wire:model.live="responseValues.{{ $field['key'] }}" class="w-full" aria-label="{{ $field['label'] }}">
                                        <option value="">{{ __('Select') }}</option>
                                        @foreach (($field['options'] ?? []) as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </x-forms.select>
                                @else
                                    <x-forms.input :id="'custom-form-'.$field['key']" :type="$field['type']" wire:model.live="responseValues.{{ $field['key'] }}" />
                                @endif
                                <x-forms.input-error :for="$field['key']" />
                            </div>
                        @endforeach
                    </div>

                    <x-actions.button type="submit" class="mt-5 w-full justify-center">
                        {{ __('Submit Form') }}
                    </x-actions.button>
                </form>
            @else
                <div class="user-empty-state">
                    <x-heroicon-o-clipboard-document-list class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" />
                    <h2 class="mt-3 font-semibold text-slate-950 dark:text-white">{{ __('Choose a form') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Pick a form from the list to start filling it out.') }}</p>
                </div>
            @endif

            <div class="user-list-card">
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Recent Submissions') }}</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($submissions as $submission)
                        <div class="rounded-xl bg-slate-50/70 p-3 text-sm dark:bg-slate-950/35">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $submission->template?->title }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $submission->created_at?->format('d M Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No submissions yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
            </div>
        </section>
    </div>
</div>
