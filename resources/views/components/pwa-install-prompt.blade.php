@php
/** 
 * PWA Install Prompt Component
 * Menampilkan tombol install Progressive Web App
 * Didukung oleh Chrome/Edge/Android
 * 
 * Usage: <x-pwa-install-prompt />
 */
$showInstall = auth()->check() && !request()->secure();
@endphp

<div
    x-data="pwaInstall()"
    x-show="show"
    x-cloak
    class="fixed bottom-6 right-6 z-50 max-w-sm"
    role="alert"
    aria-live="polite"
>
    <div class="bg-canvas dark:bg-surface-container-high rounded-2xl shadow-2xl border border-outline-variant p-4 max-w-sm">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <img src="/icon-192.svg" alt="HRConnect" class="w-12 h-12 rounded-xl" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-ink dark:text-on-surface">
                    {{ __('Install HRConnect') }}
                </p>
                <p class="text-xs text-on-surface-variant dark:text-on-surface-variant mt-0.5">
                    {{ __('Akses cepat dari layar utama perangkat Anda') }}
                </p>
                <div class="flex items-center gap-2 mt-3">
                    <button
                        @click="installApp()"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-primary hover:bg-primary/90 transition-colors"
                    >
                        {{ __('Install') }}
                    </button>
                    <button
                        @click="show = false"
                        class="text-xs text-on-surface-variant hover:text-ink dark:hover:text-on-surface transition-colors"
                    >
                        {{ __('Nanti') }}
                    </button>
                </div>
            </div>
            <button
                @click="show = false"
                class="flex-shrink-0 p-1 text-on-surface-variant hover:text-ink dark:hover:text-on-surface transition-colors"
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
        class="mt-2 w-full text-xs text-center text-primary hover:text-primary/80 transition-colors"
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
                setTimeout(() => { this.show = true; }, 10000);
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
