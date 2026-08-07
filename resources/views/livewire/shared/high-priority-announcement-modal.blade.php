@php($announcementPollInterval = \App\Support\AnnouncementRefresh::pollInterval())

<div @if (\App\Support\AnnouncementRefresh::shouldPoll()) wire:poll.{{ $announcementPollInterval }}="syncAnnouncementState" @endif>
    @if ($announcement)
        <div
            x-data="{ show: @entangle('showModal'), acknowledged: @entangle('hasReadAndUnderstood').live }"
            x-show="show"
            x-trap.inert.noscroll="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[70] overflow-y-auto px-4 py-4 sm:px-5"
            style="display: none;"
        >
            <div class="fixed inset-0 bg-slate-900/70 transition-opacity"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl ring-1 ring-slate-900/5 transition-all sm:my-6 sm:w-full sm:max-w-2xl"
                        @click.away="!@js(($announcement->modal_behavior ?? 'acknowledge') === 'acknowledge') && $wire.dismiss()"
                    >
                        <div class="relative overflow-hidden bg-white px-4 pb-3 pt-4 sm:px-5">
                            <div class="relative flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 ring-1 ring-rose-100/50">
                                    <x-heroicon-o-document-text class="h-5 w-5 text-rose-600" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            {{ __('Important Policy') }}
                                        </span>
                                        <span class="text-xs font-medium text-slate-500">
                                            {{ ($announcement->published_at?->translatedFormat('d F Y')) ?? ($announcement->created_at?->translatedFormat('d F Y') ?? '') }}
                                        </span>
                                    </div>
                                    <h2 class="mt-2 text-lg font-semibold leading-tight tracking-tight text-slate-900 sm:text-xl">
                                        {{ $announcement->title }}
                                    </h2>
                                    <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                        <x-heroicon-m-user class="h-4 w-4" />
                                        <span>{{ __('Published by') }} <span class="font-medium text-slate-700">{{ $announcement->creator?->name ?? 'System' }}</span></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="relative max-h-[45dvh] overflow-y-auto border-y border-slate-200 bg-slate-50 px-4 py-4 sm:px-5">
                            <div class="prose prose-slate prose-sm max-w-none">
                                {!! nl2br(e($announcement->content)) !!}
                            </div>
                        </div>

                        <div class="sticky bottom-0 z-20 border-t border-slate-200/80 bg-white px-4 py-4 sm:px-5">
                            @if (($announcement->modal_behavior ?? 'acknowledge') === 'acknowledge')
                                <div class="mb-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    <x-heroicon-m-information-circle class="h-4 w-4 shrink-0 text-amber-600" />
                                    <p>{{ __('Acknowledge') }}</p>
                                </div>

                                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                    <label class="group relative flex cursor-pointer items-center gap-3">
                                        <div class="relative flex items-center justify-center">
                                            <input type="checkbox" x-model="acknowledged" x-on:change="$wire.set('hasReadAndUnderstood', acknowledged)" class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border-2 border-slate-300 bg-white transition-all checked:border-rose-600 checked:bg-rose-600 hover:border-rose-500">
                                            <x-heroicon-m-check class="pointer-events-none absolute h-4 w-4 text-white opacity-0 transition-opacity peer-checked:opacity-100" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700 transition-colors group-hover:text-slate-900">
                                            {{ __('I have read, understood, and agree to this policy.') }}
                                        </span>
                                    </label>

                                    <x-actions.button type="button" wire:click="dismiss(true)" variant="primary"
                                        wire:loading.attr="disabled"
                                        x-bind:disabled="!acknowledged"
                                        class="w-full px-6 py-2.5 font-semibold shadow-md transition-all disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                    >
                                        {{ __('Acknowledge & Continue') }} <x-heroicon-m-arrow-right class="ml-2 h-4 w-4" />
                                    </x-actions.button>
                                </div>
                            @else
                                <div class="flex items-center justify-end">
                                    <x-actions.button type="button" wire:click="dismiss" variant="primary" class="w-full px-6 py-2.5 sm:w-auto">
                                        {{ __('Close Document') }}
                                    </x-actions.button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

