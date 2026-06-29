const isPickerDetached = (instance) => {
    if (!instance) return true;
    if (!instance.input || !instance.calendarContainer) return true;
    if (!document.documentElement.contains(instance.input)) return true;
    if (instance.config?.altInput && !document.documentElement.contains(instance.altInput)) return true;
    return false;
};

const destroyPicker = (input) => {
    if (!input?._flatpickr) {
        if (input && input._flatpickr === null) {
            try { delete input._flatpickr; } catch { input._flatpickr = undefined; }
        }
        return;
    }
    try { input._flatpickr.destroy(); } catch {}
    try { delete input._flatpickr; } catch { input._flatpickr = undefined; }
};

const enforceReadonly = (instance, input) => {
    [input, instance?.input, instance?.altInput].forEach((field) => {
        if (!field) return;
        field.readOnly = true;
        field.setAttribute('readonly', 'readonly');
        field.setAttribute('inputmode', 'none');
        field.setAttribute('autocomplete', 'off');
        field.setAttribute('aria-haspopup', 'dialog');
        if (!field.dataset.uiPickerGuard) {
            field.dataset.uiPickerGuard = 'true';
            field.addEventListener('keydown', (event) => {
                if (!['Tab', 'Escape', 'Enter', ' ', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                    event.preventDefault();
                }
            });
        }
    });
};

const syncValue = (instance, input) => {
    const value = input?.value || input?.getAttribute?.('value') || '';
    if (instance?.input && value && instance.input.value !== value) {
        instance.setDate(value, false, instance.config?.dateFormat);
    }
};

const syncWidth = (instance, input) => {
    const wrapper = instance?.altInput?.closest?.('.flatpickr-wrapper')
        || instance?.input?.closest?.('.flatpickr-wrapper')
        || input?.closest?.('.flatpickr-wrapper');
    if (wrapper) { wrapper.style.display = 'block'; wrapper.style.width = '100%'; }
    [instance?.altInput, instance?.input, input].forEach((field) => {
        if (field) { field.style.display = 'block'; field.style.width = '100%'; }
    });
};

const normalizeCalendar = (instance) => {
    const calendar = instance?.calendarContainer;
    if (!calendar) return;
    const months = calendar.querySelector('.flatpickr-months');
    const month = calendar.querySelector('.flatpickr-month');
    if (months) { months.style.height = '48px'; months.style.minHeight = '48px'; }
    if (month) { month.style.height = '48px'; }
};

const initUiPickers = (root = document) => {
    if (!window.flatpickr) return;

    const inputs = [];
    if (root.matches?.('[data-ui-picker]')) inputs.push(root);
    root.querySelectorAll?.('[data-ui-picker]').forEach((input) => inputs.push(input));

    inputs.forEach((input) => {
        if (input.dataset.uiPickerInit === 'true') return;
        input.dataset.uiPickerInit = 'true';

        if (input._flatpickr === null) destroyPicker(input);

        const mode = input.dataset.uiPicker;
        const dialogPanel = input.closest?.('[role="dialog"]');
        const staticPicker = dialogPanel !== null;

        if (input._flatpickr) {
            if (isPickerDetached(input._flatpickr)) {
                destroyPicker(input);
            } else {
                syncValue(input._flatpickr, input);
                syncWidth(input._flatpickr, input);
                return;
            }
        }

        const config = {
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            static: staticPicker,
            onChange: (selectedDates, dateStr, instance) => {
                if (mode === 'date-range') return;
                input.value = dateStr;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                setTimeout(() => instance.close(), 80);
            },
            onReady: (selectedDates, dateStr, instance) => {
                enforceReadonly(instance, input);
                syncValue(instance, input);
                syncWidth(instance, input);
                normalizeCalendar(instance);
            },
            onOpen: () => {
                enforceReadonly(input._flatpickr, input);
                syncValue(input._flatpickr, input);
                syncWidth(input._flatpickr, input);
                normalizeCalendar(input._flatpickr);
            },
            onMonthChange: () => normalizeCalendar(input._flatpickr),
            onYearChange: () => normalizeCalendar(input._flatpickr),
        };

        if (input.min) config.minDate = input.min;
        if (input.max) config.maxDate = input.max;

        if (!staticPicker) {
            config.appendTo = input.closest?.('.modal-content') || document.body;
        }

        if (mode === 'time') {
            Object.assign(config, { enableTime: true, noCalendar: true, dateFormat: 'H:i', time_24hr: true });
        } else if (mode === 'datetime') {
            Object.assign(config, { enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true });
        } else {
            Object.assign(config, { dateFormat: 'Y-m-d' });
        }

        const picker = window.flatpickr(input, config);
        enforceReadonly(picker, input);
        syncWidth(picker, input);
        normalizeCalendar(picker);
    });
};

window.initUiPickers = initUiPickers;

const watchPickerMounts = () => {
    initUiPickers();
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) initUiPickers(node);
            });
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
};

document.addEventListener('pointerdown', (event) => {
    const input = event.target.closest?.('[data-ui-picker]');
    if (input && input._flatpickr && isPickerDetached(input._flatpickr)) {
        destroyPicker(input);
        initUiPickers(input);
    }
}, true);

export { initUiPickers, watchPickerMounts };
