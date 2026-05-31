<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji {{ $payroll->employee->employee_number }} — {{ $period_label }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica', 'Arial', sans-serif; }
        body { font-size: 11px; color: #1f2937; padding: 32px 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; border-bottom: 2px solid #1f2937; margin-bottom: 24px; }
        .company { font-size: 14px; font-weight: bold; }
        .company-meta { font-size: 9px; color: #6b7280; margin-top: 4px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 18px; font-weight: bold; color: #1f2937; }
        .doc-title .period { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; padding: 12px 16px; background-color: #f9fafb; border-radius: 4px; }
        .info-grid dl { display: grid; grid-template-columns: 100px 1fr; gap: 4px 12px; font-size: 10px; }
        .info-grid dt { color: #6b7280; }
        .info-grid dd { font-weight: bold; }
        .columns { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .col { border: 1px solid #e5e7eb; border-radius: 4px; }
        .col-head { padding: 8px 12px; background-color: #f3f4f6; font-weight: bold; font-size: 11px; border-bottom: 1px solid #e5e7eb; }
        .col-head.income { color: #047857; }
        .col-head.deduction { color: #b91c1c; }
        .col-row { display: flex; justify-content: space-between; padding: 6px 12px; font-size: 10px; border-bottom: 1px solid #f3f4f6; }
        .col-row:last-child { border-bottom: none; }
        .col-row .label { color: #4b5563; }
        .col-row .value { font-weight: bold; }
        .col-total { display: flex; justify-content: space-between; padding: 8px 12px; background-color: #f9fafb; font-weight: bold; border-top: 1px solid #d1d5db; }
        .col-total.income .value { color: #047857; }
        .col-total.deduction .value { color: #b91c1c; }
        .net-salary { padding: 16px 20px; background-color: #1f2937; color: white; border-radius: 4px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .net-salary .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; }
        .net-salary .value { font-size: 20px; font-weight: bold; }
        .footer { padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
        .footer .auto { font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company">{{ $company['name'] ?? 'PT 521 Teknologi Indonesia' }}</div>
            <div class="company-meta">
                {{ $company['address'] ?? 'Jakarta, Indonesia' }}<br>
                NPWP: {{ $company['npwp'] ?? '-' }}
            </div>
        </div>
        <div class="doc-title">
            <h1>SLIP GAJI</h1>
            <div class="period">Periode {{ $period_label }}</div>
        </div>
    </div>

    <div class="info-grid">
        <dl>
            <dt>NIK</dt><dd>{{ $payroll->employee->employee_number }}</dd>
            <dt>Nama</dt><dd>{{ $payroll->employee->full_name }}</dd>
            <dt>Posisi</dt><dd>{{ $payroll->employee->position?->name ?? '-' }}</dd>
            <dt>Departemen</dt><dd>{{ $payroll->employee->department?->name ?? '-' }}</dd>
        </dl>
        <dl>
            <dt>Cabang</dt><dd>{{ $payroll->employee->branch?->name ?? '-' }}</dd>
            <dt>Tipe Kerja</dt><dd>{{ $payroll->employee->employment_type?->value ?? '-' }}</dd>
            <dt>Tgl Masuk</dt><dd>{{ optional($payroll->employee->join_date)->format('d M Y') ?? '-' }}</dd>
            <dt>Status Slip</dt><dd>{{ strtoupper($payroll->status?->value ?? '-') }}</dd>
        </dl>
    </div>

    <div class="columns">
        <div class="col">
            <div class="col-head income">PENDAPATAN</div>
            <div class="col-row">
                <span class="label">Gaji Pokok</span>
                <span class="value">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</span>
            </div>
            <div class="col-row">
                <span class="label">Tunjangan</span>
                <span class="value">Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</span>
            </div>
            <div class="col-row">
                <span class="label">Lembur</span>
                <span class="value">Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</span>
            </div>
            @foreach($extra_income as $item)
                <div class="col-row">
                    <span class="label">{{ $item['name'] }}</span>
                    <span class="value">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                </div>
            @endforeach
            <div class="col-total income">
                <span>TOTAL PENDAPATAN</span>
                <span class="value">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="col">
            <div class="col-head deduction">POTONGAN</div>
            <div class="col-row">
                <span class="label">PPh 21</span>
                <span class="value">Rp {{ number_format($payroll->pph21, 0, ',', '.') }}</span>
            </div>
            <div class="col-row">
                <span class="label">BPJS Kesehatan</span>
                <span class="value">Rp {{ number_format($payroll->bpjs_health, 0, ',', '.') }}</span>
            </div>
            <div class="col-row">
                <span class="label">BPJS Ketenagakerjaan</span>
                <span class="value">Rp {{ number_format($payroll->bpjs_employment, 0, ',', '.') }}</span>
            </div>
            <div class="col-row">
                <span class="label">Pinjaman</span>
                <span class="value">Rp {{ number_format($payroll->loan_deduction, 0, ',', '.') }}</span>
            </div>
            <div class="col-row">
                <span class="label">Denda Kehadiran</span>
                <span class="value">Rp {{ number_format($payroll->attendance_penalty, 0, ',', '.') }}</span>
            </div>
            <div class="col-total deduction">
                <span>TOTAL POTONGAN</span>
                <span class="value">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="net-salary">
        <span class="label">Take Home Pay</span>
        <span class="value">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
    </div>

    <div class="footer">
        <div>Slip gaji ini di-generate otomatis oleh sistem HRConnect.</div>
        <div class="auto">
            Dicetak: {{ $generated_at }} — Untuk pertanyaan, hubungi tim HR/Finance.
        </div>
    </div>
</body>
</html>
