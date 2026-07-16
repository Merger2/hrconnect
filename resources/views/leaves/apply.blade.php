<x-layouts::app.sidebar>
    <div x-data="wizardLeaveApply({{ Js::from(['leaveTypes' => $leaveTypes->toArray(), 'hasEmployee' => $employee !== null]) }})">
        <x-page-shell title="{{ __('Pengajuan Cuti') }}" subtitle="{{ __('Ajukan cuti atau izin baru') }}">
            <x-slot:actions>
                <x-button variant="secondary" href="{{ route('leaves.index') }}" wire:navigate icon="arrow_back">
                    {{ __('Kembali') }}
                </x-button>
            </x-slot:actions>

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

                <x-forms.form-wizard
                    :steps="[
                        ['title' => 'Jenis & Tanggal', 'subtitle' => 'Pilih cuti dan periode'],
                        ['title' => 'Alasan & Lampiran', 'subtitle' => 'Detail pengajuan'],
                    ]"
                    submit-label="{{ __('Ajukan Cuti') }}"
                    cancel-url="{{ route('leaves.index') }}"
                >
                    {{-- Step 0 — Jenis & Tanggal --}}
                    <div data-step-panel="0" class="space-y-5">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Jenis Cuti') }}</label>
                            <select x-ref="leaveTypeSelect" x-model="form.leave_type_id"
                                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink"
                                    placeholder="{{ __('Pilih jenis cuti...') }}">
                                <option value="">{{ __('Pilih jenis cuti...') }}</option>
                                @foreach ($leaveTypes as $lt)
                                    <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Tanggal Mulai') }}</label>
                                <input type="date" x-model="form.start_date" x-ref="startDate"
                                       class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink"
                                       placeholder="{{ __('Pilih tanggal') }}">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Tanggal Selesai') }}</label>
                                <input type="date" x-model="form.end_date" x-ref="endDate"
                                       class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink"
                                       placeholder="{{ __('Pilih tanggal') }}">
                            </div>
                        </div>
                        <p class="-mt-3 text-xs text-on-surface-variant">{{ __('Tap satu tanggal untuk cuti sehari, atau pilih tanggal lain untuk rentang.') }}</p>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Durasi') }}</label>
                            <select x-model="form.day_type" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                                <option value="full_day">{{ __('Sehari Penuh') }}</option>
                                <option value="morning">{{ __('Pagi Saja') }}</option>
                                <option value="afternoon">{{ __('Sore Saja') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Step 1 — Alasan & Lampiran --}}
                    <div data-step-panel="1" class="space-y-5" x-cloak>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Alasan') }}</label>
                            <textarea x-model="form.reason" rows="4"
                                      class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60"
                                      placeholder="{{ __('Jelaskan alasan cuti Anda...') }}"></textarea>
                            <p class="mt-1 text-xs text-on-surface-variant" x-text="form.reason.length + ' / 500'"></p>
                        </div>

                        <div>
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

                        <div x-show="error" class="rounded-xl bg-error/10 p-4 text-sm text-error" x-text="error"></div>
                    </div>
                </x-forms.form-wizard>
            </div>
        </x-page-shell>
    </div>
</x-layouts::app.sidebar>
