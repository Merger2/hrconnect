<div wire:poll.15s="refreshStatus"
     x-data="clockInAction()"
     @gps-captured.window="onGpsCaptured($event.detail)"
     @face-captured.window="onFaceCaptured($event.detail)"
     class="space-y-4">
    {{-- Loading overlay --}}
    <div x-show="$wire.isLoading"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-white/80"
         role="status"
         aria-live="polite">
        <div class="flex flex-col items-center gap-3">
            <svg class="h-10 w-10 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm font-medium text-slate-600">{{ __('Processing...') }}</p>
        </div>
    </div>

    {{-- Error toast --}}
    <div x-show="$wire.errorMessage"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm"
         role="alert">
        <div class="flex items-start gap-3">
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-red-500" />
            <p class="flex-1 text-sm font-medium text-red-800" x-text="$wire.errorMessage"></p>
            <button type="button" wire:click="dismissError" class="shrink-0 text-red-400 hover:text-red-600 transition-colors">
                <x-heroicon-o-x-mark class="h-5 w-5" />
                <span class="sr-only">{{ __('Dismiss') }}</span>
            </button>
        </div>
    </div>

    {{-- Success toast --}}
    <div x-show="$wire.successMessage"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm"
         role="status"
         aria-live="polite">
        <div class="flex items-start gap-3">
            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                <x-heroicon-o-check class="h-4 w-4" />
            </div>
            <p class="flex-1 text-sm font-medium text-emerald-800" x-text="$wire.successMessage"></p>
            <button type="button" wire:click="dismissSuccess" class="shrink-0 text-emerald-400 hover:text-emerald-600 transition-colors">
                <x-heroicon-o-x-mark class="h-5 w-5" />
                <span class="sr-only">{{ __('Dismiss') }}</span>
            </button>
        </div>
    </div>

    {{-- GPS error warning --}}
    <div x-show="gpsWarning"
         x-cloak
         x-transition
         class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-medium text-amber-700">
        <div class="flex items-center gap-2">
            <x-heroicon-o-map-pin class="h-4 w-4 shrink-0" />
            <span x-text="gpsWarning"></span>
        </div>
    </div>

    {{-- 🟢 FACE ENROLLMENT REQUIRED --}}
    <div x-show="$wire.requiresFaceEnrollment"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20">
                    <x-heroicon-o-face-smile class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h3 class="font-semibold text-white">{{ __('Face ID Required') }}</h3>
                    <p class="text-xs text-primary-100">{{ __('Register your face to enable secure attendance') }}</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-4">
            <p class="mb-4 text-sm text-slate-600">{{ __('To ensure workplace security, you must register your face before you can check in / out.') }}</p>
            <a href="{{ route('face.enrollment') }}"
               class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-[0.98]">
                <x-heroicon-o-camera class="h-5 w-5" />
                <span>{{ __('Register Face ID Now') }}</span>
            </a>
        </div>
    </div>

    {{-- 🟢 ATTENDANCE MAIN CARD --}}
    <div x-show="!$wire.requiresFaceEnrollment"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- Card header: Date + Live badge --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Attendance') }}</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900">{{ now()->translatedFormat('l, d F Y') }}</h2>
            </div>
            <div class="flex shrink-0 items-center gap-2 rounded-full bg-primary-50 px-3 py-1.5" role="status" aria-live="polite">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-primary-600"></span>
                </span>
                <span class="text-xs font-semibold text-primary-700">{{ __('Live') }}</span>
            </div>
        </div>

        {{-- Shift info --}}
        <div class="border-b border-slate-100 px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <x-heroicon-o-calendar-days class="h-4.5 w-4.5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900" x-text="shiftLabel"></p>
                    <p class="text-xs text-slate-500">
                        {{ __('Working hours') }}: <span class="font-semibold text-slate-700" x-text="workHours"></span>
                        <template x-if="shiftDuration">
                            <span><span class="text-slate-300">•</span> <span x-text="shiftDuration"></span></span>
                        </template>
                    </p>
                </div>
            </div>
        </div>

        {{-- ⏱ COUNTDOWN TIMER (shown when checked in but not out) --}}
        <div x-show="$wire.hasCheckedIn && !$wire.hasCheckedOut && shiftEndTimestamp"
             x-cloak
             x-transition
             class="border-b border-slate-100 px-5 py-3">
            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-slate-50 to-primary-50/50 px-4 py-2.5">
                <div class="flex items-center gap-2 text-sm font-medium text-slate-600">
                    <x-heroicon-o-clock class="h-4 w-4" />
                    <span>{{ __('Shift ends in') }}</span>
                </div>
                <span class="font-mono text-lg font-bold text-primary-700" x-text="formattedCountdown"></span>
            </div>
        </div>

        {{-- ⏱ OVERTIME indicator --}}
        <div x-show="$wire.hasCheckedIn && !$wire.hasCheckedOut && !shiftEndTimestamp"
             x-cloak
             x-transition
             class="border-b border-slate-100 px-5 py-3">
            <div class="rounded-xl bg-amber-50 px-4 py-3 text-center">
                <p class="text-sm font-semibold text-amber-700">{{ __('No shift end time assigned') }}</p>
                <p class="text-xs text-amber-600">{{ __('Remember to check out when you finish work.') }}</p>
            </div>
        </div>

        {{-- 🎯 Main CTA area: Clock In / Out big button + options --}}
        <div class="px-5 py-6">
            {{-- CLOCKED OUT STATE --}}
            <div x-show="!$wire.hasCheckedIn" x-cloak>
                <button type="button"
                        wire:click="startClockIn"
                        :disabled="$wire.isLoading"
                        class="group relative mx-auto flex w-40 h-40 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg transition-all hover:shadow-xl hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-4 disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="{{ __('Check In') }}">
                    <div class="flex flex-col items-center gap-1 text-white">
                        <x-heroicon-o-arrow-left-on-rectangle class="h-10 w-10 transition-transform group-hover:-translate-x-0.5" />
                        <span class="text-sm font-bold">{{ __('Check In') }}</span>
                    </div>
                    {{-- Pulse ring animation --}}
                    <span class="absolute inset-0 rounded-full bg-primary-400/20 animate-ping"></span>
                </button>

                <div class="mt-5 flex justify-center gap-3">
                    {{-- WFA option --}}
                    <button type="button"
                            wire:click="startWfaClockIn"
                            :disabled="$wire.isLoading"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:opacity-50">
                        <x-heroicon-o-home-modern class="h-4 w-4" />
                        <span>{{ __('WFA') }}</span>
                    </button>
                    {{-- GPS capture button --}}
                    <button type="button"
                            @click="captureGps()"
                            :disabled="$wire.isGpsLoading || $wire.isLoading"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50"
                            :class="gpsCaptured ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:ring-slate-400'">
                        <template x-if="gpsLoading">
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <template x-if="!gpsLoading">
                            <x-heroicon-o-map-pin class="h-4 w-4" />
                        </template>
                        <span x-text="gpsCaptured ? '{{ __('GPS Ready') }}' : '{{ __('GPS') }}'"></span>
                    </button>
                </div>
            </div>

            {{-- CLOCKED IN STATE (not clocked out) --}}
            <div x-show="$wire.hasCheckedIn && !$wire.hasCheckedOut" x-cloak>
                <div class="flex flex-col items-center gap-4">
                    {{-- Check in time display --}}
                    <div class="flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-medium text-emerald-700">
                        <x-heroicon-o-check-circle class="h-4 w-4" />
                        <span>{{ __('Checked in at') }}</span>
                        <span class="font-semibold" x-text="clockInTime"></span>
                    </div>

                    {{-- Clock Out button --}}
                    <button type="button"
                            wire:click="startClockOut"
                            :disabled="$wire.isLoading"
                            class="group relative mx-auto flex w-36 h-36 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-rose-700 shadow-lg transition-all hover:shadow-xl hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-4 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="{{ __('Check Out') }}">
                        <div class="flex flex-col items-center gap-1 text-white">
                            <x-heroicon-o-arrow-right-on-rectangle class="h-10 w-10 transition-transform group-hover:translate-x-0.5" />
                            <span class="text-sm font-bold">{{ __('Check Out') }}</span>
                        </div>
                        <span class="absolute inset-0 rounded-full bg-rose-400/20 animate-ping"></span>
                    </button>

                    {{-- GPS status --}}
                    <button type="button"
                            @click="captureGps()"
                            :disabled="gpsLoading || $wire.isLoading"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-xs font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50"
                            :class="gpsCaptured ? 'border-emerald-300 bg-emerald-50 text-emerald-700 focus:ring-emerald-400' : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50 focus:ring-slate-400'">
                        <template x-if="gpsLoading">
                            <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <template x-if="!gpsLoading">
                            <x-heroicon-o-map-pin class="h-3.5 w-3.5" />
                        </template>
                        <span x-text="gpsCaptured ? '{{ __('GPS location captured') }}' : '{{ __('Capture GPS') }}'"></span>
                        <template x-if="gpsAccuracy">
                            <span class="text-slate-400" x-text="'±' + gpsAccuracy + 'm'"></span>
                        </template>
                    </button>
                </div>
            </div>

            {{-- BOTH CHECKED IN AND OUT — DONE STATE --}}
            <div x-show="$wire.hasCheckedIn && $wire.hasCheckedOut" x-cloak>
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 shadow-md">
                        <x-heroicon-o-check class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ __('Attendance Complete!') }}</h3>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Today attendance has been fully recorded.') }}</p>
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <div>
                            <p class="text-xs text-slate-400">{{ __('Check In') }}</p>
                            <p class="font-semibold text-slate-900" x-text="clockInTime"></p>
                        </div>
                        <div class="h-8 w-px bg-slate-200"></div>
                        <div>
                            <p class="text-xs text-slate-400">{{ __('Check Out') }}</p>
                            <p class="font-semibold text-slate-900" x-text="clockOutTime"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🗺️ Location map — shows when GPS is captured (before or after check-in) --}}
    <div x-show="gpsCaptured" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <x-user.location-card
            :mapId="'clock-in-map'"
            :title="__('Lokasi Anda')"
            :latitude="$latitude"
            :longitude="$longitude"
            :branchLatitude="$branchLatitude"
            :branchLongitude="$branchLongitude"
            :branchRadius="$branchRadius"
            :branchName="$branchName"
            icon="true"
            :showRefresh="true" />
    </div>

    {{-- 🟢 WFA Clock In Modal --}}
    <div x-show="$wire.showWfaModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center"
         @click.self="$wire.set('showWfaModal', false)"
         role="dialog"
         aria-modal="true"
         aria-labelledby="wfa-modal-title">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="w-full max-w-md rounded-t-2xl bg-white p-6 shadow-2xl sm:rounded-2xl">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-600">
                    <x-heroicon-o-home-modern class="h-5 w-5" />
                </div>
                <div>
                    <h3 id="wfa-modal-title" class="text-lg font-bold text-slate-900">{{ __('Work From Anywhere') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('Please provide the reason for WFA today.') }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="wfa-note" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Reason / Location') }}</label>
                    <textarea id="wfa-note"
                              x-model="$wire.wfaNote"
                              rows="4"
                              maxlength="500"
                              class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                              placeholder="{{ __('e.g. Working from home due to...') }}"></textarea>
                    <p class="mt-1 text-right text-xs text-slate-400" x-text="$wire.wfaNote.length + '/500'"></p>
                    @error('wfaNote') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- ✅ Face verified badge (shown when face recognition was used) --}}
                <div x-show="$wire.wfaFaceMode" x-cloak
                     class="flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                        <x-heroicon-o-check class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-emerald-800">{{ __('Face Verified') }}</p>
                        <p class="text-xs text-emerald-600">{{ __('Identity confirmed. Just fill in the reason.') }}</p>
                    </div>
                </div>

                {{-- 🔒 PIN fallback (shown when face is not enrolled) --}}
                <div x-show="!$wire.wfaFaceMode" x-cloak>
                    <label for="wfa-pin" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('PIN Verification (fallback)') }}</label>
                    <input id="wfa-pin"
                           type="password"
                           x-model="$wire.wfaPin"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           maxlength="8"
                           autocomplete="off"
                           class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-center text-lg font-bold tracking-[0.3em] transition-colors placeholder:text-slate-300 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                           placeholder="• • • • • •">
                    @error('wfaPin') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            @click="$wire.set('showWfaModal', false)"
                            class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button"
                            wire:click="submitWfaClockIn"
                            :disabled="$wire.isLoading || (!$wire.wfaFaceMode && $wire.wfaPin.length < 4)"
                            class="flex-1 rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50">
                        <template x-if="!$wire.isLoading">
                            <span>{{ __('Check In (WFA)') }}</span>
                        </template>
                        <template x-if="$wire.isLoading">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ __('Verifying...') }}</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 🟢 PIN Verification Modal --}}
    <div x-show="$wire.showPinModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center"
         @click.self="$wire.set('showPinModal', false)"
         role="dialog"
         aria-modal="true"
         aria-labelledby="pin-modal-title">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="w-full max-w-sm rounded-t-2xl bg-white p-6 shadow-2xl sm:rounded-2xl">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                    <x-heroicon-o-lock-closed class="h-7 w-7 text-slate-600" />
                </div>
                <h3 id="pin-modal-title" class="text-lg font-bold text-slate-900">
                    <span x-text="$wire.pinAction === 'clock_in' ? '{{ __('Verify Check In') }}' : '{{ __('Verify Check Out') }}'"></span>
                </h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Enter your PIN to confirm identity.') }}</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="pin-input" class="sr-only">{{ __('PIN') }}</label>
                    <input id="pin-input"
                           type="password"
                           x-model="$wire.pin"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           maxlength="8"
                           autocomplete="off"
                           class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-center text-2xl font-bold tracking-[0.5em] transition-colors placeholder:text-slate-300 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                           placeholder="• • • • • •">
                    @error('pin') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            @click="$wire.set('showPinModal', false); $wire.set('pin', '')"
                            class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button"
                            x-on:click="$wire.pinAction === 'clock_in' ? $wire.doClockInWithPin() : $wire.doClockOutWithPin()"
                            :disabled="$wire.isLoading || $wire.pin.length < 4"
                            class="flex-1 rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50">
                        <template x-if="!$wire.isLoading">
                            <span>{{ __('Verify') }}</span>
                        </template>
                        <template x-if="$wire.isLoading">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ __('Verifying...') }}</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ⚡ Face capture + timeout hidden triggers --}}
    <div x-data="{}"
         x-init="
            this.$wire.$watch('isLoading', val => {
                if (val === false) scrollTo({ top: 0, behavior: 'smooth' });
            });
         "
         @trigger-face-capture.window="
            $nextTick(() => {
                // Dispatch to the FaceEnrollment component on the page
                window.dispatchEvent(new CustomEvent('start-face-verification', {
                    detail: { action: $event.detail.action }
                }));
            });
         "
         @face-verification-timeout.window="
            if ($event.detail.timeoutMs) {
                let timer = setTimeout(() => {
                    const el = document.querySelector('[x-data^=\'clockInAction\']')?.__x;
                    if (el && el.$wire.isLoading) {
                        const action = $event.detail.action || 'clock_in';
                        if (action === 'wfa') {
                            // WFA timeout → show WFA modal with PIN fallback
                            el.$wire.set('isLoading', false);
                            el.$wire.set('wfaFaceMode', false);
                            el.$wire.set('wfaPin', '');
                            el.$wire.set('showWfaModal', true);
                            el.$wire.set('errorMessage', '{{ __('Face verification did not respond. Use PIN instead.') }}');
                        } else {
                            // clock_in / clock_out timeout → show PIN modal
                            el.$wire.set('isLoading', false);
                            el.$wire.set('pinAction', action);
                            el.$wire.set('pin', '');
                            el.$wire.set('showPinModal', true);
                            el.$wire.set('errorMessage', '{{ __('Face verification did not respond. Use PIN instead.') }}');
                        }
                    }
                }, $event.detail.timeoutMs);
                // Store the timer reference for cleanup
                window.__faceTimeoutTimer = timer;
            }
         "
         aria-hidden="true"
         class="hidden">
    </div>

    {{-- Refresh status on page visibility change --}}
    <div x-data="{
        init() {
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.$wire.refreshStatus();
                }
            });
        }
    }" aria-hidden="true" class="hidden"></div>

    {{-- Clear face timeout on successful face capture --}}
    <div x-data="{}"
         @face-captured.window="
            if (window.__faceTimeoutTimer) {
                clearTimeout(window.__faceTimeoutTimer);
                window.__faceTimeoutTimer = null;
            }
         "
         aria-hidden="true"
         class="hidden"></div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('clockInAction', () => ({
                // --- GPS State ---
                gpsLoading: false,
                gpsCaptured: false,
                gpsAccuracy: null,
                gpsWarning: '',

                // --- Shift timer state (computed from $wire) ---
                shiftEndTimestamp: null,
                hasApprovedOvertime: false,
                countdownTimer: null,
                countdownSeconds: 0,
                faceTimeoutTimer: null,

                // --- Init: watch for reactive updates ---
                init() {
                    // 1. Compute initial shift end from $wire values loaded in mount()
                    this.updateShiftEnd();

                    // 2. Watch attendance changes for reactive countdown
                    this.$wire.$watch('attendance', () => this.updateShiftEnd());
                    this.$wire.$watch('todayShiftSummary', () => this.updateShiftEnd());
                    this.$wire.$watch('hasApprovedOvertime', (val) => {
                        this.hasApprovedOvertime = val;
                    });

                    // 3. GPS auto-capture
                    if (navigator.geolocation) {
                        this.captureGps();
                    }

                    // 4. Start countdown ticker (shiftEndTimestamp already computed)
                    this.startCountdown();
                },

                updateShiftEnd() {
                    const attendance = this.$wire.attendance;
                    const summary = this.$wire.todayShiftSummary;

                    // Try attendance shift first
                    let endTime = attendance?.shift?.end_time;  
                    if (!endTime) {
                        // Fallback to summary
                        endTime = summary?.end_time;
                    }

                    if (endTime) {
                        // Build today's end timestamp
                        const today = new Date();
                        const dateStr = today.getFullYear() + '-' +
                            String(today.getMonth() + 1).padStart(2, '0') + '-' +
                            String(today.getDate()).padStart(2, '0');
                        this.shiftEndTimestamp = dateStr + ' ' + endTime;
                    } else {
                        this.shiftEndTimestamp = null;
                    }

                    this.hasApprovedOvertime = this.$wire.hasApprovedOvertime;
                },

                // --- Computed shift labels ---
                get shiftLabel() {
                    if (this.$wire.todayShiftSummary?.is_off) {
                        return '{{ __('Off Day') }}';
                    }
                    return this.$wire.todayShiftSummary?.name || '{{ __('No shift assigned') }}';
                },
                get workHours() {
                    const start = this.$wire.todayShiftSummary?.start;
                    const end = this.$wire.todayShiftSummary?.end;
                    if (start && end) return start + ' - ' + end;
                    return '{{ __('Flexible') }}';
                },
                get shiftDuration() {
                    return this.$wire.todayShiftSummary?.duration || '';
                },

                // --- Clock in/out times from attendance ---
                get clockInTime() {
                    // Prefer direct string property (fast, no deferred load)
                    if (this.$wire.clockInTime) return this.$wire.clockInTime;
                    // Fallback to deferred model
                    return this.$wire.attendance?.clock_in
                        ? new Date(this.$wire.attendance.clock_in).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                        : '--:--';
                },
                get clockOutTime() {
                    if (this.$wire.clockOutTime) return this.$wire.clockOutTime;
                    return this.$wire.attendance?.clock_out
                        ? new Date(this.$wire.attendance.clock_out).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                        : '--:--';
                },

                // --- Countdown formatted ---
                get formattedCountdown() {
                    if (!this.shiftEndTimestamp) return '--:--:--';
                    if (this.countdownSeconds <= 0) {
                        return this.hasApprovedOvertime ? '{{ __('Overtime') }}' : '{{ __('Clock Out Now') }}';
                    }
                    const h = Math.floor(this.countdownSeconds / 3600);
                    const m = Math.floor((this.countdownSeconds % 3600) / 60);
                    const s = this.countdownSeconds % 60;
                    return String(h).padStart(2, '0') + ':' +
                           String(m).padStart(2, '0') + ':' +
                           String(s).padStart(2, '0');
                },

                // --- GPS Capture ---
                captureGps() {
                    if (this.gpsLoading) return;
                    this.gpsLoading = true;
                    this.gpsWarning = '';

                    if (!navigator.geolocation) {
                        this.gpsWarning = '{{ __('Geolocation is not available in this browser.') }}';
                        this.gpsLoading = false;
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            const acc = Math.round(position.coords.accuracy);

                            this.gpsCaptured = true;
                            this.gpsAccuracy = acc;
                            this.gpsLoading = false;

                            // Send to Livewire component
                            this.$wire.setGps(lat, lng, acc);

                            // Dispatch event so location-card can update reactively
                            window.dispatchEvent(new CustomEvent('gps-coordinates-updated', {
                                detail: { latitude: lat, longitude: lng, accuracy: acc }
                            }));
                        },
                        (error) => {
                            console.warn('GPS error:', error);
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    this.gpsWarning = '{{ __('GPS permission denied. Please enable location access.') }}';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    this.gpsWarning = '{{ __('GPS position unavailable. Try again.') }}';
                                    break;
                                case error.TIMEOUT:
                                    this.gpsWarning = '{{ __('GPS request timed out. Please try again.') }}';
                                    break;
                                default:
                                    this.gpsWarning = '{{ __('Could not get GPS location.') }}';
                            }
                            this.gpsLoading = false;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 30000
                        }
                    );
                },

                // --- Handle GPS from dispatched event (e.g., from Capacitor) ---
                onGpsCaptured(detail) {
                    if (detail?.latitude && detail?.longitude) {
                        this.gpsCaptured = true;
                        this.gpsAccuracy = detail.accuracy || null;
                        this.gpsLoading = false;
                        this.$wire.setGps(detail.latitude, detail.longitude, detail.accuracy);

                        // Dispatch event so location-card can update reactively
                        window.dispatchEvent(new CustomEvent('gps-coordinates-updated', {
                            detail: { latitude: detail.latitude, longitude: detail.longitude, accuracy: detail.accuracy }
                        }));
                    }
                },

                // --- Handle face captured event from FaceEnrollment ---
                onFaceCaptured(detail) {
                    if (detail?.descriptor && detail?.action) {
                        // Clear face timeout since we got a response
                        if (this.faceTimeoutTimer) {
                            clearTimeout(this.faceTimeoutTimer);
                            this.faceTimeoutTimer = null;
                        }

                        if (detail.action === 'clock_in') {
                            this.$wire.doClockInWithFace(detail.descriptor);
                        } else if (detail.action === 'clock_out') {
                            this.$wire.doClockOutWithFace(detail.descriptor);
                        } else if (detail.action === 'wfa') {
                            this.$wire.doWfaClockInWithFace(detail.descriptor);
                        }
                    }
                },

                // --- Countdown timer logic ---
                startCountdown() {
                    this.updateCountdown();
                    this.countdownTimer = setInterval(() => this.updateCountdown(), 1000);
                },

                updateCountdown() {
                    if (!this.shiftEndTimestamp) return;
                    const now = new Date().getTime();
                    const end = new Date(this.shiftEndTimestamp).getTime();
                    this.countdownSeconds = Math.max(0, Math.floor((end - now) / 1000));
                },

                // Clean up on destroy
                destroy() {
                    if (this.countdownTimer) clearInterval(this.countdownTimer);
                    if (window.__faceTimeoutTimer) {
                        clearTimeout(window.__faceTimeoutTimer);
                        window.__faceTimeoutTimer = null;
                    }
                }
            }));
        });
    </script>
    @endpush
</div>
