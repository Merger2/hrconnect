import './pwa-install';
import './tom-select';
import { loadFaceModels, faceapi } from './face-recognition';
import faceEnrollment from './face-enrollment';
import { detectLocation, initGpsLocator, DEFAULT_CENTER } from './gps-locator';
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
import clockIn from './clock-in';
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
    icon: '!my-2 !h-16 !w-16 !border-[0.28rem]',
    title: '!mt-4 !text-lg !font-bold !tracking-tight !text-ink',
    htmlContainer: '!mx-0 !mt-3 !text-sm !leading-6 !text-on-surface-variant',
    actions: '!mt-6 !grid !w-full !grid-cols-2 !gap-3',
    confirmButton: '!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-xl !bg-ink !px-5 !py-3 !text-sm !font-bold !text-white focus:!ring-2 focus:!ring-ink/20',
    cancelButton: '!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-xl !border !border-outline-variant !bg-canvas !px-5 !py-3 !text-sm !font-bold !text-ink',
};

// `authChecking` adalah flag yang True SELAMA kita sedang mengecek/mengambil
// Sanctum token. Komponen Alpine yang butuh auth (mis. attendance/today,
// leave/quota) harus menunggu flag ini False sebelum memanggil API, agar tidak
// memicu 401/404 palsu di halaman publik (login, register, dll) yang juga
// memuat app.js.
// Baca status auth dari <meta name="auth-status"> — head.blade.php menambahkannya
// di semua layout. Di halaman publik (login/register) value-nya 'guest'.
window.isAuthenticated = document.querySelector('meta[name="auth-status"]')
    ?.getAttribute('content') === 'authenticated';
window.authChecking = true;

const fetchSanctumToken = async () => {
    // Hanya ambil token kalau user terautentikasi di halaman ini. app.js
    // dimuat di LAYOUT, bukan per-halaman, jadi dia juga jalan di halaman
    // publik (login/register/forgot-password). Memanggil endpoint yang
    // butuh auth:sanctum di halaman publik akan menghasilkan 401 palsu.
    if (!window.isAuthenticated) {
        window.authChecking = false;
        return;
    }

    if (localStorage.getItem('sanctum_token')) {
        window.authChecking = false;
        return;
    }

    try {
        const res = await fetch('/api/v1/sanctum/token', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const json = await res.json();
            if (json.data?.token) {
                localStorage.setItem('sanctum_token', json.data.token);
            }
        }
        // 401 di sini bukan error — berarti sesi belum ada (halaman publik).
    } catch {
        // Silent fail — token akan unavailable untuk sesi ini.
    } finally {
        window.authChecking = false;
    }
};
fetchSanctumToken();

window.apiHeaders = () => {
    const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const token = localStorage.getItem('sanctum_token');
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return headers;
};

// Helper: tunggu hingga pemeriksaan auth selesai sebelum memanggil API
// yang butuh Bearer token. Mengembalikan Promise<void>.
window.whenAuthReady = () => new Promise((resolve) => {
    if (!window.authChecking) return resolve();
    const interval = setInterval(() => {
        if (!window.authChecking) {
            clearInterval(interval);
            resolve();
        }
    }, 25);
});

window.HRConnectAlert = {
    toast(data) {
        const isDark = false; // Light mode only.
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
window.loadFaceModels = loadFaceModels;
window.faceapi = faceapi;
window.detectLocation = detectLocation;
window.initGpsLocator = initGpsLocator;
window.GPS_DEFAULT_CENTER = DEFAULT_CENTER;
// Leaflet map utilities for branch geofence forms
// Leaflet map utilities for branch geofence forms
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
    window.Alpine.data('clockIn', clockIn);
    window.Alpine.data('approvalsIndex', approvalsIndex);
    window.Alpine.data('employeesIndex', employeesIndex);
    window.Alpine.data('employeeShow', employeeShow);
    window.Alpine.data('createEmployeeForm', createEmployeeForm);
    window.Alpine.data('terminateEmployeeForm', terminateEmployeeForm);
    window.Alpine.data('importEmployeesForm', importEmployeesForm);
    window.Alpine.data('assetsIndex', assetsIndex);
    window.Alpine.data('knowledgeBaseChat', knowledgeBaseChat);
    window.Alpine.data('faceEnrollment', faceEnrollment);
    window.Alpine.store('darkMode', {
        on: false,

        init() {
            // Light mode only — HRConnect design system enforces light theme.
            // Strip any stale 'dark' class from previous builds/localStorage.
            document.documentElement.classList.remove('dark');
            try { localStorage.removeItem('theme'); } catch (_) {}
        },

        toggle() {
            // No-op — light mode only.
        },

        set(mode) {
            // No-op — light mode only.
        },

        sync() {
            // No-op — light mode only. Never add the 'dark' class.
            document.documentElement.classList.remove('dark');
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