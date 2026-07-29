<<<<<<< HEAD
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
=======
import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import flatpickr from "flatpickr";
import Swal from "sweetalert2";
import Chart from "chart.js/auto";
import { Capacitor } from "@capacitor/core";
import { StatusBar, Style } from "@capacitor/status-bar";
import { App } from "@capacitor/app";
import { Browser } from "@capacitor/browser";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import CapacitorGeolocation from "./services/capacitor-geolocation";

import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import bootSseNotifications from "./sse-notifications";

delete L.Icon.Default.prototype._getIconUrl;
>>>>>>> main
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});
<<<<<<< HEAD
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
=======

window.L = L;
window.TomSelect = TomSelect;
window.flatpickr = flatpickr;
window.Swal = Swal;
window.Chart = Chart;
window.Capacitor = window.Capacitor || Capacitor;
window.CapacitorGeolocation = CapacitorGeolocation;
window.CapacitorApp = App;
window.axios = axios;

const pasPapanAlertLabels = () => ({
    confirm: window.PasPapanAlertLabels?.confirm || "Confirm",
    cancel: window.PasPapanAlertLabels?.cancel || "Cancel",
    confirmTitle: window.PasPapanAlertLabels?.confirmTitle || "Are you sure?",
    noticeTitle: window.PasPapanAlertLabels?.noticeTitle || "Notice",
>>>>>>> main
});

const normalizeSweetAlertIcon = (icon) => {
    if (["danger", "failed", "failure"].includes(icon)) {
        return "error";
    }

    if (["warn"].includes(icon)) {
        return "warning";
    }

    return ["success", "error", "warning", "info", "question"].includes(icon)
        ? icon
        : "info";
};

const sweetAlertBaseClasses = {
    popup: "!w-[min(28rem,calc(100vw-2rem))] !rounded-[1.35rem] !border !border-slate-200 !bg-white !px-5 !py-6 !text-slate-950 !shadow-[0_28px_80px_-42px_rgba(15,23,42,0.62)]   ",
    icon: "!my-2 !h-16 !w-16 !border-[0.28rem]",
    title: "!mt-4 !text-lg !font-bold !tracking-tight",
    htmlContainer: "!mx-0 !mt-3 !text-sm !leading-6 !text-slate-600 ",
    actions: "!mt-7 !grid !w-full !grid-cols-1 !gap-3 sm:!grid-cols-2",
    confirmButton: "!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-2xl !bg-primary-700 !px-5 !py-3 !text-base !font-bold !text-white hover:!bg-primary-800 focus:!shadow-none focus:!ring-2 focus:!ring-primary-500 focus:!ring-offset-2   ",
    cancelButton: "!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-2xl !border !border-slate-300 !bg-transparent !px-5 !py-3 !text-base !font-bold !text-slate-700 hover:!bg-slate-50 focus:!shadow-none focus:!ring-2 focus:!ring-slate-300 focus:!ring-offset-2   ",
};

const createPasPapanToast = () => Swal.mixin({
    toast: true,
    position: "bottom-end",
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
    background: "transparent",
    customClass: {
        popup: "!bg-white  !text-slate-900  !rounded-2xl !shadow-[0_18px_48px_-28px_rgba(15,23,42,0.65)] !border !border-slate-100 /70 !px-4 !py-3 !w-auto !max-w-[92vw] !mx-auto !mt-4",
        title: "!text-sm !font-semibold !leading-5",
        timerProgressBar: "!bg-primary-500 !h-1",
    },
    didOpen: (toast) => {
        toast.addEventListener("mouseenter", Swal.stopTimer);
        toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
});

window.PasPapanAlert = {
    toast(payload = {}) {
        const title = payload.title || payload.message || payload.text || "";

        if (!title) {
            return Promise.resolve();
        }

        return createPasPapanToast().fire({
            icon: normalizeSweetAlertIcon(payload.icon || payload.style || "info"),
            title,
        });
    },
    modal(payload = {}) {
        const labels = pasPapanAlertLabels();
        return Swal.fire({
            title: payload.title || payload.text || "",
            html: payload.html || "",
            icon: normalizeSweetAlertIcon(payload.icon || payload.style || "info"),
            confirmButtonText: labels.confirm,
            cancelButtonText: labels.cancel,
            showCancelButton: true,
            customClass: sweetAlertBaseClasses,
        });
    },
};

document.addEventListener("DOMContentLoaded", () => bootSseNotifications());

window.tomSelectInput = (options, placeholder, selected, disabled) => ({
    init() {
        this.$nextTick(() => {
            const el = this.$refs.select;
            if (!el || el.tomselect) return;

            new TomSelect(el, {
                placeholder: placeholder || 'Select an option',
                maxOptions: null,
                allowEmptyOption: true,
            });
        });
    },
});

<<<<<<< HEAD
observer.observe(document.body, { childList: true, subtree: true });
=======
>>>>>>> main
