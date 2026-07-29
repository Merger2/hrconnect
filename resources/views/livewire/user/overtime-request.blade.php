<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="overtime-request-title" class="user-page-surface relative" @unless($showModal) wire:poll.visible.20s @endunless>
            <x-user.page-header
                :back-href="!$showModal ? route('home') : null"
                :title="$showModal ? __('New Request') : __('Overtime Request')"
                title-id="overtime-request-title"
                class="border-b-0">
                <x-slot name="icon">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-sky-50 text-indigo-700 ring-1 ring-inset ring-indigo-100 shadow-sm dark:from-indigo-900/30 dark:via-gray-800 dark:to-sky-900/20 dark:text-indigo-300 dark:ring-indigo-800/60">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </div>
                </x-slot>
                <x-slot name="actions">
                    @if($showModal)
                        <button wire:click="close" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                            <span>{{ __('Back') }}</span>
                        </button>
                    @else
                        <button wire:click="create" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                            <x-heroicon-o-plus class="h-5 w-5" />
                            <span>{{ __('New Request') }}</span>
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if($showModal)
                    {{-- Create Form --}}
                    <div class="user-native-form mx-auto max-w-2xl p-4 sm:p-5">
                        <form wire:submit.prevent="store" class="space-y-4">
                            
                            {{-- Date --}}
                            <x-user.native-date-field
                                id="date"
                                :label="__('Overtime Date')"
                                model="date"
                                error="date"
                                modifier="default"
                            />

                            {{-- Time Range --}}
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-user.native-date-field
                                    id="start_time"
                                    :label="__('Start Time')"
                                    model="start_time"
                                    error="start_time"
                                    type="time"
                                    modifier="default"
                                />
                                <x-user.native-date-field
                                    id="end_time"
                                    :label="__('End Time')"
                                    model="end_time"
                                    error="end_time"
                                    type="time"
                                    modifier="default"
                                />
                            </div>

                            {{-- Reason --}}
                            <x-user.native-textarea-field
                                id="reason"
                                :label="__('Reason')"
                                model="reason"
                                error="reason"
                                rows="3"
                                modifier="default"
                                placeholder="{{ __('e.g. Project Deadline') }}"
                            />

                            <div class="flex flex-col-reverse items-stretch gap-2 border-t border-gray-100 pt-3 dark:border-gray-700 sm:flex-row sm:justify-end">
                                <x-actions.secondary-button wire:click="close" wire:loading.attr="disabled">
                                    {{ __('Cancel') }}
                                </x-actions.secondary-button>

                                <x-actions.button wire:loading.attr="disabled">
                                    {{ __('Submit Request') }}
                                </x-actions.button>
                            </div>
                        </form>
                    </div>

                @else
                    {{-- History List --}}
                    @if($overtimes->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-clock class="h-10 w-10" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No Overtime Requests') }}</h3>
                            <p class="user-empty-state__copy">{{ __('You haven\'t submitted any overtime requests yet.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($overtimes as $overtime)
                                <article class="user-list-card">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                            <x-heroicon-o-clock class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold capitalize text-gray-900 dark:text-white">
                                                {{ $overtime->date->format('d M Y') }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">
                                                {{ $overtime->start_time->format('H:i') }} - {{ $overtime->end_time->format('H:i') }}
                                                <span class="mx-1">•</span>
                                                {{ $overtime->duration_text }}
                                            </p>
                                            <p class="sr-only">{{ $overtime->reason }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                         <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($overtime->status === 'approved') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400
                                            @elseif($overtime->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400 @endif">
                                            {{ ucfirst($overtime->status) }}
                                        </span>
                                    </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            {{ $overtimes->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
