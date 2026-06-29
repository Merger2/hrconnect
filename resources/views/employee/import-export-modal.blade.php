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


