@php
$show = $result || true;
$r = $result ?: [
    'gaji_pokok' => 0,
    'tunjangan_jabatan' => 0,
    'tunjangan_makan' => 0,
    'tunjangan_transport' => 0,
    'lembur_pay' => 0,
    'penghasilan_bruto' => 0,
    'potongan_alfa' => 0,
    'potongan_pph21' => 0,
    'potongan_bpjs' => 0,
    'total_potongan' => 0,
    'total_bersih' => 0,
];
@endphp

<div class="space-y-6">
    <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
        <h3 class="mb-4 text-sm font-semibold text-ink">{{ __('Kalkulator Gaji') }}</h3>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-forms.input name="gajiPokok" label="Gaji Pokok" type="number"
                    wire:model.live="gajiPokok" data-field="gaji_pokok"
                    oninput="recalculateClientSide()" />
            </div>
            <div>
                <x-forms.input name="tunjanganJabatan" label="Tunjangan Jabatan" type="number"
                    wire:model.live="tunjanganJabatan" data-field="tunjangan_jabatan"
                    oninput="recalculateClientSide()" />
            </div>
            <div>
                <x-forms.input name="tunjanganMakan" label="Tunjangan Makan" type="number"
                    wire:model.live="tunjanganMakan" data-field="tunjangan_makan"
                    oninput="recalculateClientSide()" />
            </div>
            <div>
                <x-forms.input name="tunjanganTransport" label="Tunjangan Transport" type="number"
                    wire:model.live="tunjanganTransport" data-field="tunjangan_transport"
                    oninput="recalculateClientSide()" />
            </div>
            <div>
                <x-forms.input name="lemburPay" label="Lembur (Rp)" type="number"
                    wire:model.live="lemburPay" data-field="lembur_pay"
                    oninput="recalculateClientSide()" />
            </div>
            <div>
                <x-forms.input name="hariAlfa" label="Hari Alfa" type="number"
                    wire:model.live="hariAlfa" data-field="hari_alfa"
                    oninput="recalculateClientSide()" />
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <x-button wire:click="calculate">{{ __('Hitung via Server') }}</x-button>
        </div>
    </div>

    <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-ink">{{ __('Ringkasan Perhitungan') }}</h4>

        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-on-surface-variant">{{ __('Gaji Pokok') }}</dt>
                <dd class="font-medium text-ink" id="calc-gaji-pokok"><x-amount :value="$r['gaji_pokok']" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-on-surface-variant">{{ __('Tunjangan') }}</dt>
                <dd class="font-medium text-ink" id="calc-tunjangan"><x-amount :value="$r['tunjangan_jabatan'] + $r['tunjangan_makan'] + $r['tunjangan_transport']" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-on-surface-variant">{{ __('Lembur') }}</dt>
                <dd class="font-medium text-ink" id="calc-lembur"><x-amount :value="$r['lembur_pay']" /></dd>
            </div>
            <div class="border-t border-outline-variant/50 pt-2">
                <div class="flex justify-between font-semibold">
                    <dt>{{ __('Penghasilan Bruto') }}</dt>
                    <dd id="calc-bruto"><x-amount :value="$r['penghasilan_bruto']" /></dd>
                </div>
            </div>
            <div class="flex justify-between text-error">
                <dt>{{ __('Potongan Alfa') }}</dt>
                <dd id="calc-alfa"><x-amount :value="$r['potongan_alfa']" color="danger" /></dd>
            </div>
            <div class="flex justify-between text-error">
                <dt>{{ __('Potongan PPh21') }}</dt>
                <dd id="calc-pph21"><x-amount :value="$r['potongan_pph21']" color="danger" /></dd>
            </div>
            <div class="flex justify-between text-error">
                <dt>{{ __('Potongan BPJS') }}</dt>
                <dd id="calc-bpjs"><x-amount :value="$r['potongan_bpjs']" color="danger" /></dd>
            </div>
            <div class="flex justify-between text-on-surface-variant">
                <dt>{{ __('Total Potongan') }}</dt>
                <dd id="calc-total-potongan"><x-amount :value="$r['total_potongan']" color="danger" /></dd>
            </div>
            <div class="border-t border-outline-variant/50 pt-2">
                <div class="flex justify-between text-lg font-bold text-success">
                    <dt>{{ __('Gaji Bersih') }}</dt>
                    <dd id="calc-bersih"><x-amount :value="$r['total_bersih']" color="success" /></dd>
                </div>
            </div>
        </dl>
    </div>

    <script>
        function recalculateClientSide() {
            setTimeout(() => {
                const gajiPokok = parseFloat(document.querySelector('[data-field="gaji_pokok"]')?.value || 0);
                const tunjanganJabatan = parseFloat(document.querySelector('[data-field="tunjangan_jabatan"]')?.value || 0);
                const tunjanganMakan = parseFloat(document.querySelector('[data-field="tunjangan_makan"]')?.value || 0);
                const tunjanganTransport = parseFloat(document.querySelector('[data-field="tunjangan_transport"]')?.value || 0);
                const lemburPay = parseFloat(document.querySelector('[data-field="lembur_pay"]')?.value || 0);
                const hariAlfa = parseInt(document.querySelector('[data-field="hari_alfa"]')?.value || 0);

                const tunjangan = tunjanganJabatan + tunjanganMakan + tunjanganTransport;
                const bruto = gajiPokok + tunjangan + lemburPay;

                const upahTetap = gajiPokok + tunjanganJabatan;
                const potonganAlfa = hariAlfa > 0 ? Math.round((upahTetap / 22) * hariAlfa) : 0;

                const pkp = bruto * 12 - 54000000;
                let pph21Setahun = 0;
                if (pkp > 0) {
                    if (pkp <= 60000000) {
                        pph21Setahun = pkp * 0.05;
                    } else if (pkp <= 250000000) {
                        pph21Setahun = 60000000 * 0.05 + (pkp - 60000000) * 0.15;
                    } else if (pkp <= 500000000) {
                        pph21Setahun = 60000000 * 0.05 + 190000000 * 0.15 + (pkp - 250000000) * 0.25;
                    } else {
                        pph21Setahun = 60000000 * 0.05 + 190000000 * 0.15 + 250000000 * 0.25 + (pkp - 500000000) * 0.3;
                    }
                }
                const potonganPph21 = Math.round(pph21Setahun / 12);

                const dasar = gajiPokok + tunjanganJabatan;
                const bpjsKesehatan = Math.round(Math.min(dasar, 12000000) * 0.01);
                const bpjsJht = Math.round(gajiPokok * 0.02);
                const bpjsJp = Math.round(Math.min(dasar, 10547400) * 0.01);
                const potonganBpjs = bpjsKesehatan + bpjsJht + bpjsJp;

                const totalPotongan = potonganAlfa + potonganPph21 + potonganBpjs;
                const bersih = Math.max(0, bruto - totalPotongan);

                const fmt = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');

                const el = (id) => document.getElementById(id);
                if (el('calc-gaji-pokok')) el('calc-gaji-pokok').textContent = fmt(gajiPokok);
                if (el('calc-tunjangan')) el('calc-tunjangan').textContent = fmt(tunjangan);
                if (el('calc-lembur')) el('calc-lembur').textContent = fmt(lemburPay);
                if (el('calc-bruto')) el('calc-bruto').innerHTML = '<span class="font-semibold text-ink">' + fmt(bruto) + '</span>';
                if (el('calc-alfa')) el('calc-alfa').innerHTML = '<span class="text-error">' + fmt(potonganAlfa) + '</span>';
                if (el('calc-pph21')) el('calc-pph21').innerHTML = '<span class="text-error">' + fmt(potonganPph21) + '</span>';
                if (el('calc-bpjs')) el('calc-bpjs').innerHTML = '<span class="text-error">' + fmt(potonganBpjs) + '</span>';
                if (el('calc-total-potongan')) el('calc-total-potongan').innerHTML = '<span class="text-on-surface-variant">' + fmt(totalPotongan) + '</span>';
                if (el('calc-bersih')) el('calc-bersih').innerHTML = '<span class="text-lg font-bold text-success">' + fmt(bersih) + '</span>';
            }, 50);
        }

        document.addEventListener('livewire:init', () => {
            setTimeout(recalculateClientSide, 500);
        });
    </script>
</div>
