import './pwa-install';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.confirmAction = (options) => {
    return Swal.fire({
        icon: options.icon || 'warning',
        title: options.title || 'Apakah Anda yakin?',
        text: options.text || '',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Ya, lanjutkan',
        cancelButtonText: options.cancelText || 'Batal',
        confirmButtonColor: options.confirmColor || '#0a0a0a',
        reverseButtons: true,
    });
};

document.addEventListener('livewire:init', () => {
    if (typeof window.Alpine === 'undefined') return;

    Livewire.on('notify', (data) => {
        const config = {
            toast: true,
            position: 'bottom-right',
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
            icon: data.type || 'success',
            title: data.message || '',
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            },
        };

        Swal.fire(config);
    });

    initUiPickers();
});

function initUiPickers() {
    const observer = new MutationObserver(() => {
        document.querySelectorAll('[data-ui-picker]:not([data-ui-picker-initialized])').forEach((el) => {
            el.setAttribute('data-ui-picker-initialized', '');
            const mode = el.getAttribute('data-ui-picker');

            const config = {
                dateFormat: 'Y-m-d',
                allowInput: true,
            };

            if (mode === 'datetime') {
                config.enableTime = true;
                config.dateFormat = 'Y-m-d H:i';
            } else if (mode === 'time') {
                config.noCalendar = true;
                config.enableTime = true;
                config.dateFormat = 'H:i';
            }

            flatpickr(el, config);
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
