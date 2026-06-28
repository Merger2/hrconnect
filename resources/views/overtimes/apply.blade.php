<x-layouts::app.sidebar>
    <div x-data="overtimeApply()">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('Request Overtime') }}</h1>
                <p class="mt-1 text-sm text-on-surface-variant">{{ __('Submit a new overtime request') }}</p>
            </div>
            <x-button variant="secondary" href="{{ route('overtimes.index') }}" wire:navigate icon="arrow_back">
                {{ __('Back') }}
            </x-button>
        </div>

        <div class="mx-auto max-w-2xl">
            <x-app.panel class="p-6">
                {{-- Date --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Date') }}</label>
                    <input type="date" x-model="form.date" :min="today()" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                </div>

                {{-- Time --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Start Time') }}</label>
                        <input type="time" x-model="form.start_time" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('End Time') }}</label>
                        <input type="time" x-model="form.end_time" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>

                {{-- Duration hint --}}
                <p x-show="form.start_time && form.end_time" class="mb-4 text-xs text-on-surface-variant">
                    {{ __('Duration') }}: <span class="font-medium text-ink" x-text="calcDuration()"></span>
                </p>

                {{-- Reason --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Reason') }}</label>
                    <textarea x-model="form.description" rows="4" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" placeholder="{{ __('Describe the reason for overtime...') }}"></textarea>
                </div>

                {{-- Error --}}
                <div x-show="error" class="mb-4 rounded-xl bg-error/10 p-4 text-sm text-error" x-text="error"></div>

                {{-- Actions --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <x-button variant="secondary" href="{{ route('overtimes.index') }}" wire:navigate>{{ __('Cancel') }}</x-button>
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
            Alpine.data('overtimeApply', () => ({
                form: {
                    date: '',
                    start_time: '',
                    end_time: '',
                    description: '',
                },
                submitting: false,
                error: '',

                init() {
                    this.form.date = this.today();
                },

                today() {
                    return new Date().toISOString().split('T')[0];
                },

                calcDuration() {
                    if (!this.form.start_time || !this.form.end_time) return '';
                    const [sh, sm] = this.form.start_time.split(':').map(Number);
                    const [eh, em] = this.form.end_time.split(':').map(Number);
                    let mins = (eh * 60 + em) - (sh * 60 + sm);
                    if (mins < 0) mins += 1440;
                    return (mins / 60).toFixed(1) + ' {{ __('hours') }}';
                },

                validate() {
                    if (!this.form.date) { this.error = '{{ __('Pilih tanggal') }}'; return false; }
                    if (!this.form.start_time) { this.error = '{{ __('Pilih jam mulai') }}'; return false; }
                    if (!this.form.end_time) { this.error = '{{ __('Pilih jam selesai') }}'; return false; }
                    if (this.form.start_time === this.form.end_time) { this.error = '{{ __('Jam mulai dan selesai tidak boleh sama') }}'; return false; }
                    if (this.form.description.length < 10) { this.error = '{{ __('Alasan minimal 10 karakter') }}'; return false; }
                    this.error = '';
                    return true;
                },

                async submit() {
                    if (!this.validate()) return;
                    this.submitting = true;
                    try {
                        const res = await fetch('/api/v1/overtime', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify(this.form),
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Lembur diajukan') }}' });
                            window.location.href = '{{ route('overtimes.index') }}';
                        } else {
                            this.error = json.message || '{{ __('Gagal mengajukan lembur') }}';
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
