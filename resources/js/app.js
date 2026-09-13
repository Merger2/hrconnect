import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
// Base flatpickr stylesheet WAJIB di-load. Tanpa ini, rule layout inti dari
// flatpickr (`.dayContainer` flex-wrap grid 7 kolom, posisi absolut panah
// prev/next month, header hari `.flatpickr-weekday` flex, state `display:none`
// saat tertutup) ikut hilang — kalender menyempit jadi satu baris tanggal
// bertumpuk, panah jadi raksasa, dropdown bulan/tahun melayang terpisah.
// Overrides di bawah menimpa tampilan visual di atas base ini.
import "flatpickr/dist/flatpickr.css";
import "../css/vendor/flatpickr-overrides.css";
import flatpickr from "flatpickr";
import Swal from "sweetalert2";
import { Capacitor } from "@capacitor/core";
import { StatusBar, Style } from "@capacitor/status-bar";
import { App } from "@capacitor/app";
import { Browser } from "@capacitor/browser";
import CapacitorGeolocation from "./services/capacitor-geolocation";

import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import bootSseNotifications from "./sse-notifications";

window.TomSelect = TomSelect;
window.flatpickr = flatpickr;
window.Swal = Swal;
window.Capacitor = window.Capacitor || Capacitor;
window.CapacitorGeolocation = CapacitorGeolocation;
window.CapacitorApp = App;
window.axios = axios;

// ─── Chart.js & Leaflet: lazy-load per halaman ─────────────────────────
// Dua library terbesar (vendor-charts ~198KB, vendor-maps ~179KB) hanya
// di-download saat halaman benar-benar memakainya. Blade pemakai sudah
// defensif: analytics/dashboard retry `typeof Chart === 'undefined'`,
// location-card return bila L belum ada, modal attendance memanggil
// window.ensureMaps() sebelum L.map.
let chartsPromise = null;
let mapsPromise = null;

window.ensureCharts = () => {
    if (!chartsPromise) {
        chartsPromise = import("chart.js/auto").then((mod) => {
            window.Chart = mod.default;
        });
    }

    return chartsPromise;
};

window.ensureMaps = () => {
    if (!mapsPromise) {
        // Wrapper leaflet-map.js: leaflet + markercluster dalam satu module graph
        // (markercluster UMD butuh global L — lihat komentar di file tsb).
        mapsPromise = import("./leaflet-map");
    }

    return mapsPromise;
};

// These Alpine factories are used by the attendance scan screen. They must be
// global: the scan screen can be reached through wire:navigate, which keeps the
// layout alive and does not execute scripts pushed by the destination Blade view.
window.clockInAction = () => ({
    gpsLoading: false, gpsCaptured: false, gpsAccuracy: null, gpsWarning: '',
    shiftEndTimestamp: null, hasApprovedOvertime: false, countdownTimer: null, countdownSeconds: 0, faceTimeoutTimer: null,
    init() {
        this.updateShiftEnd();
        this.$wire.$watch('attendance', () => this.updateShiftEnd());
        this.$wire.$watch('todayShiftSummary', () => this.updateShiftEnd());
        this.$wire.$watch('hasApprovedOvertime', (value) => { this.hasApprovedOvertime = value; });
        if (navigator.geolocation) { this.captureGps(); }
        this.startCountdown();
    },
    updateShiftEnd() {
        const endTime = this.$wire.attendance?.shift?.end_time || this.$wire.todayShiftSummary?.end_time;
        this.shiftEndTimestamp = endTime ? `${new Date().toISOString().slice(0, 10)} ${endTime}` : null;
        this.hasApprovedOvertime = this.$wire.hasApprovedOvertime;
    },
    get shiftLabel() { return this.$wire.todayShiftSummary?.is_off ? 'Off Day' : (this.$wire.todayShiftSummary?.name || 'No shift assigned'); },
    get workHours() { const s = this.$wire.todayShiftSummary; return s?.start && s?.end ? `${s.start} - ${s.end}` : 'Flexible'; },
    get shiftDuration() { return this.$wire.todayShiftSummary?.duration || ''; },
    get clockInTime() { return this.$wire.clockInTime || (this.$wire.attendance?.clock_in ? new Date(this.$wire.attendance.clock_in).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'); },
    get clockOutTime() { return this.$wire.clockOutTime || (this.$wire.attendance?.clock_out ? new Date(this.$wire.attendance.clock_out).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'); },
    get formattedCountdown() { if (!this.shiftEndTimestamp) return '--:--:--'; if (this.countdownSeconds <= 0) return this.hasApprovedOvertime ? 'Overtime' : 'Clock Out Now'; return [Math.floor(this.countdownSeconds / 3600), Math.floor((this.countdownSeconds % 3600) / 60), this.countdownSeconds % 60].map((v) => String(v).padStart(2, '0')).join(':'); },
    captureGps() {
        if (this.gpsLoading) return;
        this.gpsLoading = true; this.gpsWarning = '';
        if (!navigator.geolocation) { this.gpsWarning = 'Geolocation is not available in this browser.'; this.gpsLoading = false; return; }
        navigator.geolocation.getCurrentPosition((position) => {
            const { latitude, longitude, accuracy } = position.coords; const roundedAccuracy = Math.round(accuracy);
            this.gpsCaptured = true; this.gpsAccuracy = roundedAccuracy; this.gpsLoading = false;
            this.$wire.setGps(latitude, longitude, roundedAccuracy);
            window.dispatchEvent(new CustomEvent('gps-coordinates-updated', { detail: { latitude, longitude, accuracy: roundedAccuracy } }));
        }, (error) => {
            this.gpsWarning = error.code === error.PERMISSION_DENIED ? 'GPS permission denied. Please enable location access.' : 'Could not get GPS location.';
            this.gpsLoading = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 });
    },
    onGpsCaptured(detail) { if (detail?.latitude && detail?.longitude) { this.gpsCaptured = true; this.gpsAccuracy = detail.accuracy || null; this.gpsLoading = false; this.$wire.setGps(detail.latitude, detail.longitude, detail.accuracy); window.dispatchEvent(new CustomEvent('gps-coordinates-updated', { detail })); } },
    onFaceCaptured(detail) { if (!detail?.descriptor || !detail?.action) return; if (this.faceTimeoutTimer) { clearTimeout(this.faceTimeoutTimer); this.faceTimeoutTimer = null; } if (detail.action === 'clock_in') this.$wire.doClockInWithFace(detail.descriptor); else if (detail.action === 'clock_out') this.$wire.doClockOutWithFace(detail.descriptor); else if (detail.action === 'wfa') this.$wire.doWfaClockInWithFace(detail.descriptor); },
    startCountdown() { this.updateCountdown(); this.countdownTimer = setInterval(() => this.updateCountdown(), 1000); },
    updateCountdown() { if (this.shiftEndTimestamp) this.countdownSeconds = Math.max(0, Math.floor((new Date(this.shiftEndTimestamp).getTime() - Date.now()) / 1000)); },
    destroy() { if (this.countdownTimer) clearInterval(this.countdownTimer); },
});

window.locationCard = () => ({
    lat: null, lng: null, mapVisible: false, lastUpdated: '', _map: null, _userMarker: null, officeDistance: null, branchLat: null, branchLng: null, branchRadius: null, branchName: '',
    init() {
        this.lat = this.safeFloat(this.$wire.latitude); this.lng = this.safeFloat(this.$wire.longitude);
        this.branchLat = this.safeFloat(this.$wire.branchLatitude); this.branchLng = this.safeFloat(this.$wire.branchLongitude); this.branchRadius = this.safeFloat(this.$wire.branchRadius); this.branchName = this.$wire.branchName || ''; this.calcDistance();
        this.$wire.$watch('latitude', (value) => this.setCoordinates(value, this.lng)); this.$wire.$watch('longitude', (value) => this.setCoordinates(this.lat, value));
    },
    safeFloat(value) { const parsed = Number.parseFloat(value); return Number.isFinite(parsed) ? parsed : null; },
    setCoordinates(lat, lng) { this.lat = this.safeFloat(lat); this.lng = this.safeFloat(lng); this.calcDistance(); this.updateMap(); },
    calcDistance() { if (![this.lat, this.lng, this.branchLat, this.branchLng].every((value) => value !== null)) { this.officeDistance = null; return; } const rad = (value) => value * Math.PI / 180; const a = Math.sin(rad(this.branchLat - this.lat) / 2) ** 2 + Math.cos(rad(this.lat)) * Math.cos(rad(this.branchLat)) * Math.sin(rad(this.branchLng - this.lng) / 2) ** 2; this.officeDistance = Math.round(6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))); },
    get formattedDistance() { return this.officeDistance === null ? '' : (this.officeDistance < 1000 ? `${this.officeDistance} m` : `${(this.officeDistance / 1000).toFixed(1)} km`); },
    toggle() { this.mapVisible = !this.mapVisible; if (this.mapVisible && this.lat !== null && this.lng !== null) this.$nextTick(() => this.initMap()); },
    initMap() { if (this._map || !this.$refs.mapContainer) return; if (!window.L) { window.ensureMaps?.().then(() => this.initMap()); return; } this._map = window.L.map(this.$refs.mapContainer).setView([this.lat, this.lng], 15); window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(this._map); this._userMarker = window.L.marker([this.lat, this.lng]).addTo(this._map); },
    updateMap() { if (this._map && this.lat !== null && this.lng !== null) { this._userMarker?.setLatLng([this.lat, this.lng]); this._map.setView([this.lat, this.lng]); this.lastUpdated = `Updated ${new Date().toLocaleTimeString('id-ID')}`; } else if (this.mapVisible && this.lat !== null && this.lng !== null) { this.$nextTick(() => this.initMap()); } },
    onCoordsUpdated(detail) { if (detail?.latitude && detail?.longitude) this.setCoordinates(detail.latitude, detail.longitude); },
    refreshLocation() { const parent = this.$el.closest('[wire\\:id]')?._x_dataStack?.[0]; parent?.captureGps?.(); },
});

// Marker halaman: [data-*-charts-root] → Chart; #employeeOriginsMap /
// [data-leaflet-map] / #map_in / #map_out → Leaflet.
const bootLazyLibs = () => {
    if (document.querySelector('[data-analytics-charts-root], [data-dashboard-charts-root]')) {
        window.ensureCharts();
    }

    if (document.querySelector('#employeeOriginsMap, [data-leaflet-map], #map_in, #map_out')) {
        window.ensureMaps();
    }
};

bootLazyLibs();
document.addEventListener('DOMContentLoaded', bootLazyLibs);
document.addEventListener('livewire:navigated', bootLazyLibs);

// Resolve @theme design token untuk runtime. Canvas / Chart.js TIDAK
// me-resolve CSS custom properties sendiri — semua warna runtime harus
// lewat token (token-only rule, design.md), bukan hex hardcode.
window.cssVar = (name, fallback = '') => {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value || fallback;
};

// Ubah warna token (#hex / rgb(...)) menjadi rgba() dengan alpha — untuk
// canvas gradient / shadow yang butuh nilai ter-resolve.
window.colorWithAlpha = (color, alpha) => {
    const c = String(color || '').trim();
    const hex = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(c);

    if (hex) {
        return `rgba(${parseInt(hex[1], 16)}, ${parseInt(hex[2], 16)}, ${parseInt(hex[3], 16)}, ${alpha})`;
    }

    const rgb = /^rgba?\(([\d.]+)[ ,]+([\d.]+)[ ,]+([\d.]+)/i.exec(c);

    if (rgb) {
        return `rgba(${rgb[1]}, ${rgb[2]}, ${rgb[3]}, ${alpha})`;
    }

    return c;
};

const pasPapanAlertLabels = () => ({
    confirm: window.PasPapanAlertLabels?.confirm || "Confirm",
    cancel: window.PasPapanAlertLabels?.cancel || "Cancel",
    confirmTitle: window.PasPapanAlertLabels?.confirmTitle || "Are you sure?",
    noticeTitle: window.PasPapanAlertLabels?.noticeTitle || "Notice",
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
    popup: "!w-[min(28rem,calc(100vw-2rem))] !rounded-[1.35rem] !border !border-slate-200 !bg-white !px-5 !py-6 !text-slate-950 swal-alert-popup   ",
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
        popup: "!bg-white  !text-slate-900  !rounded-2xl swal-alert-toast !border !border-slate-100 /70 !px-4 !py-3 !w-auto !max-w-[92vw] !mx-auto !mt-4",
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

// Inisialisasi satu elemen select menjadi TomSelect. getOrCreate: kalau select
// sudah punya instance (mis. safety net initUiPickers duluan sebelum Alpine
// factory dievaluasi), return instance yang ada — pemanggil tetap bisa
// register change handler tanpa double-init.
const getOrCreateTomSelect = (select, { placeholder }) => {
    let ts = select.tomselect;

    if (!ts) {
        // dropdownParent=body (opt-in via data attribute dari x-user.tom-select-user):
        // dropdown dirender ke <body> supaya tidak terjebak stacking context
        // ancestor (mis. .user-list-card position:relative+z-index:0 → card
        // berikutnya menutup dropdown walau z-index 99999). Default (tanpa
        // attribute) tetap in-wrapper — perilaku admin tak berubah.
        const dropdownParent = select.getAttribute('data-tomselect-dropdown-parent') === 'body'
            ? document.body
            : null;

        ts = new TomSelect(select, {
            placeholder: placeholder || 'Select an option',
            maxOptions: null,
            allowEmptyOption: true,
            clearable: select.hasAttribute('data-clearable') ? select.getAttribute('data-clearable') !== 'false' : false,
            ...(dropdownParent ? { dropdownParent } : {}),
        });
        select.tomselect = ts;
    }

    return ts;
};

// Nilai awal widget: entangle/x-model (initial) lebih diutamakan. Saat
// KOSONG jangan fallback ke apa pun — biarkan placeholder tampil hingga user
// memilih. Catatan: jangan fallback ke select.value / option[selected] karena
// TomSelect MENANDAI option[selected] sendiri saat sync (nilai bisa terkunci
// ke opsi yang salah), dan scope.value (entangle atau @js($selected)) sudah
// menjadi source of truth di x-data komponen.
const resolveInitial = (initial) => {
    if (initial !== null && initial !== undefined && initial !== '') {
        return initial;
    }

    return null;
};

// Akses scope Alpine (x-data wrapper) secara LAZY. Dipanggil dari dalam
// handler change dan saat init — kalau Alpine belum siap (module app.js
// selesai sebelum Livewire/Alpine boot), return null dan dicoba lagi nanti
// (morph.updated / livewire:init memanggil initUiPickers ulang).
const resolveAlpineScope = (el) => {
    if (!el || typeof window.Alpine === 'undefined') return null;

    try {
        return Alpine.$data(el);
    } catch (e) {
        return null;
    }
};

// ─── Flatpickr initializer ─────────────────────────────────────────────
// Scans for [data-ui-picker] elements (dan [data-ui-picker-static] legacy
// admin) and initializes flatpickr on each. Safe to call multiple times
// (skips already-initialized elements).
//
// Parse server value (Y-m-d / range "Y-m-d - Y-m-d") menjadi defaultDate —
// flatpickr memakai dateFormat 'd M Y' sehingga value mentah server tidak
// terbaca sebagai tanggal (input tampil mentah + kalender buka bulan salah).
const parseServerDate = (v) => {
    if (!v) return undefined;

    const s = String(v).trim();

    const toDate = (str) => {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(str);

        return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
    };

    // Range: "YYYY-MM-DD - YYYY-MM-DD" (apply-leave date-range).
    // Separator = dash dengan spasi di kedua sisi — jangan split dash di dalam
    // tanggal (YYYY-MM-DD) itu sendiri.
    const parts = s.split(/\s+-\s+/).map(toDate);

    if (parts.length === 2 && parts[0] && parts[1]) {
        return parts;
    }

    return toDate(s) || undefined;
};

const initFlatpickr = (root = document) => {
    root.querySelectorAll(
        '[data-ui-picker]:not([data-flatpickr-inited]), [data-ui-picker-static]:not([data-flatpickr-inited])'
    ).forEach((el) => {
        // mode: atribut baru data-ui-picker, atau data-ui-picker-static legacy
        // (dipakai input admin type="date" — bermakna flatpickr static date).
        const mode = el.getAttribute('data-ui-picker') || 'date';

        // Input admin masih type="date" — flatpickr menulis value berformat
        // 'd M Y' yang tidak valid untuk native date input (value jadi kosong
        // di browser). Konversi ke text dulu, sama seperti native-date-field.
        // (Defensif untuk semua elemen: pemakaian data-ui-picker yang lain sudah
        // merender type="text" dari awal, jadi ini hanya berdampak pada input
        // data-ui-picker-static legacy.)
        if (el.type === 'date') {
            el.type = 'text';
        }
        const minDate = el.getAttribute('min') || null;
        const maxDate = el.getAttribute('max') || null;
        const isRange = mode === 'date-range';

        // Destroy existing instance sebelum init ulang. Livewire morph bisa
        // melepas atribut data-flatpickr-inited pada node yang SAMA (node
        // dipertahankan, atribut di-patch) — tanpa destroy, kalender ganda
        // menumpuk di body (instance lama tidak pernah dibersihkan).
        if (el._flatpickr) {
            try {
                el._flatpickr.destroy();
            } catch (e) { /* ignore */ }
        }

        try {
            const fp = flatpickr(el, {
                // Format per mode: time harus 'H:i' (bukan 'd M Y') supaya value
                // yang dikirim ke Livewire lolos validasi date_format:H:i.
                // Regresi lama: semua mode memakai 'd M Y' → time picker mengirim
                // tanggal → submit lembur/WFH selalu gagal validasi.
                dateFormat: mode === 'time' ? 'H:i' : mode === 'datetime' ? 'd M Y H:i' : 'd M Y',
                time_24hr: true,
                allowInput: false,
                mode: isRange ? 'range' : 'single',
                enableTime: mode === 'datetime' || mode === 'time',
                noCalendar: mode === 'time',
                monthSelectorType: 'dropdown',
                disableMobile: true,
                // static: true → kalender dirender DI DALAM .flatpickr-wrapper
                // (persis di bawah input), bukan di-append ke document.body.
                // Base flatpickr CSS (posisi absolute + top) kini di-load, dan
                // overrides (flatpickr-overrides.css: .flatpickr-wrapper,
                // .flatpickr-calendar.static) menyetel top: calc(100% + 0.375rem).
                static: true,
                // "below" → kalender tidak pernah flip ke atas input; static
                // mode menentukan posisi via CSS (top: calc(100% + 0.375rem)),
                // opsi ini menjaga kelas arrow tetap konsisten (arrowTop).
                position: 'below',
                minDate: minDate || undefined,
                maxDate: maxDate || undefined,
                defaultDate: parseServerDate(el.value),
                onChange: function (selectedDates, dateStr) {
                    // Date-range: sync hidden inputs (#from / #to) in Y-m-d format
                    if (isRange) {
                        const fromSel = el.getAttribute('data-ui-range-from');
                        const toSel = el.getAttribute('data-ui-range-to');
                        if (fromSel && selectedDates[0]) {
                            document.querySelector(fromSel).value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                        }
                        if (toSel && selectedDates[1]) {
                            document.querySelector(toSel).value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                        }
                    }
                    // Trigger Livewire model update
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                },
            });

            // Tampilkan nilai awal server dalam format 'd M Y' di input.
            // Hanya untuk single date — mode range dibiarkan via defaultDate
            // (setDate pada range bisa menimpa hidden #from/#to secara tidak
            // sengaja lewat format ulang).
            if (!isRange && el.value && /^\d{4}-\d{2}-\d{2}$/.test(String(el.value).trim())) {
                const serverDate = parseServerDate(el.value);

                if (serverDate instanceof Date) {
                    // setDate(false) = tanpa trigger onChange → hidden input & model aman
                    fp.setDate(serverDate, false);
                }
            }

            el.setAttribute('data-flatpickr-inited', 'true');
        } catch (e) {
            console.warn('Flatpickr init failed for', el, e);
        }
    });
};

// Initial run after DOM ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        initFlatpickr();
        // Safety net untuk race Alpine-vs-module: tom-select yang x-data-nya
        // dievaluasi Alpine SEBELUM window.tomSelectInput terdefinisi (module
        // app.js deferred) dirender sebagai {} — select native tetap harus
        // di-upgrade ke TomSelect di sini. Idempoten (skip el.tomselect).
        initUiPickers();
    }, 100);
});    // Halaman NON-Livewire (login/guest/blade statis yang memakai tom-select):
    // livewire:init / morph.updated tidak pernah fire — re-init begitu Alpine
    // selesai boot supaya scope (value: entangle atau @js($selected)) tersedia
    // dan nilai awal diterapkan. Idempoten.
    document.addEventListener('alpine:initialized', () => {
        setTimeout(initUiPickers, 50);
    });

    // Re-init after Livewire updates (component re-renders)
    document.addEventListener('livewire:init', () => {
        // Livewire + Alpine sudah pasti boot di sini — init ulang pickers
        // supaya model (entangle/x-model) yang baru tersedia ikut dibaca
        // (kasus initUiPickers pertama di DOMContentLoaded terjadi sebelum
        // Alpine siap → scope null). Idempoten.
        setTimeout(initUiPickers, 50);

        Livewire.hook('morph.updated', () => {
            // initUiPickers memanggil initFlatpickr di dalamnya + upgrade
            // tom-select baru hasil re-render.
            setTimeout(initUiPickers, 50);
        });

    // Saat Livewire morph MENGHAPUS elemen input, instance flatpickr lama
    // ikut mati bersama node — tetapi .flatpickr-calendar-nya tertinggal
    // yatim di body → kalender dobel. Destroy eksplisit di sini membersihkannya.
    Livewire.hook('morph.removed', ({ el }) => {
        if (el._flatpickr) {
            try {
                el._flatpickr.destroy();
            } catch (e) { /* ignore */ }
        }

        // Static mode: flatpickr membungkus input dalam div.flatpickr-wrapper.
        // Morph menghapus WRAPPER (bukan input di dalamnya) → hook di atas tidak
        // kena. Destroy instance picker di dalam wrapper agar tidak jadi zombie
        // (event listener + closure bocor di tiap re-render Livewire).
        if (el.querySelector) {
            const picker = el.querySelector('[data-ui-picker], [data-ui-picker-static]');
            if (picker && picker._flatpickr) {
                try {
                    picker._flatpickr.destroy();
                } catch (e) { /* ignore */ }
            }
        }
    });
});

// ─── UI pickers initializer (modal-aware) ─────────────────────────────
// Initializes flatpickr ([data-ui-picker]) and tom-select fields inside a
// container that may have been rendered after the initial page load
// (e.g. modal content opened via x-teleport). Follows the same init
// pattern as initFlatpickr / tomSelectInput: already-initialized
// elements are skipped, so calling this repeatedly is safe.
const initUiPickers = (root = document) => {
    const container = root instanceof Element ? root : document;

    initFlatpickr(container);

    // SATU-SATUNYA jalur inisialisasi TomSelect (fix race 2026-08-11):
    // sebelumnya init ada dua jalur (Alpine x-data factory + safety net ini)
    // yang saling race — kalau Alpine mengevaluasi x-data sebelum module
    // app.js selesai, hasilnya `{}` dan widget di-init tanpa handler sync.
    // Sekarang x-data blade HANYA membawa model (`{ value: @entangle }`),
    // dan seluruh init + sinkronisasi dua arah ada di sini. Idempoten
    // (el.tomselect / el.__tsSync), dipanggil ulang oleh morph.updated /
    // livewire:init / DOMContentLoaded.
    container.querySelectorAll('[data-ui-tomselect]').forEach((el) => {
        const wrapper = el.closest('[data-ui-tomselect-root]') || el.parentElement;

        const ts = getOrCreateTomSelect(el, {
            placeholder: el.getAttribute('placeholder') || 'Select an option',
        });

        // Nilai awal dari model (entangle/x-model via scope Alpine). Saat
        // scope belum siap (Alpine belum boot — initUiPickers pertama di
        // DOMContentLoaded), biarkan placeholder; livewire:init / morph
        // memanggil initUiPickers lagi dan set nilai dari model.
        const scope = resolveAlpineScope(wrapper);
        const modelValue = resolveInitial(scope ? scope.value : null);
        if (modelValue !== null) {
            ts.setValue(modelValue, true);
        } else if (ts.getValue() !== null && ts.getValue() !== '' && !el.querySelector('option[selected]')) {
            // Model kosong tapi widget punya nilai — TomSelect meng-adopsi
            // nilai browser default saat konstruksi (select native tanpa
            // option[selected] otomatis terpilih opsi pertama) atau sisa run
            // init sebelumnya. Reset ke placeholder supaya filter tidak
            // menampilkan opsi pertama seolah terpilih ("All Divisions"
            // berubah jadi divisi pertama).
            //
            // Guard option[selected]: TomSelect menandai option.selected
            // (PROPERTY, bukan attribute) saat sync — attribute hanya dibuat
            // blade @selected (via :selected prop). Kalau ada, nilai itu nilai
            // server asli dan harus dipertahankan (mis. halaman non-Livewire
            // yang scope Alpine-nya tidak pernah tersedia).
            ts.clear(true);
        }

        if (!el.__tsSync) {
            el.__tsSync = true;
            const submitOnChange = el.hasAttribute('data-submit-on-change');

            // TomSelect → model: pilihan user menulis ke entangle/x-model
            // (scope dibaca LAZY setiap change — bukan di-capture saat init).
            ts.on('change', () => {
                const s = resolveAlpineScope(wrapper);
                if (s) {
                    s.value = ts.getValue();
                }
                // submitOnChange: form GET non-Livewire (analytics-dashboard
                // month/year) — pilihan langsung submit agar query terkirim.
                if (submitOnChange && el.form) {
                    el.form.submit();
                }
            });
        }
    });
};

window.initUiPickers = initUiPickers;
