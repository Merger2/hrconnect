<div
    x-data="toastContainer()"
    x-init="
        // Listen for toast events from anywhere
        window.addEventListener('toast', (e) => this.add(e.detail));
        // Restore persisted toasts on page load
        this.restore();
    "
    class="fixed z-[var(--toast-z-index,9999)] flex flex-col gap-[var(--toast-gap,8px)] p-4 pointer-events-none"
    :class="{
        'inset-0 items-end justify-end': position === 'top-right',
        'inset-0 items-start justify-end': position === 'top-left',
        'inset-0 items-end justify-start': position === 'bottom-right',
        'inset-0 items-start justify-start': position === 'bottom-left',
        'inset-0 items-center justify-center': position === 'top-center' || position === 'bottom-center',
    }"
    role="region"
    aria-live="polite"
    aria-label="Notifications"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition-toast-enter"
            x-transition:enter-start="opacity-0 translate-x-4 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition-toast-exit"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-4 scale-95"
            class="pointer-events-auto w-full max-w-[var(--toast-max-width,90vw)] min-w-[320px]"
            :class="toast.typeClasses"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            @mouseenter="pauseTimer(toast.id)"
            @mouseleave="resumeTimer(toast.id)"
        >
            <div class="flex items-start gap-3 p-4 rounded-xl border shadow-lg ring-1">
                <!-- Icon -->
                <div class="flex-shrink-0 size-5 mt-0.5" :class="toast.iconColor">
                    <span class="material-symbols-outlined size-5" :class="toast.iconColor" aria-hidden="true" x-text="toast.icon"></span>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <template x-if="toast.title">
                        <p class="text-sm font-semibold text-ink" x-text="toast.title"></p>
                    </template>
                    <p class="text-sm text-on-surface-variant mt-0.5" x-text="toast.message"></p>
                </div>

                <!-- Action Button -->
                <template x-if="toast.action">
                    <button
                        type="button"
                        class="flex-shrink-0 px-3 py-1.5 text-xs font-semibold rounded-xl border border-outline-variant/50 hover:bg-surface-container-high transition-motion-fast"
                        :class="toast.actionColor"
                        @click="handleAction(toast)"
                    >
                        <span x-text="toast.action.label"></span>
                    </button>
                </template>

                <!-- Close Button -->
                <button
                    type="button"
                    class="flex-shrink-0 p-1 rounded-lg text-on-surface-variant/60 hover:text-on-surface hover:bg-surface-container-high transition-motion-fast -ml-1 mr-2"
                    @click="remove(toast.id)"
                    aria-label="Tutup notifikasi"
                >
                    <span class="material-symbols-outlined size-4">close</span>
                </button>
            </div>

            <!-- Progress Bar (Timer) -->
            <template x-if="toast.duration && toast.duration > 0">
                <div class="h-1 w-full bg-on-surface-variant/10 rounded-b-xl overflow-hidden">
                    <div
                        class="h-full bg-current rounded-b-xl transition-all duration-[100ms] ease-linear"
                        :style="`width: ${toast.progress}%`"
                        :class="toast.progressColor"
                        role="progressbar"
                        aria-valuenow="100"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Waktu tersisa notifikasi"
                    ></div>
                </div>
            </template>
        </div>
    </template>
</div>