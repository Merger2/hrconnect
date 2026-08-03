import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import "../css/vendor/flatpickr-overrides.css";
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
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

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

// ─── Flatpickr initializer ─────────────────────────────────────────────
// Scans for [data-ui-picker] elements and initializes flatpickr on each.
// Safe to call multiple times (skips already-initialized elements).
const initFlatpickr = () => {
    document.querySelectorAll('[data-ui-picker]:not([data-flatpickr-inited])').forEach((el) => {
        const mode = el.getAttribute('data-ui-picker') || 'date';
        const minDate = el.getAttribute('min') || null;
        const maxDate = el.getAttribute('max') || null;

        try {
            flatpickr(el, {
                dateFormat: 'd M Y',
                allowInput: false,
                enableTime: mode === 'datetime' || mode === 'time',
                noCalendar: mode === 'time',
                monthSelectorType: 'dropdown',
                disableMobile: true,
                minDate: minDate || undefined,
                maxDate: maxDate || undefined,
                onChange: function (selectedDates, dateStr) {
                    // Trigger Livewire model update
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                },
            });

            el.setAttribute('data-flatpickr-inited', 'true');
        } catch (e) {
            console.warn('Flatpickr init failed for', el, e);
        }
    });
};

// Initial run after DOM ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initFlatpickr, 100);
});

// Re-init after Livewire updates (component re-renders)
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        setTimeout(initFlatpickr, 50);
    });
});

