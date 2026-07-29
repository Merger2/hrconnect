<div class="scanner-card-surface native-scan-surface relative overflow-visible" id="scanner-card" wire:ignore>
    <div class="scanner-header relative flex flex-col gap-3">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h3 class="flex items-center gap-2 text-base font-semibold text-slate-950 dark:text-white">
                    <span class="scanner-title-icon">
                        <x-heroicon-o-qr-code class="h-5 w-5" />
                    </span>
                    <span class="truncate">{{ $title }}</span>
                </h3>
                <p class="scanner-helper-text">{{ __('Tap the frame, then align the attendance QR.') }}</p>
            </div>
            <button type="button" id="switch-camera-btn" onclick="window.switchCamera?.()"
                aria-label="{{ __('Switch camera') }}" class="scanner-camera-toggle">
                <x-heroicon-o-arrow-path class="h-4 w-4" />
                <span>{{ __('Switch') }}</span>
            </button>
        </div>

        @if (isset($headerActions))
            <div class="w-full">
                {{ $headerActions }}
            </div>
        @endif
    </div>

    <div class="hidden mb-3 max-h-32 w-full max-w-sm overflow-y-auto rounded-lg border border-red-500/50 bg-gray-900 p-2 shadow-inner">
        <div class="mb-1 flex items-center justify-between border-b border-gray-700 pb-1 font-mono text-[10px] text-gray-400">
            <span>{{ __('Camera Debug Log') }}</span>
            <button onclick="this.parentElement.parentElement.classList.add('hidden')" class="text-gray-500 hover:text-white" aria-label="{{ __('Dismiss') }}">
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
        <div id="debug-log" class="word-break space-y-0.5 whitespace-pre-wrap font-mono text-[10px] text-green-400"></div>
    </div>

    <div class="scanner-container group relative mx-auto flex aspect-square w-full cursor-pointer items-center justify-center overflow-hidden rounded-2xl"
        id="scanner" onclick="handleScanClick()">
        <div id="scanner-overlay" class="pointer-events-none absolute inset-0 z-10 hidden">
            <div class="absolute inset-8 shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]">
                <div class="absolute -left-1 -top-1 h-10 w-10 border-l-[4px] border-t-[4px] border-white"></div>
                <div class="absolute -right-1 -top-1 h-10 w-10 border-r-[4px] border-t-[4px] border-white"></div>
                <div class="absolute -bottom-1 -left-1 h-10 w-10 border-b-[4px] border-l-[4px] border-white"></div>
                <div class="absolute -bottom-1 -right-1 h-10 w-10 border-b-[4px] border-r-[4px] border-white"></div>
            </div>
        </div>

        <span id="scanner-placeholder" class="scanner-placeholder z-0">
            <span class="scanner-placeholder__icon">
                <x-heroicon-o-camera class="h-8 w-8" />
            </span>
            <span class="scanner-placeholder__title">{{ __('Tap to open camera') }}</span>
            <span class="scanner-placeholder__copy">{{ __('Camera permission is required for attendance.') }}</span>
        </span>
    </div>

    <div id="scanner-result" class="mt-3 hidden text-center text-sm font-medium text-green-600 dark:text-green-400"></div>
    <div id="scanner-error" class="mt-3 hidden text-center text-sm font-medium text-red-600 dark:text-red-400"></div>

    @if (isset($slot) && $slot->isNotEmpty())
        <div class="scanner-footer">
            {{ $slot }}
        </div>
    @endif
</div>
