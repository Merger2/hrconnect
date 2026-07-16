<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji {{ $payroll->employee->employee_number }} — {{ $period_label }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica', 'Arial', sans-serif; }
        body { font-size: 11px; color: var(--md-sys-color-on-surface, #1f2937); padding: 32px 40px; }
        h1 { font-size: 18px; font-weight: bold; color: var(--md-sys-color-on-surface, #1f2937); }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 4px; }

        .header-table { width: 100%; margin-bottom: 16px; border-bottom: 2px solid var(--md-sys-color-on-surface, #1f2937); padding-bottom: 16px; }
        .header-left { font-size: 14px; font-weight: bold; }
        .header-left .meta { font-size: 9px; color: var(--md-sys-color-on-surface-variant, #6b7280); font-weight: normal; margin-top: 4px; }
        .header-right { text-align: right; }
        .header-right .period { font-size: 11px; color: var(--md-sys-color-on-surface-variant, #6b7280); margin-top: 4px; font-weight: normal; }

        .info-table { width: 100%; margin-bottom: 24px; background-color: var(--md-sys-color-surface-container, #f9fafb); }
        .info-table td { padding: 4px 12px; font-size: 10px; width: 50%; }
        .info-table .label { color: var(--md-sys-color-on-surface-variant, #6b7280); width: 100px; }
        .info-table .value { font-weight: bold; }

        .col-table { width: 100%; margin-bottom: 24px; }
        .col-table td { width: 50%; padding: 0 8px; vertical-align: top; }
        .col-inner { border: 1px solid var(--md-sys-color-outline-variant, #e5e7eb); }
        .col-head { padding: 8px 12px; background-color: var(--md-sys-color-surface-container-high, #f3f4f6); font-weight: bold; font-size: 11px; border-bottom: 1px solid var(--md-sys-color-outline-variant, #e5e7eb); }
        .col-head.income { color: var(--md-sys-color-success, #047857); }
        .col-head.deduction { color: var(--md-sys-color-error, #b91c1c); }
        .col-row { padding: 6px 12px; font-size: 10px; border-bottom: 1px solid var(--md-sys-color-surface-container-high, #f3f4f6); }
        .col-row .pull-left { float: left; color: var(--md-sys-color-on-surface-variant, #4b5563); }
        .col-row .pull-right { float: right; font-weight: bold; }
        .col-row:after { content: ''; display: table; clear: both; }
        .col-total { padding: 8px 12px; background-color: var(--md-sys-color-surface-container, #f9fafb); font-weight: bold; border-top: 1px solid var(--md-sys-color-outline, #d1d5db); }
        .col-total .pull-left { float: left; }
        .col-total .pull-right { float: right; }
        .col-total:after { content: ''; display: table; clear: both; }
        .col-total.income .pull-right { color: var(--md-sys-color-success, #047857); }
        .col-total.deduction .pull-right { color: var(--md-sys-color-error, #b91c1c); }

        .net-table { width: 100%; margin-bottom: 24px; }
        .net-table td { padding: 16px 20px; background-color: var(--md-sys-color-on-surface, #1f2937); color: var(--md-sys-color-surface, #ffffff); }
        .net-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; }
        .net-value { font-size: 20px; font-weight: bold; text-align: right; }

        .footer { padding-top: 16px; border-top: 1px solid var(--md-sys-color-outline-variant, #e5e7eb); font-size: 9px; color: var(--md-sys-color-outline, #9ca3af); text-align: center; }
        .footer .auto { font-style: italic; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="header-left" style="width: 60%;">
                {{ $company['name'] ?? 'PT 521 Teknologi Indonesia' }}
                <div class="meta">
                    {{ $company['address'] ?? 'Jakarta, Indonesia' }}<br>
                    NPWP: {{ $company['npwp'] ?? '-' }}
                </div>
            </td>
            <td class="header-right" style="width: 40%;">
                <h1>SLIP GAJI</h1>
                <div class="period">Periode {{ $period_label }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <table><tr><td class="label">NIK</td><td class="value">{{ $payroll->employee->employee_number }}</td></tr></table>
                <table><tr><td class="label">Nama</td><td class="value">{{ $payroll->employee->full_name }}</td></tr></table>
                <table><tr><td class="label">Posisi</td><td class="value">{{ $payroll->employee->position?->name ?? '-' }}</td></tr></table>
                <table><tr><td class="label">Departemen</td><td class="value">{{ $payroll->employee->department?->name ?? '-' }}</td></tr></table>
            </td>
            <td style="width: 50%;">
                <table><tr><td class="label">Cabang</td><td class="value">{{ $payroll->employee->branch?->name ?? '-' }}</td></tr></table>
                <table><tr><td class="label">Tipe Kerja</td><td class="value">{{ $payroll->employee->employment_type?->value ?? '-' }}</td></tr></table>
                <table><tr><td class="label">Tgl Masuk</td><td class="value">{{ optional($payroll->employee->join_date)->locale('id')->translatedFormat('d F Y') ?? '-' }}</td></tr></table>
                <table><tr><td class="label">Status Slip</td><td class="value">{{ strtoupper($payroll->status?->value ?? '-') }}</td></tr></table>
            </td>
        </tr>
    </table>

    <table class="col-table">
        <tr>
            <td>
                <div class="col-inner">
                    <div class="col-head income">PENDAPATAN</div>
                    <div class="col-row">
                        <span class="pull-left">Gaji Pokok</span>
                        <span class="pull-right">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-row">
                        <span class="pull-left">Tunjangan</span>
                        <span class="pull-right">Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-row">
                        <span class="pull-left">Lembur</span>
                        <span class="pull-right">Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</span>
                    </div>
                    @foreach($extra_income as $item)
                        <div class="col-row">
                            <span class="pull-left">{{ $item['name'] }}</span>
                            <span class="pull-right">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="col-total income">
                        <span class="pull-left">TOTAL PENDAPATAN</span>
                        <span class="pull-right">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</span>
                    </div>
                </div>
            </td>
            <td>
                <div class="col-inner">
                    <div class="col-head deduction">POTONGAN</div>
                    <div class="col-row">
                        <span class="pull-left">PPh 21</span>
                        <span class="pull-right">Rp {{ number_format($payroll->pph21, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-row">
                        <span class="pull-left">BPJS Kesehatan</span>
                        <span class="pull-right">Rp {{ number_format($payroll->bpjs_health, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-row">
                        <span class="pull-left">BPJS Ketenagakerjaan</span>
                        <span class="pull-right">Rp {{ number_format($payroll->bpjs_employment, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-row">
                        <span class="pull-left">Pinjaman</span>
                        <span class="pull-right">Rp {{ number_format($payroll->loan_deduction, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-row">
                        <span class="pull-left">Denda Kehadiran</span>
                        <span class="pull-right">Rp {{ number_format($payroll->attendance_penalty, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-total deduction">
                        <span class="pull-left">TOTAL POTONGAN</span>
                        <span class="pull-right">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="net-table">
        <tr>
            <td style="width: 50%;"><span class="net-label">Take Home Pay</span></td>
            <td style="width: 50%;"><span class="net-value">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span></td>
        </tr>
    </table>

    <div class="footer">
        <div>Slip gaji ini di-generate otomatis oleh sistem HRConnect.</div>
        <div class="auto">Dicetak: {{ $generated_at }} — Untuk pertanyaan, hubungi tim HR/Finance.</div>
    </div>
</body>
</html>
