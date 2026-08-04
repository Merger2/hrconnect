<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section class="user-page-surface" aria-labelledby="wfh-request-title" @unless($showCreateModal) wire:poll.visible.20s @endunless>
            <x-user.page-header
                :back-href="route('home')"
                :title="__('WFH Request')"
                :description="__('Request work-from-home approval with date, time, location, and reason.')"
                title-id="wfh-request-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-home-modern class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <button type="button" wire:click="create" class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-none transition hover:bg-primary-700" aria-label="{{ __('New Request') }}">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Request') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body bg-gray-50/50">
                <x-feedback.alert-messages />

                <div class="wfh-request-summary" aria-label="{{ __('WFH request summary') }}">
                    <div class="wfh-request-summary__icon">
                        <x-heroicon-o-home-modern class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="wfh-request-summary__label">{{ __('Remote Work') }}</p>
                        <p class="wfh-request-summary__copy">{{ __('Submit WFH with date, optional hours, location, and reason in one flow.') }}</p>
                    </div>
                </div>

                <div class="wfh-request-list">
                        @forelse ($requests as $request)
                            @php
                                $tone = match ($request->status) {
                                    \App\Models\WorkFromHomeRequest::STATUS_APPROVED => 'wfh-request-item__status--approved',
                                    \App\Models\WorkFromHomeRequest::STATUS_REJECTED => 'wfh-request-item__status--rejected',
                                    default => 'wfh-request-item__status--pending',
                                };
                            @endphp
                            <article class="wfh-request-item">
                                <div class="wfh-request-item__top">
                                    <div class="wfh-request-item__date">
                                        <span class="wfh-request-item__day">{{ $request->date?->translatedFormat('d') }}</span>
                                        <span class="wfh-request-item__month">{{ $request->date?->translatedFormat('M') }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="wfh-request-item__title">{{ $request->date?->translatedFormat('l') }}</h3>
                                                <div class="wfh-request-item__meta">
                                                    <x-heroicon-o-clock class="h-4 w-4" />
                                                    <span>
                                                        @if ($request->start_time && $request->end_time)
                                                            {{ $request->start_time }} - {{ $request->end_time }}
                                                        @else
                                                            {{ __('Full-day request') }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="wfh-request-item__status {{ $tone }}">
                                                {{ __(str($request->status)->headline()->toString()) }}
                                            </span>
                                        </div>

                                        @if ($request->location_address)
                                            <p class="wfh-request-item__line">
                                                <x-heroicon-o-map-pin class="h-4 w-4" />
                                                <span>{{ $request->location_address }}</span>
                                            </p>
                                        @endif

                                        <p class="wfh-request-item__reason">{{ $request->reason }}</p>
                                    </div>
                                </div>

                                @if ($request->reviewer)
                                    <p class="wfh-request-item__review">
                                        {{ __('Reviewed by :name', ['name' => $request->reviewer->name]) }}
                                        @if ($request->review_note)
                                            · {{ $request->review_note }}
                                        @endif
                                    </p>
                                @endif
                            </article>
                        @empty
                            <div class="user-empty-state">
                                <div class="user-empty-state__icon">
                                    <x-heroicon-o-home-modern class="h-10 w-10" />
                                </div>
                                <h3 class="user-empty-state__title">{{ __('No WFH requests yet') }}</h3>
                                <p class="user-empty-state__copy">{{ __('Your submitted work-from-home requests will appear here.') }}</p>
                            </div>
                        @endforelse

                        @if ($requests->hasPages())
                            <div class="wfh-request-pagination">
                                {{ $requests->links() }}
                            </div>
                        @endif
                </div>
            </div>
        </section>

        <x-overlays.modal wire:model.live="showCreateModal" maxWidth="lg" onclose="$wire.close()">
            <form wire:submit.prevent="submit" class="user-ui wfh-request-modal" aria-labelledby="wfh-request-modal-title">
                <div class="wfh-request-modal__header">
                    <div class="min-w-0">
                        <p class="wfh-request-modal__eyebrow">{{ __('Remote Work') }}</p>
                        <h2 id="wfh-request-modal-title" class="wfh-request-modal__title">{{ __('WFH Request') }}</h2>
                    </div>
                    <button type="button" wire:click="close" class="wfh-request-modal__close" aria-label="{{ __('Close') }}" wire:loading.attr="disabled">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="wfh-request-modal__body">
                    <div class="wfh-request-step">
                        <span class="wfh-request-step__number">1</span>
                        <div>
                            <p class="wfh-request-step__title">{{ __('Choose date and hours') }}</p>
                            <p class="wfh-request-step__copy">{{ __('Leave start and end blank for a full-day WFH request.') }}</p>
                        </div>
                    </div>

                    <div class="wfh-request-form__fields">
                        <x-user.native-date-field
                            id="wfh-date"
                            :label="__('Date')"
                            model="date"
                            error="date"
                            :min="now()->toDateString()"
                        />

                        <div class="grid grid-cols-2 gap-2.5">
                            <x-user.native-date-field
                                id="wfh-start"
                                :label="__('Start')"
                                model="startTime"
                                error="startTime"
                                type="time"
                            />
                            <x-user.native-date-field
                                id="wfh-end"
                                :label="__('End')"
                                model="endTime"
                                error="endTime"
                                type="time"
                            />
                        </div>

                        <div class="wfh-request-step">
                            <span class="wfh-request-step__number">2</span>
                            <div>
                                <p class="wfh-request-step__title">{{ __('Add work context') }}</p>
                                <p class="wfh-request-step__copy">{{ __('Tell the reviewer where you will work and why WFH is needed.') }}</p>
                            </div>
                        </div>

                        <x-user.native-text-field
                            id="wfh-location"
                            :label="__('Location')"
                            model="locationAddress"
                            error="locationAddress"
                            icon="heroicon-o-map-pin"
                            placeholder="{{ __('Home address or work location') }}"
                        />

                        <x-user.native-textarea-field
                            id="wfh-reason"
                            :label="__('Reason')"
                            model="reason"
                            error="reason"
                            placeholder="{{ __('Explain why you need to work from home...') }}"
                        />
                    </div>

                </div>

                <div class="wfh-request-modal__footer">
                    <button type="submit" class="wfh-submit-button" aria-label="{{ __('Submit WFH Request') }}" wire:loading.attr="disabled">
                        <x-heroicon-m-paper-airplane class="h-5 w-5" />
                        <span>{{ __('Submit WFH Request') }}</span>
                    </button>
                </div>
            </form>
        </x-overlays.modal>
    </div>
</div>
