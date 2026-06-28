import './pwa-install';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

const swalClasses = {
    confirmButton: 'inline-flex items-center justify-center rounded-xl bg-danger px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 ml-2',
    cancelButton: 'inline-flex items-center justify-center rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
    popup: 'rounded-xl border border-outline-variant bg-canvas p-6 shadow-lg',
    title: 'text-lg font-semibold text-ink',
    htmlContainer: 'text-sm text-on-surface-variant',
};

window.HRConnectAlert = {
    toast(data) {
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
    },

    async confirm(message, options = {}) {
        const result = await Swal.fire({
            icon: options.icon || 'question',
            title: options.title || 'Apakah Anda yakin?',
            text: message || options.text || '',
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Ya, lanjutkan',
            cancelButtonText: options.cancelButtonText || 'Batal',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: swalClasses,
        });

        return result.isConfirmed;
    },

    modal(options) {
        return Swal.fire({
            icon: options.icon || 'info',
            title: options.title || '',
            text: options.text || '',
            html: options.html || undefined,
            showConfirmButton: true,
            confirmButtonText: options.confirmButtonText || 'Tutup',
            showCancelButton: options.showCancelButton || false,
            cancelButtonText: options.cancelButtonText || 'Batal',
            buttonsStyling: false,
            customClass: swalClasses,
            didRender: options.didRender || undefined,
        });
    },
};

function installSweetAlertConfirmations(root = document) {
    root.querySelectorAll?.('[wire\\:confirm], [wire\\:confirm\\.prompt]').forEach((element) => {
        element.__livewire_confirm = (onConfirm, onCancel) => {
            const message = element.getAttribute('wire:confirm')
                || element.getAttribute('wire:confirm.prompt')
                || 'Apakah Anda yakin?';

            window.HRConnectAlert.confirm(message).then((confirmed) => {
                if (confirmed) {
                    onConfirm();
                    return;
                }
                onCancel();
            });
        };
    });
}

document.addEventListener('livewire:init', () => {
    if (typeof window.Alpine === 'undefined') return;

    Livewire.on('notify', (data) => {
        window.HRConnectAlert.toast(data);
    });

    initUiPickers();
    installSweetAlertConfirmations();
});

const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
                installSweetAlertConfirmations(node);
            }
        });
    });
});

observer.observe(document.body, { childList: true, subtree: true });

function initUiPickers() {
    const pickerObserver = new MutationObserver(() => {
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

    pickerObserver.observe(document.body, { childList: true, subtree: true });
}
