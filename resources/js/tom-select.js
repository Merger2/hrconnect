import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

window.TomSelect = TomSelect;

window.tomSelectInput = (options, placeholder, wireModel, disabled = false, livewireModel = null, submitOnChange = false, livewireSetLive = false, dropdownDirection = 'auto') => ({
    tomSelectInstance: null,
    options: options,
    value: wireModel,
    pendingValue: wireModel,
    disabled: disabled,
    livewireModel: livewireModel,
    submitOnChange: submitOnChange,
    livewireSetLive: livewireSetLive,
    dropdownDirection: dropdownDirection,
    tomSelectRetryCount: 0,
    destroyed: false,

    init() {
        if (this.destroyed || this.tomSelectInstance) return;

        if (!window.TomSelect) {
            if (this.tomSelectRetryCount < 20) {
                this.tomSelectRetryCount += 1;
                setTimeout(() => this.init(), 25);
            }
            return;
        }

        const config = {
            create: false,
            openOnFocus: true,
            closeAfterSelect: true,
            preload: true,
            maxOptions: 1000,
            shouldLoad: () => true,
            sortField: { field: '$order' },
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            placeholder: placeholder,
            onChange: (value) => {
                this.pendingValue = value;
                queueMicrotask(() => {
                    if (this.tomSelectInstance && !this.tomSelectInstance.isOpen) {
                        this.commitPendingValue();
                    }
                });
            },
            onDropdownOpen: () => {
                if (this.tomSelectInstance) this.tomSelectInstance.positionDropdown();
            },
            onFocus: () => {
                if (!this.tomSelectInstance || this.disabled) return;
                this.tomSelectInstance.refreshOptions(false);
                requestAnimationFrame(() => this.tomSelectInstance?.open());
            },
            onDropdownClose: () => { this.commitPendingValue(); },
            onBlur: () => { this.commitPendingValue(); },
        };

        if (this.options && this.options.length > 0) {
            config.options = this.options;
        }

        this.tomSelectInstance = new window.TomSelect(this.$refs.select, config);

        const openOptions = () => {
            if (this.disabled || !this.tomSelectInstance) return;
            this.tomSelectInstance.refreshOptions(false);
            requestAnimationFrame(() => this.tomSelectInstance?.open());
        };

        this.tomSelectInstance.control_input?.addEventListener('focus', openOptions);

        this.$watch('value', (newValue) => {
            if (!this.tomSelectInstance) return;
            this.pendingValue = newValue;
            const currentValue = this.tomSelectInstance.getValue();
            if (newValue != currentValue) {
                this.tomSelectInstance.setValue(newValue, true);
            }
        });

        if (this.hasValue(this.value)) {
            this.tomSelectInstance.setValue(this.value, true);
        }

        if (this.disabled) {
            this.tomSelectInstance.lock();
        }

        this.$watch('disabled', (isDisabled) => {
            if (!this.tomSelectInstance) return;
            isDisabled ? this.tomSelectInstance.lock() : this.tomSelectInstance.unlock();
        });
    },

    hasValue(value) {
        return value !== null && value !== undefined && value !== '';
    },

    commitPendingValue() {
        if (this.pendingValue == this.value) return;
        this.value = this.pendingValue;
        if (this.livewireModel && this.$wire) {
            this.$wire.set(this.livewireModel, this.pendingValue, this.livewireSetLive);
        }
    },

    destroy() {
        this.destroyed = true;
        if (this.tomSelectInstance) {
            this.tomSelectInstance.destroy();
            this.tomSelectInstance = null;
        }
    },
});
