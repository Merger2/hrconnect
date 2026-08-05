@php
/**
 * PWA Install Prompt Component
 * Menampilkan tombol install Progressive Web App
 * Didukung oleh Chrome/Edge/Android
 *
 * Usage: <x-pwa-install-prompt />
 * Catatan: beforeinstallprompt hanya fire di HTTPS.
 * Di dev (HTTP), fallback ke panduan manual install.
 */
@endphp

<div
    x-data="pwaInstall()"
    x-show="show"
    x-cloak
    class="fixed bottom-[calc(6.5rem+env(safe-area-inset-bottom))] right-4 z-40 max-w-sm"
    role="alert"
    aria-live="polite"
>
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 p-4 max-w-sm">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <img src="/icon-192.svg" alt="HRConnect" class="w-12 h-12 rounded-xl" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900">
                    {{ __('Install HRConnect') }}
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
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
                        @click="dismiss()"
                        class="text-xs text-gray-500 hover:text-gray-900 transition-colors"
                    >
                        {{ __('Nanti') }}
                    </button>
                </div>
            </div>
            <button
                @click="dismiss()"
                class="flex-shrink-0 p-1 text-gray-500 hover:text-gray-900 transition-colors"
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
            // JANGAN tampilkan prompt di localhost/development — menyebalkan
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                return;
            }

            // Cek dismissal — kalo pengguna klik "Nanti", jangan munculin lagi selama 7 hari
            const dismissed = localStorage.getItem('pwa-dismissed-at');
            const sevenDays = 7 * 24 * 60 * 60 * 1000;
            if (dismissed && Date.now() - parseInt(dismissed, 10) < sevenDays) {
                return;
            }

            // Tampilkan prompt jika belum terinstall dan bukan standalone
            if (!this.isStandalone && !this.installed) {
                // Tunggu 10 detik sebelum muncul
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
                localStorage.removeItem('pwa-dismissed-at');
                this.deferredPrompt = null;
            });
        },

        dismiss() {
            this.show = false;
            localStorage.setItem('pwa-dismissed-at', String(Date.now()));
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
            this.dismiss();
            alert('{{ __("Buka menu browser, pilih \"Install\" atau \"Add to Home Screen\"") }}');
        }
    };
}
</script>
