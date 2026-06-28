<x-layouts::app.sidebar>
    <div x-data="leaveApply()">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('Apply Leave') }}</h1>
                <p class="mt-1 text-sm text-on-surface-variant">{{ __('Submit a new leave request') }}</p>
            </div>
            <x-button variant="secondary" href="{{ route('leaves.index') }}" wire:navigate icon="arrow_back">
                {{ __('Back') }}
            </x-button>
        </div>

        <div class="mx-auto max-w-2xl">
            <x-app.panel class="p-6">
                {{-- Leave Type --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Leave Type') }}</label>
                    <select x-model="form.leave_type_id" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="">{{ __('Pilih tipe cuti...') }}</option>
                        <template x-for="lt in leaveTypes" :key="lt.id">
                            <option :value="lt.id" x-text="lt.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Dates --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Start Date') }}</label>
                        <input type="date" x-model="form.start_date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('End Date') }}</label>
                        <input type="date" x-model="form.end_date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>

                {{-- Day Type --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Duration') }}</label>
                    <select x-model="form.day_type" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="full_day">{{ __('Full Day') }}</option>
                        <option value="morning">{{ __('Morning Only') }}</option>
                        <option value="afternoon">{{ __('Afternoon Only') }}</option>
                    </select>
                </div>

                {{-- Reason --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Reason') }}</label>
                    <textarea x-model="form.reason" rows="4" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" placeholder="{{ __('Describe your leave reason...') }}"></textarea>
                    <p class="mt-1 text-xs text-on-surface-variant" x-text="form.reason.length + ' / 500'"></p>
                </div>

                {{-- Error --}}
                <div x-show="error" class="mb-4 rounded-xl bg-error/10 p-4 text-sm text-error" x-text="error"></div>

                {{-- Actions --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <x-button variant="secondary" href="{{ route('leaves.index') }}" wire:navigate>{{ __('Cancel') }}</x-button>
                    <x-button variant="primary" @click="submit()" x-bind:disabled="submitting">
                        <span x-show="!submitting">{{ __('Submit Request') }}</span>
                        <span x-show="submitting" class="material-symbols-outlined animate-spin">progress_activity</span>
                    </x-button>
                </div>
            </x-app.panel>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('leaveApply', () => ({
                leaveTypes: [],
                form: {
                    leave_type_id: '',
                    start_date: '',
                    end_date: '',
                    day_type: 'full_day',
                    reason: '',
                },
                submitting: false,
                error: '',

                init() {
                    this.fetchLeaveTypes();
                },

                async fetchLeaveTypes() {
                    try {
                        const res = await fetch('/api/v1/leave/quota', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.leaveTypes = json.data.map(q => q.leave_type).filter(Boolean);
                        }
                    } catch { /* silent */ }
                },

                validate() {
                    if (!this.form.leave_type_id) { this.error = '{{ __('Pilih tipe cuti') }}'; return false; }
                    if (!this.form.start_date) { this.error = '{{ __('Pilih tanggal mulai') }}'; return false; }
                    if (!this.form.end_date) { this.error = '{{ __('Pilih tanggal selesai') }}'; return false; }
                    if (this.form.start_date > this.form.end_date) { this.error = '{{ __('Tanggal selesai harus setelah tanggal mulai') }}'; return false; }
                    if (this.form.reason.length < 10) { this.error = '{{ __('Alasan minimal 10 karakter') }}'; return false; }
                    this.error = '';
                    return true;
                },

                async submit() {
                    if (!this.validate()) return;
                    this.submitting = true;
                    try {
                        const res = await fetch('/api/v1/leave', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify(this.form),
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Cuti diajukan') }}' });
                            window.location.href = '{{ route('leaves.index') }}';
                        } else {
                            this.error = json.message || '{{ __('Gagal mengajukan cuti') }}';
                        }
                    } catch {
                        this.error = '{{ __('Koneksi error') }}';
                    }
                    finally { this.submitting = false; }
                },
            }));
        });
    </script>
</x-layouts::app.sidebar>
