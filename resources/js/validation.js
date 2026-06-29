const labels = {
    required: 'Bagian ini wajib diisi.',
    email: 'Format email tidak valid.',
    url: 'Format URL tidak valid.',
    pattern: 'Format tidak sesuai.',
    minLength: 'Minimal :min karakter.',
    maxLength: 'Maksimal :max karakter.',
    rangeUnderflow: 'Nilai minimal :min.',
    rangeOverflow: 'Nilai maksimal :max.',
    invalid: 'Input tidak valid.',
    summary: 'Ada input yang perlu diperbaiki.',
};

const interpolate = (message, replacements = {}) =>
    Object.entries(replacements).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), message);

const validationMessageFor = (control) => {
    const v = control.validity;
    if (v.valueMissing) return labels.required;
    if (v.typeMismatch && control.type === 'email') return labels.email;
    if (v.typeMismatch && control.type === 'url') return labels.url;
    if (v.patternMismatch) return labels.pattern;
    if (v.tooShort) return interpolate(labels.minLength, { min: control.minLength });
    if (v.tooLong) return interpolate(labels.maxLength, { max: control.maxLength });
    if (v.rangeUnderflow) return interpolate(labels.rangeUnderflow, { min: control.min });
    if (v.rangeOverflow) return interpolate(labels.rangeOverflow, { max: control.max });
    return labels.invalid;
};

const errorIdFor = (control) => {
    if (!control.id) control.id = `field-${Math.random().toString(36).slice(2, 9)}`;
    return `${control.id}-error`;
};

const validatableControls = (form) =>
    Array.from(form.querySelectorAll('input, select, textarea'))
        .filter((c) => !c.disabled && c.type !== 'hidden' && !c.matches('[data-no-validate]'));

const clearValidation = (control) => {
    const eid = errorIdFor(control);
    control.classList.remove('border-error');
    control.removeAttribute('aria-invalid');
    const describedBy = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter((x) => x && x !== eid);
    describedBy.length ? control.setAttribute('aria-describedby', describedBy.join(' ')) : control.removeAttribute('aria-describedby');
    document.getElementById(eid)?.remove();
};

const renderValidation = (control) => {
    const eid = errorIdFor(control);
    const container = control.closest('.space-y-1, .space-y-1\\.5, .space-y-2, label, div') || control.parentElement;
    const message = validationMessageFor(control);

    control.classList.add('border-error');
    control.setAttribute('aria-invalid', 'true');

    const describedBy = new Set((control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
    describedBy.add(eid);
    control.setAttribute('aria-describedby', Array.from(describedBy).join(' '));

    let error = document.getElementById(eid);
    if (!error) {
        error = document.createElement('p');
        error.id = eid;
        error.className = 'mt-1 text-xs text-error';
        error.setAttribute('role', 'alert');
        container.appendChild(error);
    }
    error.textContent = message;
};

const validateForm = (form, event = null) => {
    const controls = validatableControls(form);
    controls.forEach(clearValidation);

    const invalid = controls.filter((c) => !c.validity.valid);
    invalid.forEach(renderValidation);

    if (!invalid.length) return true;

    event?.preventDefault();
    event?.stopImmediatePropagation();

    const first = invalid[0];
    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    requestAnimationFrame(() => first.focus({ preventScroll: true }));

    window.HRConnectAlert?.toast({ type: 'warning', text: labels.summary });
    return false;
};

const installValidation = () => {
    document.querySelectorAll('form:not([data-validation])').forEach((form) => {
        form.dataset.validation = 'true';

        form.addEventListener('submit', (event) => {
            if (event.submitter?.formNoValidate || event.defaultPrevented) return;
            validateForm(form, event);
        }, true);

        form.addEventListener('input', (event) => {
            const c = event.target;
            if (c instanceof HTMLInputElement || c instanceof HTMLSelectElement || c instanceof HTMLTextAreaElement) {
                if (c.validity.valid) clearValidation(c);
            }
        }, true);
    });
};

export { validateForm, installValidation };
