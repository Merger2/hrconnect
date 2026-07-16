@php
    $employee = Auth::user()->employee;
    $hasFace = $employee
        ? app(\App\Services\FaceRecognitionService::class)->hasFaceEnrolled($employee)
        : false;
@endphp

<x-layouts::app.sidebar :title="__('Absen')">
    <div x-data="clockIn({ hasFaceEnrolled: {{ $hasFace ? 'true' : 'false' }} })" class="mx-auto flex max-w-[480px] flex-col gap-5 md:max-w-3xl md:gap-6">

        {{-- CAMERA SECTION (only when face enrolled) --}}
        <template x-if="!pinRequired">
            {{-- Camera View --}}
            <section class="relative overflow-hidden rounded-2xl border border-outline-variant bg-canvas" aria-label="{{ __('Kamera Absensi') }}">
                <video x-ref="video" autoplay playsinline muted class="block w-full" style="aspect-ratio: 4/3;"></video>
                <canvas x-ref="overlay" class="absolute inset-0 h-full w-full"></canvas>

                {{-- Initial placeholder --}}
                <div x-ref="placeholder" x-show="status === 'loading-models' || status === 'opening-camera'"
                     class="absolute inset-0 z-[1] flex items-center justify-center bg-surface-container-low transition-opacity">
                    <div class="flex flex-col items-center gap-3">
                        <div class="size-8 animate-spin rounded-full border-4 border-outline-variant border-t-primary"></div>
                        <p class="text-sm font-medium text-on-surface-variant" x-text="statusMessage"></p>
                    </div>
                </div>
            </section>

            {{-- Status pill + hint --}}
            <div class="flex flex-col items-center gap-2">
                <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium" role="status" aria-live="polite"
                    :class="{
                        'bg-success/10 text-success': status === 'ready-to-capture',
                        'bg-warning/10 text-warning ring-1 ring-warning/30': ['turn-face', 'arming-liveness', 'recenter-face', 'blink', 'align-face'].includes(status),
                        'bg-error/10 text-error': status === 'error',
                        'bg-surface-container-low text-on-surface-variant': !['ready-to-capture', 'turn-face', 'arming-liveness', 'recenter-face', 'blink', 'align-face', 'error'].includes(status)
                    }">
                    <span x-show="showSpinner()" class="material-symbols-outlined animate-spin text-base">sync</span>
                    <span x-text="statusMessage"></span>
                </div>
                <p class="text-xs text-on-surface-variant" x-text="hintMessage"></p>
            </div>
        </template>

        {{-- PIN MODE (when face not enrolled) --}}
        <template x-if="pinRequired">
            <section class="relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-warning/30 bg-warning/5 p-5" x-transition>
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-warning/20 text-warning">
                        <span class="material-symbols-outlined">lock</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Face ID Belum Terdaftar') }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ __('Gunakan PIN 6 digit untuk absen hari ini. Daftarkan Face ID di Pengaturan untuk absen lebih cepat.') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-center gap-2" x-ref="pinInputs">
                        <template x-for="i in 6" :key="i">
                            <input type="password"
                                   maxlength="1"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   class="h-12 w-11 rounded-xl border border-outline-variant bg-canvas text-center text-lg font-semibold text-ink focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                   x-model="pinDigits[i - 1]"
                                   x-on:input="handlePinInput($event, i)"
                                   x-on:keydown.backspace="if (!pinDigits[i - 1] && i > 1) $refs.pinInputs.children[i - 2].focus()"
                                   :aria-label="'{{ __('Digit PIN') }} ' + i" />
                        </template>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" x-on:click="clearPin"
                                class="flex-1 rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-surface-container-low">
                            {{ __('Batal') }}
                        </button>
                        <button type="button" x-on:click="submitPinClockIn" :disabled="pin.length !== 6 || clockingIn"
                                class="flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary transition hover:bg-primary/90 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!clockingIn" class="material-symbols-outlined text-lg">login</span>
                            <span x-text="clockingIn ? '{{ __('Memproses...') }}' : '{{ __('Absen dengan PIN') }}'"></span>
                        </button>
                    </div>

                    <p class="text-center text-xs text-on-surface-variant">
                        <a href="{{ route('profile.edit') }}" class="text-primary underline underline-offset-2 hover:text-primary/80">
                            {{ __('Daftar Face ID sekarang') }}
                        </a>
                    </p>
                </div>
            </section>
        </template>

        {{-- Location & WFA Panel --}}
        <section class="relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-outline-variant bg-canvas p-4">
            <div class="pointer-events-none absolute -bottom-8 -right-8 opacity-10">
                <span class="material-symbols-outlined text-8xl text-on-surface-variant/20">map</span>
            </div>

            <div class="z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-info/10 text-info">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-ink">{{ __('Lokasi Saat Ini') }}</h3>
                        <p class="text-sm text-on-surface-variant" x-text="locationName"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 rounded-full border border-outline-variant bg-surface-container-low px-3 py-1 text-xs font-semibold uppercase">
                    <div class="size-2 rounded-full"
                        :class="!['Tidak Ada', 'Mendeteksi...'].includes(geoStatus) ? 'animate-pulse bg-success' : 'bg-on-surface-variant/30'"></div>
                    <span x-text="geoStatus" class="text-on-surface-variant"></span>
                </div>
            </div>

            <div class="z-10 h-px w-full bg-outline-variant/30"></div>

            {{-- GPS Error Recovery --}}
            <template x-if="geoError || geoPermissionDenied">
                <div class="rounded-xl border border-warning/30 bg-warning/10 p-4">
                    <p class="text-sm font-semibold text-warning">{{ __('Izin lokasi diperlukan untuk absensi') }}</p>
                    <ol class="mt-2 list-inside list-decimal space-y-1 text-sm text-on-surface-variant">
                        <li>{{ __('Klik ikon lokasi di address bar browser, lalu pilih Izinkan.') }}</li>
                        <li>{{ __('Pastikan GPS/Location diaktifkan di perangkat.') }}</li>
                        <li>{{ __('Tekan tombol Coba Lagi di bawah.') }}</li>
                    </ol>
                    <button type="button" x-on:click="retryGeo()"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary shadow-soft transition-smooth hover:bg-primary-deep">
                        <span class="material-symbols-outlined text-lg">refresh</span>
                        {{ __('Coba Lagi') }}
                    </button>
                </div>
            </template>

            <div class="z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-ink">{{ __('Mode WFA') }}</h3>
                    <p class="text-sm text-on-surface-variant">{{ __('Aktifkan untuk absen di luar area') }}</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" class="peer sr-only" x-model="wfaMode" />
                    <div class="peer h-6 w-11 rounded-full bg-surface-container-high peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:w-5 after:h-5 after:rounded-full after:border after:border-outline-variant after:bg-canvas after:transition-all"></div>
                </label>
            </div>
        </section>

        {{-- Clock In Button (only when face enrolled) --}}
        <template x-if="!pinRequired">
            <button x-on:click="doClockIn()"
                    :disabled="clockingIn || !canCapture()"
                    :class="canCapture() ? 'bg-primary hover:bg-primary/90 text-on-primary' : 'bg-surface-container-high text-on-surface-variant cursor-not-allowed'"
                    class="w-full py-4 px-6 rounded-2xl font-semibold text-base flex items-center justify-center gap-2 transition-all active:scale-[0.98]">
                <span x-show="!clockingIn" class="material-symbols-outlined">fingerprint</span>
                <span x-show="clockingIn" class="inline-block size-5 animate-spin rounded-full border-2 border-on-primary border-t-transparent"></span>
                <span x-text="clockingIn ? '{{ __('Memproses...') }}' : (status === 'ready-to-capture' ? '{{ __('Absen Sekarang') }}' : '{{ __('Verifikasi wajah...') }}')"></span>
            </button>
        </template>

        {{-- Hint for face --}}
        <template x-if="!pinRequired">
            <div x-show="status !== 'ready-to-capture' && status !== 'saving' && status !== 'error' && status !== 'loading-models' && status !== 'opening-camera'"
                 class="flex items-center justify-center gap-2 rounded-2xl border border-warning/30 bg-warning/10 px-4 py-2.5 text-sm text-warning">
                <span class="material-symbols-outlined text-lg">tips_and_updates</span>
                <span>{{ __('Posisikan wajah di dalam panduan, lalu kedipkan mata saat diminta') }}</span>
            </div>
        </template>
    </div>
</x-layouts::app.sidebar>