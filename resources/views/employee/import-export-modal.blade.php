<x-form-modal name="import-employees" size="lg">
    <x-slot:title>{{ __('Import Employees') }}</x-slot:title>

    <div class="space-y-4">
        <div class="rounded-lg bg-info/10 p-3 text-sm text-info">
            <p class="font-medium">{{ __('Format') }}</p>
            <p class="mt-1 text-xs">{{ __('Upload a CSV file with headers: Employee Number, Full Name, Email, NIK, Phone, Gender (L/P), Marital Status, Birth Date (Y-m-d), Join Date (Y-m-d), Department, Position, Employment Type, Salary Type, Education Level.') }}</p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink">{{ __('CSV File') }} *</label>
            <div class="flex items-center gap-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-outline-variant bg-canvas px-4 py-3 text-sm text-ink transition-colors hover:bg-surface-dim">
                    <span class="material-symbols-outlined text-lg">upload_file</span>
                    <span x-text="importFile ? importFile.name : '{{ __('Choose file...') }}'"></span>
                    <input type="file" accept=".csv" class="hidden" @change="importFile = $event.target.files[0]">
                </label>
                <a href="#" @click.prevent="downloadTemplate" class="text-sm font-medium text-info hover:underline">{{ __('Download template') }}</a>
            </div>
        </div>

        <div x-show="importError" x-cloak class="rounded-xl bg-error/10 p-3 text-sm text-error">
            <p x-text="importError"></p>
        </div>
    </div>

    <x-slot:actions>
        <button type="button" @click="$dispatch('close-modal', 'import-employees')"
            class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
            {{ __('Cancel') }}
        </button>
        <button @click="submitImport"
            x-bind:disabled="importLoading || !importFile"
            class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
            <span x-show="!importLoading">{{ __('Import') }}</span>
            <span x-show="importLoading" x-cloak>{{ __('Importing...') }}</span>
        </button>
    </x-slot:actions>
</x-form-modal>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('importEmployeesForm', () => ({
            importFile: null,
            importError: '',
            importLoading: false,

            downloadTemplate() {
                const headers = ['Employee Number', 'Full Name', 'Email', 'NIK', 'Phone', 'Gender', 'Marital Status', 'Birth Date', 'Join Date', 'Department', 'Position', 'Employment Type', 'Salary Type', 'Education Level'];
                const csv = headers.join(',');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'employee_import_template.csv';
                a.click();
                URL.revokeObjectURL(url);
            },

            async submitImport() {
                if (!this.importFile) return;
                this.importError = '';
                this.importLoading = true;
                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);

                    const res = await fetch('/api/v1/employees/import', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData,
                    });

                    if (!res.ok) {
                        const err = await res.json();
                        this.importError = err.message || '{{ __('Import failed') }}';
                        return;
                    }

                    this.$dispatch('close-modal', 'import-employees');
                    this.importFile = null;
                    if (window.employeesIndexInstance) {
                        window.employeesIndexInstance.fetchEmployees();
                    }
                } catch (e) {
                    this.importError = '{{ __('An error occurred') }}';
                } finally {
                    this.importLoading = false;
                }
            },
        }));
    });
</script>
