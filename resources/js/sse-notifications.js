export default function bootSseNotifications() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        return;
    }

    const isAuthPage = document.querySelector('meta[name="user-id"]') !== null
        || document.body.classList.contains('authenticated');

    if (!isAuthPage) {
        return;
    }

    let lastChecked = new Date().toISOString();

    setInterval(async () => {
        try {
            const response = await fetch('/sse/notifications?since=' + encodeURIComponent(lastChecked), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const json = await response.json();

            if (json.data && json.data.length > 0) {
                lastChecked = new Date().toISOString();

                json.data.forEach((notification) => {
                    window.dispatchEvent(new CustomEvent('new-notification', { detail: notification }));
                });

                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('refresh-notifications');
                }
            }
        } catch {
            // Silently retry on next interval
        }
    }, 30000);
}
