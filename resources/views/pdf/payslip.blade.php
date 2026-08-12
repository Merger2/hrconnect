@php
    $logoSrc = $company['logo'] ?? null;
    $companyName = $company['name'] ?? \App\Models\Setting::getValue('app.company_name', config('app.name'));
    $companyAddress = $company['address'] ?? \App\Models\Setting::getValue('app.company_address', '');
    $periodDate = \Carbon\Carbon::parse($payroll->period . '-01');
    $periodLabel = $periodDate->translatedFormat('F Y');
    $formatMoney = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $totalEarnings = $payroll->gross_salary ?? ((float) $payroll->basic_salary + (float) $payroll->total_allowance);
    $totalDeductions = $payroll->total_deductions ?? $payroll->total_deduction;
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Payslip') }} - {{ $periodLabel }}</title>
    <style>
        @page {
            margin: 34px 54px 68px 54px;
        }

        body {
            color: {{ design_token('primary-900') }};
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.55;
            margin: 0;
        }

        .watermark {
            color: {{ design_token('primary-200') }};
            font-size: 68px;
            font-weight: 700;
            left: 0;
            letter-spacing: 8px;
            opacity: .26;
            position: fixed;
            right: 0;
            text-align: center;
            top: 42%;
            transform: rotate(-34deg);
            z-index: -10;
        }

        .top-corner-navy {
            border-left: 82px solid transparent;
            border-top: 82px solid {{ design_token('brand-deep') }};
            height: 0;
            position: fixed;
            right: -54px;
            top: -34px;
            width: 0;
        }

        .top-corner-primary {
            border-left: 46px solid transparent;
            border-top: 46px solid {{ design_token('brand-green-500') }};
            height: 0;
            position: fixed;
            right: -54px;
            top: -34px;
            width: 0;
        }

        .letterhead {
            border-bottom: 1.4px solid {{ design_token('brand-green-800') }};
            margin: 0 0 20px;
            padding-bottom: 12px;
            width: 100%;
        }

        .letterhead,
        .letterhead td,
        .meta-table,
        .meta-table td,
        .salary-table,
        .summary-table,
        .signature-table {
            border: 0;
        }

        .logo-cell {
            padding: 0 14px 0 0;
            vertical-align: middle;
            width: 64px;
        }

        .company-cell {
            padding: 0 14px 0 0;
            vertical-align: middle;
        }

        .doc-cell {
            text-align: right;
            vertical-align: middle;
            width: 190px;
        }

        .company-name {
            color: {{ design_token('primary-900') }};
            font-size: 15.5px;
            font-weight: 700;
            letter-spacing: .01em;
            margin: 0 0 2px;
            text-transform: uppercase;
        }

        .company-address {
            color: {{ design_token('primary-600') }};
            font-size: 9.2px;
            line-height: 1.35;
            margin: 0;
        }

        .company-mark {
            color: {{ design_token('brand-green-600') }};
            font-size: 8.2px;
            font-weight: 700;
            letter-spacing: .18em;
            margin: 3px 0 0;
            text-transform: uppercase;
        }

        .document-title {
            color: {{ design_token('brand-deep') }};
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .04em;
            margin: 0;
            text-transform: uppercase;
        }

        .document-period {
            color: {{ design_token('brand-green-800') }};
            font-size: 10px;
            font-weight: 700;
            margin: 3px 0 0;
            text-transform: uppercase;
        }

        .meta-table {
            margin: 0 0 18px;
            width: 100%;
        }

        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .meta-label {
            color: {{ design_token('brand-green-800') }};
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            width: 115px;
        }

        .meta-value {
            color: {{ design_token('primary-900') }};
            font-size: 10.5px;
            font-weight: 700;
        }

        .section-title {
            color: {{ design_token('brand-deep') }};
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            margin: 16px 0 7px;
            text-transform: uppercase;
        }

        .salary-table {
            border-collapse: collapse;
            margin: 0 0 10px;
            width: 100%;
        }

        .salary-table th {
            background: {{ design_token('brand-deep') }};
            border: 1px solid {{ design_token('brand-deep') }};
            color: {{ design_token('surface') }};
            font-size: 9px;
            font-weight: 700;
            padding: 7px;
            text-align: left;
            text-transform: uppercase;
        }

        .salary-table td {
            border: 1px solid {{ design_token('primary-300') }};
            padding: 7px;
            vertical-align: top;
        }

        .salary-table .subtotal td {
            background: {{ design_token('brand-green-50') }};
            color: {{ design_token('brand-green-800') }};
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .text-red {
            color: {{ design_token('status-red') }};
        }

        .summary-card {
            background: {{ design_token('brand-green-50') }};
            border: 1.5px solid {{ design_token('brand-green-500') }};
            border-radius: 8px;
            margin: 16px 0 0;
            padding: 12px 14px;
        }

        .summary-table {
            width: 100%;
        }

        .summary-label {
            color: {{ design_token('brand-green-800') }};
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .summary-value {
            color: {{ design_token('brand-deep') }};
            font-size: 20px;
            font-weight: 700;
            text-align: right;
        }

        .signature-section {
            margin-top: 42px;
            page-break-inside: avoid;
        }

        .signature-table {
            table-layout: fixed;
            width: 100%;
        }

        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
        }

        .signature-line {
            border-top: 1px solid {{ design_token('primary-400') }};
            margin: 54px auto 8px;
            width: 62%;
        }

        .signature-name {
            color: {{ design_token('primary-900') }};
            font-size: 11px;
            font-weight: 700;
        }

        .signature-role {
            color: {{ design_token('primary-500') }};
            font-size: 9px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .footer {
            border-top: 1px solid {{ design_token('brand-green-300') }};
            bottom: -54px;
            color: {{ design_token('primary-500') }};
            font-size: 8.4px;
            left: 0;
            line-height: 1.4;
            padding-top: 8px;
            position: fixed;
            right: 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="watermark">{{ __('Confidential') }}</div>
    <div class="top-corner-navy"></div>
    <div class="top-corner-primary"></div>

    <table class="letterhead">
        <tr>
            @if ($logoSrc)
                <td class="logo-cell">
                    <img src="{{ $logoSrc }}" style="height: 56px; width: auto;" alt="{{ $companyName }}">
                </td>
            @endif
            <td class="company-cell">
                <h1 class="company-name">{{ $companyName }}</h1>
                @if ($companyAddress)
                    <p class="company-address">{{ $companyAddress }}</p>
                @endif
                <p class="company-mark">{{ __('Enterprise Workforce System') }}</p>
            </td>
            <td class="doc-cell">
                <h2 class="document-title">{{ __('Payslip') }}</h2>
                <p class="document-period">{{ $periodLabel }}</p>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="meta-label">{{ __('NIP') }}</td>
            <td class="meta-value">{{ $payroll->employee->nip ?? '-' }}</td>
            <td class="meta-label">{{ __('Generated On') }}</td>
            <td class="meta-value">{{ $payroll->created_at->translatedFormat('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('Name') }}</td>
            <td class="meta-value">{{ $payroll->employee->full_name ?? $payroll->employee->user->name ?? '-' }}</td>
            <td class="meta-label">{{ __('Status') }}</td>
            <td class="meta-value">{{ $payroll->status instanceof \BackedEnum ? $payroll->status->label() : __(ucfirst((string) $payroll->status)) }}</td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('Department') }}</td>
            <td class="meta-value">{{ $payroll->employee->division->name ?? '-' }}</td>
            <td class="meta-label">{{ __('Job Title') }}</td>
            <td class="meta-value">{{ $payroll->employee->position->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">{{ __('Earnings') }}</div>
    <table class="salary-table">
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th style="width: 30%;" class="text-right">{{ __('Amount') }} (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ __('Basic Salary') }}</td>
                <td class="text-right">{{ $formatMoney($payroll->basic_salary) }}</td>
            </tr>
            @if (is_array($payroll->allowances))
                @foreach ($payroll->allowances as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td class="text-right">{{ $formatMoney($amount) }}</td>
                    </tr>
                @endforeach
            @endif
            <tr class="subtotal">
                <td>{{ __('Total Earnings') }}</td>
                <td class="text-right">{{ $formatMoney($totalEarnings) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">{{ __('Deductions') }}</div>
    <table class="salary-table">
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th style="width: 30%;" class="text-right">{{ __('Amount') }} (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @if (is_array($payroll->deductions) && count($payroll->deductions) > 0)
                @foreach ($payroll->deductions as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td class="text-right text-red">-{{ $formatMoney($amount) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>{{ __('No deductions') }}</td>
                    <td class="text-right">Rp 0</td>
                </tr>
            @endif
            <tr class="subtotal">
                <td>{{ __('Total Deductions') }}</td>
                <td class="text-right text-red">-{{ $formatMoney($totalDeductions) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-card">
        <table class="summary-table">
            <tr>
                <td class="summary-label">{{ __('Net Salary (Take Home Pay)') }}</td>
                <td class="summary-value">{{ $formatMoney($payroll->net_salary) }}</td>
            </tr>
        </table>
    </div>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-role">{{ __('Approved By') }}</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ __('Manager / HR') }}</div>
                </td>
                <td>
                    <div class="signature-role">{{ __('Received By') }}</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $payroll->employee->full_name ?? $payroll->employee->user->name ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div>{{ __('Generated by :app. This is a computer-generated document and may not require a physical signature.', ['app' => config('app.name')]) }}</div>
        <div>{{ __('Please note that the contents of this statement should be treated with absolute confidentiality except where disclosure is required for tax, legal, or regulatory purposes. Any breach of this confidentiality obligation may result in disciplinary action.') }}</div>
    </div>
</body>
</html>
