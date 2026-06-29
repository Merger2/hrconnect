import './pwa-install';
import './tom-select';
import { watchPickerMounts } from './datepicker';
import { installValidation } from './validation';
import profilePhotoEditor from './profile-photo-editor';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
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
        const isDark = document.documentElement.classList.contains('dark');
        const config = {
            toast: true,
            position: 'bottom-right',
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
            icon: data.type || 'success',
            title: data.message || '',
            background: isDark ? '#1c1b1b' : '#ffffff',
            color: isDark ? '#f8fafc' : '#0a0a0a',
            iconColor: data.type === 'error' ? '#ba1a1a' : data.type === 'warning' ? '#f59e0b' : '#22c55e',
            customClass: {
                popup: 'rounded-xl border border-outline-variant/50 shadow-lg px-4 py-3 font-sans',
                title: 'text-sm font-semibold text-ink',
                timerProgressBar: 'bg-primary h-1',
            },
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

window.L = L;
window.profilePhotoEditor = profilePhotoEditor;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('profilePhotoEditor', profilePhotoEditor);
    window.Alpine.store('darkMode', {
        on: false,
        mode: localStorage.getItem('theme') || 'system',

        init() {
            this.mode = localStorage.getItem('theme') || 'system';
            this.sync();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.mode === 'system') this.sync();
            });
        },

        toggle() {
            if (this.mode === 'system') {
                this.mode = this.on ? 'light' : 'dark';
            } else {
                this.mode = this.mode === 'dark' ? 'light' : 'dark';
            }
            localStorage.setItem('theme', this.mode);
            this.sync();
        },

        set(mode) {
            this.mode = mode;
            localStorage.setItem('theme', mode);
            this.sync();
        },

        sync() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.on = this.mode === 'dark' || (this.mode === 'system' && prefersDark);
            document.documentElement.classList.toggle('dark', this.on);
        },
    });
});

document.addEventListener('livewire:init', () => {
    if (typeof window.Alpine === 'undefined') return;

    if (window.tomSelectInput) {
        window.Alpine.data('tomSelectInput', window.tomSelectInput);
    }

    Livewire.on('toast', (data) => {
        window.HRConnectAlert.toast({
            type: data.variant || 'success',
            message: data.text || '',
        });
    });

    watchPickerMounts();
    installValidation();
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

