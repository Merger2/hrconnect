let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;

    const installBanner = document.getElementById('pwa-install-banner');
    if (installBanner) {
        installBanner.classList.remove('hidden');
    }
});

window.installPwa = function () {
    if (!deferredPrompt) return;

    deferredPrompt.prompt();

    deferredPrompt.userChoice.then((choice) => {
        if (choice.outcome === 'accepted') {
            console.log('User accepted PWA install');
        }
        deferredPrompt = null;
        const installBanner = document.getElementById('pwa-install-banner');
        if (installBanner) {
            installBanner.classList.add('hidden');
        }
    });
};

window.registerServiceWorker = function () {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').then(() => {
            console.log('Service Worker registered');
        }).catch((error) => {
            console.warn('Service Worker registration failed:', error);
        });
    }
};
