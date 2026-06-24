import './pwa-install';

document.addEventListener('livewire:init', () => {
    if (typeof window.Alpine === 'undefined') return;

    window.Alpine.data('toast', () => ({
        show: false,
        message: '',
        variant: 'success',
        init() {
            this.$nextTick(() => {
                Livewire.on('toast', (data) => {
                    this.message = data.text;
                    this.show = true;
                    setTimeout(() => this.show = false, 4000);
                });
            });
        },
    }));
});
