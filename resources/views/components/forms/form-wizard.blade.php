@props([
    'steps' => [],
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'w-full '.$class]) }}>
    <nav class="mb-6 overflow-x-auto pb-2" role="navigation" aria-label="Progress langkah form">
        <ol class="flex min-w-max items-center gap-3 md:gap-4">
            @foreach ($steps as $index => $step)
                <li class="flex items-center gap-3 md:gap-4" aria-label="Step {{ $index + 1 }} {{ $step['title'] ?? '' }}">
                    <button
                        type="button"
                        class="group flex items-center gap-3 rounded-2xl px-1 py-1 text-left outline-none transition duration-[var(--motion-duration-normal)] ease-[var(--motion-easing-standard)] focus-visible:ring-2 focus-visible:ring-primary/40"
                        @click="goToStep({{ $index }})"
                        :aria-current="currentStep === {{ $index }} ? 'step' : null"
                        :disabled="{{ $index }} > currentStep && !canJumpTo({{ $index }})"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-full border text-sm font-semibold transition duration-[var(--motion-duration-normal)] ease-[var(--motion-easing-standard)] md:size-11"
                            :class="stepCircleClass({{ $index }})"
                        >
                            <span x-show="currentStep > {{ $index }}" aria-hidden="true">✓</span>
                            <span x-show="currentStep <= {{ $index }}" x-text="{{ $index }} + 1"></span>
                        </span>
                        <span
                            class="hidden min-w-0 flex-col sm:flex"
                            :class="stepLabelClass({{ $index }})"
                        >
                            <span class="text-sm font-semibold" x-text="steps[{{ $index }}]?.title"></span>
                            <span class="text-xs text-on-surface-variant" x-text="steps[{{ $index }}]?.subtitle || ''"></span>
                        </span>
                    </button>

                    @if (! $loop->last)
                        <span
                            class="hidden h-px w-10 shrink-0 sm:block md:w-14"
                            :class="connectorClass({{ $index }})"
                            aria-hidden="true"
                        ></span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div
        class="relative overflow-hidden rounded-[1.25rem] border border-outline-variant/60 bg-canvas shadow-soft"
        x-ref="panelContainer"
    >
        {{ $slot }}
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-on-surface-variant" x-show="steps?.length">
            <span x-text="'Langkah ' + (currentStep + 1) + ' dari ' + steps.length"></span>
        </div>

        <div class="flex items-center gap-3 sm:ml-auto">
            <x-button
                type="button"
                variant="secondary"
                class="hidden sm:inline-flex"
                x-bind:class="isFirstStep ? 'opacity-0 pointer-events-none' : ''"
                x-bind:disabled="isFirstStep"
                x-on:click="prev()"
            >
                <span class="material-symbols-outlined text-base">arrow_back</span>
                {{ __('Kembali') }}
            </x-button>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm font-semibold text-ink transition duration-[var(--motion-duration-normal)] ease-[var(--motion-easing-standard)] hover:bg-surface-container focus-visible:ring-2 focus-visible:ring-primary/40 disabled:cursor-not-allowed disabled:opacity-50 sm:hidden"
                :disabled="isFirstStep"
                @click="prev()"
            >
                <span class="material-symbols-outlined text-base">arrow_back</span>
                {{ __('Kembali') }}
            </button>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition duration-[var(--motion-duration-normal)] ease-[var(--motion-easing-standard)] hover:shadow-soft focus-visible:ring-2 focus-visible:ring-primary/40 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="isSubmitting || !validateCurrentStep()"
                @click="isLastStep ? submit() : next()"
            >
                <span x-text="isLastStep ? submitLabel : nextLabel"></span>
                <span class="material-symbols-outlined text-base" x-text="isLastStep ? 'send' : 'arrow_forward'"></span>
            </button>
        </div>
    </div>
</div>
