<x-confirm-modal
    name="terminate-employee"
    variant="danger"
    :title="__('Terminate Employee')"
    :message="__('Are you sure you want to terminate this employee? This action cannot be undone.')"
    :confirmLabel="__('Terminate')"
>
    <div class="space-y-3 text-left">
        <div class="rounded-lg bg-warning/10 p-3 text-sm text-warning">
            <p class="font-medium">{{ __('Warning') }}</p>
            <p class="mt-1 text-xs">{{ __('Terminating an employee will revoke all system access and mark their status as terminated. Use this only for involuntary separations.') }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink">{{ __('Termination Type') }} *</label>
            <select x-model="terminateForm.type"
                class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
                <option value="phk">{{ __('PHK (Layoff)') }}</option>
                <option value="disciplinary">{{ __('Disciplinary Termination') }}</option>
                <option value="mutual">{{ __('Mutual Agreement') }}</option>
                <option value="contract_end">{{ __('Contract End') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink">{{ __('Reason') }} *</label>
            <textarea x-model="terminateForm.reason" rows="3"
                class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink"
                placeholder="{{ __('Explain the reason for termination...') }}"></textarea>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink">{{ __('Effective Date') }} *</label>
            <input type="date" x-model="terminateForm.date"
                class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink">
        </div>

        <div x-show="terminateError" x-cloak class="rounded-xl bg-error/10 p-3 text-sm text-error">
            <p x-text="terminateError"></p>
        </div>
    </div>

    <button @click="submitTerminate(); $el.closest('[x-data]').__x.$data.open = false"
        x-bind:disabled="terminateLoading"
        class="bg-error text-white hover:opacity-90 rounded-xl px-5 py-2.5 text-sm font-semibold transition-colors disabled:opacity-40">
        <span x-show="!terminateLoading">{{ __('Confirm Termination') }}</span>
        <span x-show="terminateLoading" x-cloak>{{ __('Processing...') }}</span>
    </button>
</x-confirm-modal>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('terminateEmployeeForm', () => ({
            terminateForm: {
                type: 'phk',
                reason: '',
                date: new Date().toISOString().slice(0, 10),
            },
            terminateError: '',
            terminateLoading: false,
            selectedEmployee: null,

            init() {
                this.$watch('selectedEmployee', (val) => {
                    if (val) {
                        this.terminateForm.date = new Date().toISOString().slice(0, 10);
                        this.terminateForm.reason = '';
                        this.terminateForm.type = 'phk';
                        this.terminateError = '';
                    }
                });
            },

            async submitTerminate() {
                if (!this.selectedEmployee) return;
                this.terminateError = '';
                this.terminateLoading = true;
                try {
                    const res = await fetch(`/api/v1/employees/${this.selectedEmployee.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            status: 'terminated',
                            termination_type: this.terminateForm.type,
                            termination_reason: this.terminateForm.reason,
                            resign_date: this.terminateForm.date,
                        }),
                    });

                    if (!res.ok) {
                        const err = await res.json();
                        this.terminateError = err.message || '{{ __('Failed to terminate') }}';
                        return;
                    }

                    this.$dispatch('close-modal', 'terminate-employee');
                    this.selectedEmployee = null;
                    if (window.employeesIndexInstance) {
                        window.employeesIndexInstance.fetchEmployees();
                    }
                } catch (e) {
                    this.terminateError = '{{ __('An error occurred') }}';
                } finally {
                    this.terminateLoading = false;
                }
            },
        }));
    });
</script>
