import './pwa-install';
import './tom-select';
import { watchPickerMounts } from './datepicker';
import { installValidation } from './validation';
import profilePhotoEditor from './profile-photo-editor';
import payrollIndex from './payroll-index';
import loansIndex from './loans-index';
import overtimesIndex from './overtimes-index';
import overtimeApply from './overtime-apply';
import leavesIndex from './leaves-index';
import leaveApply from './leave-apply';
import reimbursementsIndex from './reimbursements-index';
import reimbursementApply from './reimbursement-apply';
import attendanceIndex from './attendance-index';
import approvalsIndex from './approvals-index';
import employeesIndex from './employees-index';
import employeeShow from './employee-show';
import createEmployeeForm from './create-employee-form';
import terminateEmployeeForm from './terminate-employee-form';
import importEmployeesForm from './import-employees-form';
import assetsIndex from './assets-index';
import knowledgeBaseChat from './knowledge-base-chat';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
// Fix Leaflet default marker icon paths (broken in Vite builds)
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

const normalizeIcon = (icon) => ({ danger: 'error', failed: 'error', failure: 'error', warn: 'warning' }[icon] || icon);

const swalClasses = {
    popup: '!rounded-[1.35rem] !border !border-outline-variant/50 !bg-canvas !px-5 !py-6 !shadow-[0_28px_80px_-42px_rgba(10,10,10,0.55)]',
    icon: '!my-2 !h-16 !w-16 !border-[0.28rem]',
    title: '!mt-4 !text-lg !font-bold !tracking-tight !text-ink',
    htmlContainer: '!mx-0 !mt-3 !text-sm !leading-6 !text-on-surface-variant',
    actions: '!mt-6 !grid !w-full !grid-cols-2 !gap-3',
    confirmButton: '!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-xl !bg-ink !px-5 !py-3 !text-sm !font-bold !text-white focus:!ring-2 focus:!ring-ink/20',
    cancelButton: '!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-xl !border !border-outline-variant !bg-canvas !px-5 !py-3 !text-sm !font-bold !text-ink',
};

window.apiHeaders = () => {
    const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const xsrf = document.cookie.match('(^|; )XSRF-TOKEN=([^;]*)')?.pop();
    if (xsrf) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf);
    return headers;
};

window.HRConnectAlert = {
    toast(data) {
        const isDark = document.documentElement.classList.contains('dark');
        const config = {
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
            icon: normalizeIcon(data.type || 'success'),
            title: data.message || data.text || '',
            background: 'transparent',
            customClass: {
                popup: '!bg-canvas !text-ink !rounded-2xl !shadow-[0_18px_48px_-28px_rgba(10,10,10,0.55)] !border !border-outline-variant/50 !px-4 !py-3 !w-auto !max-w-[92vw]',
                title: '!text-sm !font-semibold !leading-5',
                timerProgressBar: '!bg-ink !h-1',
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
    window.Alpine.data('payrollIndex', payrollIndex);
    window.Alpine.data('loansIndex', loansIndex);
    window.Alpine.data('overtimesIndex', overtimesIndex);
    window.Alpine.data('overtimeApply', overtimeApply);
    window.Alpine.data('leavesIndex', leavesIndex);
    window.Alpine.data('leaveApply', leaveApply);
    window.Alpine.data('reimbursementsIndex', reimbursementsIndex);
    window.Alpine.data('reimbursementApply', reimbursementApply);
    window.Alpine.data('attendanceIndex', attendanceIndex);
    window.Alpine.data('approvalsIndex', approvalsIndex);
    window.Alpine.data('employeesIndex', employeesIndex);
    window.Alpine.data('employeeShow', employeeShow);
    window.Alpine.data('createEmployeeForm', createEmployeeForm);
    window.Alpine.data('terminateEmployeeForm', terminateEmployeeForm);
    window.Alpine.data('importEmployeesForm', importEmployeesForm);
    window.Alpine.data('assetsIndex', assetsIndex);
    window.Alpine.data('knowledgeBaseChat', knowledgeBaseChat);
    window.Alpine.store('darkMode', {
        on: false,

        init() {
            // Dark mode temporarily disabled — CSS not ready for dark variant
            // Remove 'dark' class if set from previous localStorage
            document.documentElement.classList.remove('dark');
        },

        toggle() {
            // Disabled — no-op until dark mode CSS is implemented
        },

        set(mode) {
            // Disabled
        },

        sync() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.on = this.mode === 'dark' || (this.mode === 'system' && prefersDark);
            document.documentElement.classList.toggle('dark', this.on);
        },
    });
});

const reinitLivewireComponents = () => {
    initUiPickers();
    installValidation();
    installSweetAlertConfirmations();
};

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

document.addEventListener('livewire:navigated', () => {
    reinitLivewireComponents();
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

