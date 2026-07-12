@php
/**
 * PWA Install Prompt Component
 * Menampilkan tombol install Progressive Web App
 * Didukung oleh Chrome/Edge/Android
 *
 * Usage: <x-pwa-install-prompt />
 */
$showInstall = auth()->check() && ! request()->secure() === false;
@endphp

<div
    x-data="pwaInstall()"
    x-show="show"
    x-cloak
    class="fixed bottom-4 right-4 z-50"
    role="alert"
    aria-live="polite"
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-blue-100 dark:border-blue-900 p-4 max-w-sm">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <img src="/icon-192.svg" alt="HRConnect" class="w-12 h-12 rounded-xl" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ __('Install HRConnect') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Akses cepat dari layar utama perangkat Anda') }}
                </p>
                <div class="flex items-center gap-2 mt-3">
                    <button
                        @click="installApp()"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-gradient-to-r from-[#0096D6] to-[#00BCF2] hover:from-[#0080B8] hover:to-[#00A8D9] transition-all duration-200"
                    >
                        {{ __('Install') }}
                    </button>
                    <button
                        @click="show = false"
                        class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        {{ __('Nanti') }}
                    </button>
                </div>
            </div>
            <button
                @click="show = false"
                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                aria-label="{{ __('Tutup') }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Tombol install stand-alone (untuk PWA) --}}
    <button
        x-show="!deferredPrompt && isStandalone === false"
        @click="showInstallGuide()"
        class="mt-2 w-full text-xs text-center text-blue-500 hover:text-blue-600 transition-colors"
    >
        {{ __('Pelajari cara install HRConnect') }}
    </button>
</div>

<script>
function pwaInstall() {
    return {
        show: false,
        deferredPrompt: null,
        isStandalone: window.matchMedia('(display-mode: standalone)').matches,
        installed: localStorage.getItem('pwa-installed'),

        init() {
            // Tampilkan prompt jika belum terinstall dan bukan standalone
            if (!this.isStandalone && !this.installed) {
                // Tunggu 5 detik sebelum muncul
                setTimeout(() => { this.show = true; }, 5000);
            }

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.show = true;
            });

            window.addEventListener('appinstalled', () => {
                this.installed = true;
                this.show = false;
                localStorage.setItem('pwa-installed', 'true');
                this.deferredPrompt = null;
            });
        },

        installApp() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                this.deferredPrompt.userChoice.then((choice) => {
                    if (choice.outcome === 'accepted') {
                        console.log('PWA installed');
                    }
                    this.deferredPrompt = null;
                });
            } else {
                // Fallback: arahkan ke halaman petunjuk
                this.showInstallGuide();
            }
        },

        showInstallGuide() {
            alert('{{ __("Buka menu browser, pilih \"Install\" atau \"Add to Home Screen\"") }}');
        }
    };
}
</script>
