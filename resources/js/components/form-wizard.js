export default function formWizard(props = {}) {
    return {
        steps: props.steps || [],
        currentStep: props.currentStep ?? 0,
        nextLabel: props.nextLabel || 'Lanjutkan',
        submitLabel: props.submitLabel || 'Ajukan',
        submitting: false,
        error: '',
        direction: 'forward',

        get isFirstStep() {
            return this.currentStep === 0;
        },

        get isLastStep() {
            return this.currentStep === this.steps.length - 1;
        },

        get isSubmitting() {
            return this.submitting;
        },

        initWizard() {
            this.$nextTick(() => {
                this.focusCurrentStep();
                this.syncPanelHeight();
            });

            this.$watch('currentStep', () => {
                this.$nextTick(() => {
                    this.focusCurrentStep();
                    this.syncPanelHeight();
                    this.scrollStepperIntoView();
                });
            });
        },

        validateCurrentStep(silent = true) {
            if (typeof this.validateStep === 'function') {
                return this.validateStep(this.currentStep, { silent }) === true;
            }

            return true;
        },

        next() {
            if (this.isLastStep || !this.validateCurrentStep(false)) {
                return;
            }

            this.direction = 'forward';
            this.currentStep += 1;
        },

        prev() {
            if (this.isFirstStep) {
                return;
            }

            this.direction = 'backward';
            this.currentStep -= 1;
        },

        goToStep(stepIndex) {
            if (stepIndex === this.currentStep) {
                return;
            }

            if (!this.canJumpTo(stepIndex)) {
                return;
            }

            this.direction = stepIndex > this.currentStep ? 'forward' : 'backward';
            this.currentStep = stepIndex;
        },

        canJumpTo(stepIndex) {
            if (stepIndex <= this.currentStep) {
                return true;
            }

            for (let index = this.currentStep; index < stepIndex; index += 1) {
                if (typeof this.validateStep === 'function' && this.validateStep(index, { silent: true }) !== true) {
                    return false;
                }
            }

            return true;
        },

        async submitWizard() {
            if (!this.validateCurrentStep(false) || this.submitting) {
                return;
            }

            if (typeof this.submit === 'function') {
                await this.submit();
            }
        },

        handleWizardKeydown(event, cancelUrl = null) {
            if (event.key === 'Escape') {
                if (cancelUrl) {
                    window.location.href = cancelUrl;
                }
                return;
            }

            if (event.key !== 'Enter' || event.shiftKey) {
                return;
            }

            const tagName = event.target?.tagName?.toLowerCase();
            if (tagName === 'textarea' || event.target?.type === 'file') {
                return;
            }

            event.preventDefault();

            if (this.isLastStep) {
                this.submitWizard();
                return;
            }

            this.next();
        },

        focusCurrentStep() {
            const panel = this.getCurrentPanel();
            if (!panel) {
                return;
            }

            const focusTarget = panel.querySelector('[data-autofocus], input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])');
            focusTarget?.focus({ preventScroll: true });
        },

        syncPanelHeight() {
            const container = this.$refs.panelContainer;
            const panel = this.getCurrentPanel();

            if (!container || !panel) {
                return;
            }

            container.style.minHeight = `${panel.offsetHeight}px`;
        },

        getCurrentPanel() {
            return this.$refs.panelContainer?.querySelector(`[data-step-panel="${this.currentStep}"]`);
        },

        scrollStepperIntoView() {
            const active = this.$refs.stepper?.querySelector('[aria-current="step"]');
            active?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        },

        stepPanelClass(index) {
            const base = 'absolute inset-0 w-full p-5 transition duration-[var(--motion-duration-normal)] ease-[var(--motion-easing-standard)] md:p-6';

            if (index === this.currentStep) {
                return `${base} relative translate-x-0 opacity-100`;
            }

            if (index < this.currentStep) {
                return `${base} pointer-events-none -translate-x-6 opacity-0`;
            }

            return `${base} pointer-events-none translate-x-6 opacity-0`;
        },

        stepCircleClass(index) {
            if (index < this.currentStep) {
                return 'border-primary bg-primary text-on-primary shadow-soft';
            }

            if (index === this.currentStep) {
                return 'border-primary bg-primary/10 text-primary ring-4 ring-primary/10';
            }

            return 'border-outline-variant bg-surface-container-low text-on-surface-variant';
        },

        stepLabelClass(index) {
            if (index <= this.currentStep) {
                return 'text-primary';
            }

            return 'text-on-surface-variant';
        },

        connectorClass(index) {
            return index < this.currentStep ? 'bg-primary' : 'bg-outline-variant';
        },
    };
}

window.FormWizard = formWizard;