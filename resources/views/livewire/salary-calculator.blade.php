<div class="space-y-6">
    <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
        <h3 class="mb-4 text-sm font-semibold text-ink">{{ __('Kalkulator Gaji') }}</h3>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-forms.input name="gajiPokok" label="Gaji Pokok" type="number" wire:model.live="gajiPokok" />
            <x-forms.input name="tunjanganJabatan" label="Tunjangan Jabatan" type="number" wire:model.live="tunjanganJabatan" />
            <x-forms.input name="tunjanganMakan" label="Tunjangan Makan" type="number" wire:model.live="tunjanganMakan" />
            <x-forms.input name="tunjanganTransport" label="Tunjangan Transport" type="number" wire:model.live="tunjanganTransport" />
            <x-forms.input name="lemburPay" label="Lembur (Rp)" type="number" wire:model.live="lemburPay" />
            <x-forms.input name="hariAlfa" label="Hari Alfa" type="number" wire:model.live="hariAlfa" />
        </div>

        <div class="mt-4 flex justify-end">
            <x-button wire:click="calculate">{{ __('Hitung') }}</x-button>
        </div>
    </div>

    @if ($result)
        <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
            <h4 class="mb-3 text-sm font-semibold text-ink">{{ __('Ringkasan Perhitungan') }}</h4>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-on-surface-variant">{{ __('Gaji Pokok') }}</dt>
                    <dd class="font-medium text-ink"><x-amount :value="$result['gaji_pokok']" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-on-surface-variant">{{ __('Tunjangan') }}</dt>
                    <dd class="font-medium text-ink">
                        <x-amount :value="$result['tunjangan_jabatan'] + $result['tunjangan_makan'] + $result['tunjangan_transport']" />
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-on-surface-variant">{{ __('Lembur') }}</dt>
                    <dd class="font-medium text-ink"><x-amount :value="$result['lembur_pay']" /></dd>
                </div>
                <div class="border-t border-outline-variant/50 pt-2">
                    <div class="flex justify-between font-semibold">
                        <dt>{{ __('Penghasilan Bruto') }}</dt>
                        <dd><x-amount :value="$result['penghasilan_bruto']" /></dd>
                    </div>
                </div>
                <div class="flex justify-between text-error">
                    <dt>{{ __('Potongan Alfa') }}</dt>
                    <dd><x-amount :value="$result['potongan_alfa']" color="danger" /></dd>
                </div>
                <div class="flex justify-between text-error">
                    <dt>{{ __('Potongan PPh21') }}</dt>
                    <dd><x-amount :value="$result['potongan_pph21']" color="danger" /></dd>
                </div>
                <div class="flex justify-between text-error">
                    <dt>{{ __('Potongan BPJS') }}</dt>
                    <dd><x-amount :value="$result['potongan_bpjs']" color="danger" /></dd>
                </div>
                <div class="flex justify-between text-on-surface-variant">
                    <dt>{{ __('Total Potongan') }}</dt>
                    <dd><x-amount :value="$result['total_potongan']" color="danger" /></dd>
                </div>
                <div class="border-t border-outline-variant/50 pt-2">
                    <div class="flex justify-between text-lg font-bold text-success">
                        <dt>{{ __('Gaji Bersih') }}</dt>
                        <dd><x-amount :value="$result['total_bersih']" color="success" /></dd>
                    </div>
                </div>
            </dl>
        </div>
    @endif
</div>
