<div class="mx-auto flex max-w-[480px] flex-col gap-5 md:max-w-3xl md:gap-6"
    x-data="faceEnrollment()" x-init="init()">

    <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-xl bg-surface-container text-on-surface-variant">
            <span class="material-symbols-outlined">face</span>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-ink">{{ __('Registrasi Wajah') }}</h2>
            <p class="text-sm text-on-surface-variant">{{ __('Daftarkan wajah Anda untuk verifikasi absensi') }}</p>
        </div>
    </div>

    {{-- Enrolled state — show success + actions --}}
    <template x-if="$wire.isEnrolled && !$wire.isCapturing">
        <div class="flex flex-col items-center gap-5 rounded-2xl border border-outline-variant bg-surface-container-low p-8 text-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-success/10">
                <span class="material-symbols-outlined text-4xl text-success">check_circle</span>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-on-surface-variant">{{ __('Face ID') }}</p>
                <h3 class="text-xl font-semibold text-ink">{{ __('Face ID Aktif') }}</h3>
                <p class="mt-1 text-sm text-on-surface-variant">{{ __('Wajah Anda sudah terdaftar untuk verifikasi absensi.') }}</p>
            </div>
            <div class="flex w-full gap-3">
                <button wire:click="startCapture"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ink/90">
                    <span class="material-symbols-outlined text-lg">sync</span>
                    <span>{{ __('Perbarui Face ID') }}</span>
                </button>
                <button wire:click="removeFace"
                    wire:confirm="{{ __('Yakin ingin menghapus Face ID?') }}"
                    class="flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-error transition hover:bg-error/5">
                    <span class="material-symbols-outlined text-lg">delete</span>
                    <span>{{ __('Hapus') }}</span>
                </button>
            </div>
        </div>
    </template>

    {{-- Capture state — camera + liveness guide --}}
    <template x-if="!$wire.isEnrolled || $wire.isCapturing">
        <div class="flex flex-col gap-5">
            {{-- Guide copy --}}
            <div class="flex items-start gap-4 rounded-xl border border-outline-variant bg-surface-container-low p-4">
                <div class="hidden sm:flex shrink-0 flex-col items-center gap-1.5 pt-0.5">
                    <span class="flex size-7 items-center justify-center rounded-full text-xs font-bold"
                        :class="['turn-face', 'turn-opposite-face', 'arming-liveness', 'recenter-face'].includes(status) ? 'bg-warning/20 text-warning' : 'bg-ink/10 text-ink'">1</span>
                    <span class="flex size-7 items-center justify-center rounded-full text-xs font-bold"
                        :class="status === 'ready-to-capture' || status === 'saving' ? 'bg-success/20 text-success' : 'bg-ink/10 text-ink'">2</span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-ink" x-text="guideTitle()"></h3>
                    <p class="mt-0.5 text-sm text-on-surface-variant" x-text="guideInstruction()"></p>
                </div>
            </div>

            {{-- Camera view --}}
            <div class="relative overflow-hidden rounded-2xl bg-surface-dim" aria-label="{{ __('Kamera Face ID') }}">
                <video x-ref="video" autoplay playsinline muted class="block w-full" style="aspect-ratio: 4/3;"></video>
                <canvas x-ref="overlay" class="absolute inset-0 h-full w-full"></canvas>
            </div>

            {{-- Status pill + hint --}}
            <div class="flex flex-col items-center gap-2">
                <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium" role="status" aria-live="polite"
                    :class="{
                        'bg-success/10 text-success': status === 'ready-to-capture',
                        'bg-warning/10 text-warning ring-1 ring-warning/30': ['turn-face', 'turn-opposite-face', 'arming-liveness', 'recenter-face'].includes(status),
                        'bg-error/10 text-error': status === 'error',
                        'bg-surface-container text-on-surface-variant': !['ready-to-capture', 'turn-face', 'turn-opposite-face', 'arming-liveness', 'recenter-face', 'error'].includes(status)
                    }">
                    <span x-show="showSpinner()" class="material-symbols-outlined animate-spin text-base">sync</span>
                    <span x-text="statusMessage"></span>
                </div>
                <p class="text-xs text-on-surface-variant" x-text="hintMessage"></p>
            </div>

            {{-- Action buttons --}}
            <div class="flex gap-3">
                <template x-if="$wire.isEnrolled">
                    <button wire:click="cancelCapture"
                        class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition hover:bg-surface-container">
                        {{ __('Batal') }}
                    </button>
                </template>

                <button @click="capture({ manual: true })" :disabled="!canCapture()"
                    :class="canCapture() ? 'bg-ink text-white hover:bg-ink/90' : 'bg-surface-container text-on-surface-variant cursor-not-allowed'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition">
                    <span class="material-symbols-outlined text-lg">photo_camera</span>
                    <span x-text="buttonLabel()"></span>
                </button>
            </div>
        </div>
        </div>
        </template>
        </div>
