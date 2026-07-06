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

const fetchSanctumToken = async () => {
    if (sessionStorage.getItem('sanctum_token')) return;
    try {
        const res = await fetch('/api/v1/sanctum/token', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const json = await res.json();
            if (json.data?.token) {
                sessionStorage.setItem('sanctum_token', json.data.token);
            }
        }
    } catch {
        // Silent fail — token will be unavailable for this session
    }
};
fetchSanctumToken();

window.apiHeaders = () => {
    const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const token = sessionStorage.getItem('sanctum_token');
    if (token) headers['Authorization'] = 'Bearer ' + token;
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

window.initializeMap = function ({ onUpdate, location }) {
    const defaultLoc = location ?? [-6.2088, 106.8456];

    const map = L.map('branch-map').setView(defaultLoc, 16);
    window._branchMapRef = map;

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 21,
    }).addTo(map);

    const marker = L.marker(defaultLoc, { draggable: true }).addTo(map);
    window._branchMarker = marker;

    window._branchUpdateCoords = (lat, lng) => onUpdate(Number(lat).toFixed(6), Number(lng).toFixed(6));

    marker.on('dragend', (e) => {
        const pos = marker.getLatLng();
        window._branchUpdateCoords(pos.lat, pos.lng);
    });

    map.on('drag', () => {
        const center = map.getCenter();
        marker.setLatLng(center);
        window._branchUpdateCoords(center.lat, center.lng);
    });

    window._branchUpdateCoords(defaultLoc[0], defaultLoc[1]);

    setTimeout(() => map.invalidateSize(), 500);
};

window.setMapLocation = function ({ location }) {
    const map = window._branchMapRef;
    const marker = window._branchMarker;
    if (!location || !map) return;
    map.setView(location, 16);
    if (marker) marker.setLatLng(location);
};

window.detectLocation = function () {
    if (!navigator.geolocation) {
        window.HRConnectAlert?.toast({ type: 'error', message: 'Browser tidak mendukung geolokasi.' });
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude.toFixed(6);
            const lng = pos.coords.longitude.toFixed(6);
            const latEl = document.getElementById('lat-input');
            const lngEl = document.getElementById('lng-input');
            if (latEl) { latEl.value = lat; latEl.dispatchEvent(new Event('input', { bubbles: true })); }
            if (lngEl) { lngEl.value = lng; lngEl.dispatchEvent(new Event('input', { bubbles: true })); }
            if (window._branchMapRef) {
                window._branchMapRef.setView([lat, lng], 18);
                if (window._branchMarker) window._branchMarker.setLatLng([lat, lng]);
                window._branchUpdateCoords?.(lat, lng);
            }
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`)
                .then(r => r.json())
                .then(data => {
                    const addr = data?.display_name;
                    if (addr) {
                        const addrEl = document.getElementById('address');
                        if (addrEl) { addrEl.value = addr; addrEl.dispatchEvent(new Event('input', { bubbles: true })); }
                    }
                })
                .catch(() => {});
            window.HRConnectAlert?.toast({ type: 'success', message: 'Lokasi terdeteksi' });
        },
        (err) => {
            const messages = {
                1: 'Izin lokasi ditolak. Buka pengaturan browser untuk mengizinkan akses lokasi.',
                2: 'Lokasi tidak tersedia. Pastikan GPS/Location Service aktif.',
                3: 'Waktu mendeteksi lokasi habis. Coba lagi.',
            };
            const msg = messages[err.code] || 'Gagal mendeteksi lokasi.';
            window.HRConnectAlert?.toast({ type: 'error', message: msg });
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    );
};

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

    Livewire.on('branch-map-open', () => {
        setTimeout(() => {
            const latEl = document.getElementById('lat-input');
            const lngEl = document.getElementById('lng-input');
            const mapEl = document.getElementById('branch-map');
            if (!mapEl || !latEl || !lngEl) return;

            if (window._branchMapInit) {
                window._branchMapInit = null;
                if (window._branchMapRef) { window._branchMapRef.remove(); window._branchMapRef = null; }
            }

            const hasCoords = latEl.value && lngEl.value;
            window.initializeMap({
                location: hasCoords ? [parseFloat(latEl.value), parseFloat(lngEl.value)] : undefined,
                onUpdate: (lat, lng) => {
                    latEl.value = lat; latEl.dispatchEvent(new Event('input', { bubbles: true }));
                    lngEl.value = lng; lngEl.dispatchEvent(new Event('input', { bubbles: true }));
                },
            });

            window._branchMapInit = true;

            [latEl, lngEl].forEach(el => {
                el.addEventListener('input', () => {
                    const lat = parseFloat(latEl.value);
                    const lng = parseFloat(lngEl.value);
                    if (!isNaN(lat) && !isNaN(lng)) window.setMapLocation({ location: [lat, lng] });
                });
            });
        }, 300);
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

