<x-layouts::app.sidebar>
    <div x-data="leaveApply({{ Js::from(['leaveTypes' => $leaveTypes->toArray(), 'hasEmployee' => $employee !== null]) }})">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('Pengajuan Cuti') }}</h1>
                <p class="mt-1 text-sm text-on-surface-variant">{{ __('Ajukan cuti atau izin baru') }}</p>
            </div>
            <x-button variant="secondary" href="{{ route('leaves.index') }}" wire:navigate icon="arrow_back">
                {{ __('Kembali') }}
            </x-button>
        </div>

        <div class="mx-auto max-w-2xl">
            {{-- Quota Summary --}}
            @if ($balances->isNotEmpty())
                <div class="mb-4 grid grid-cols-1 gap-3">
                    @foreach ($balances as $b)
                        @php $lt = $b->leaveType; @endphp
                        @if ($lt)
                            <div class="flex items-center gap-4 rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-ink">{{ $lt->name }}</p>
                                    <p class="text-xs text-on-surface-variant">
                                        {{ __('Terpakai') }}: {{ (float) $b->used }}
                                        @if ($b->carry_forward_deadline && today()->lessThanOrEqualTo($b->carry_forward_deadline))
                                            &bull; {{ __('Dibawa') }}: {{ (float) $b->carry_forward }}
                                        @endif
                                        @if ($b->carry_forward_deadline)
                                            &bull; {{ __('Berlaku sampai') }} {{ \Illuminate\Support\Carbon::parse($b->carry_forward_deadline)->translatedFormat('d M Y') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-ink">{{ number_format($b->available(), 1) }}</p>
                                    <p class="text-xs text-on-surface-variant">/ {{ (float) $b->quota + (float) $b->carry_forward }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- No Employee Warning --}}
            @if (! $employee)
                <div class="mb-4 rounded-lg bg-warning/10 p-4 text-sm text-warning">
                    <span class="material-symbols-outlined mr-1 align-middle text-base">warning</span>
                    {{ __('Akun Anda belum terhubung dengan data karyawan. Silakan hubungi HR.') }}
                </div>
            @endif

            <x-app.panel class="p-6">
                {{-- Leave Type --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Jenis Cuti') }}</label>
                    <select x-ref="leaveTypeSelect" x-model="form.leave_type_id"
                            class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="">{{ __('Pilih jenis cuti...') }}</option>
                        @foreach ($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dates --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Tanggal Mulai') }}</label>
                        <input type="date" x-model="form.start_date" x-ref="startDate"
                               class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Tanggal Selesai') }}</label>
                        <input type="date" x-model="form.end_date" x-ref="endDate"
                               class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>
                <p class="-mt-3 mb-5 text-xs text-on-surface-variant">{{ __('Tap satu tanggal untuk cuti sehari, atau pilih tanggal lain untuk rentang.') }}</p>

                {{-- Day Type --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Durasi') }}</label>
                    <select x-model="form.day_type" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="full_day">{{ __('Sehari Penuh') }}</option>
                        <option value="morning">{{ __('Pagi Saja') }}</option>
                        <option value="afternoon">{{ __('Sore Saja') }}</option>
                    </select>
                </div>

                {{-- Reason --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Alasan') }}</label>
                    <textarea x-model="form.reason" rows="4"
                              class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60"
                              placeholder="{{ __('Jelaskan alasan cuti Anda...') }}"></textarea>
                    <p class="mt-1 text-xs text-on-surface-variant" x-text="form.reason.length + ' / 500'"></p>
                </div>

                {{-- Attachment --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Lampiran') }}</label>
                    <div class="flex min-h-[3rem] w-full cursor-pointer items-center justify-between gap-4 rounded-xl border border-dashed border-outline-variant px-4 py-3 text-sm hover:bg-surface-dim"
                         @click="$refs.attachmentInput.click()">
                        <span class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-base text-on-surface-variant">attach_file</span>
                            <span class="text-on-surface-variant">
                                <span x-show="!form.attachment_name">{{ __('Pilih file (gambar/PDF)') }}</span>
                                <span x-show="form.attachment_name" class="font-medium text-ink" x-text="form.attachment_name"></span>
                            </span>
                        </span>
                        <span class="rounded bg-surface-container-high px-2 py-0.5 text-xs font-medium text-on-surface-variant">{{ __('Opsional') }}</span>
                    </div>
                    <input type="file" accept="image/*,application/pdf" x-ref="attachmentInput" class="hidden"
                           @change="form.attachment = $refs.attachmentInput.files[0]; form.attachment_name = $refs.attachmentInput.files[0]?.name || ''" />
                </div>

                {{-- Error --}}
                <div x-show="error" class="mb-4 rounded-xl bg-error/10 p-4 text-sm text-error" x-text="error"></div>

                {{-- Actions --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <x-button variant="secondary" href="{{ route('leaves.index') }}" wire:navigate>{{ __('Batal') }}</x-button>
                    <x-button variant="primary" @click="submit()" x-bind:disabled="submitting">
                        <span x-show="!submitting">{{ __('Ajukan Cuti') }}</span>
                        <span x-show="submitting" class="material-symbols-outlined animate-spin">progress_activity</span>
                    </x-button>
                </div>
            </x-app.panel>
        </div>
    </div>
</x-layouts::app.sidebar>
