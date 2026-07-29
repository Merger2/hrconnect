@php
    $logoSrc = \App\Support\MailBranding::logoPdfSource();
    $companyAddress = \App\Models\Setting::getValue('app.company_address', '');
    $periodLabel = \Carbon\Carbon::createFromDate((int) $appraisal->period_year, (int) $appraisal->period_month, 1)->translatedFormat('F Y');
    $documentId = 'APR-'.str_pad((string) $appraisal->id, 5, '0', STR_PAD_LEFT);
    $scoreClass = fn ($score) => (float) $score >= 80 ? 'score-good' : ((float) $score >= 60 ? 'score-watch' : 'score-risk');
    $gradeLabel = function ($score): string {
        if (! is_numeric($score)) {
            return '-';
        }

        return match (true) {
            (float) $score >= 90 => __('Grade').': A ('.__('Outstanding').')',
            (float) $score >= 80 => __('Grade').': B ('.__('Exceeds Expectations').')',
            (float) $score >= 70 => __('Grade').': C ('.__('Meets Expectations').')',
            (float) $score >= 60 => __('Grade').': D ('.__('Needs Improvement').')',
            default => __('Grade').': E ('.__('Below Expectations').')',
        };
    };
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Performance Appraisal Report') }} - {{ $periodLabel }}</title>
    <style>
        @page {
            margin: 34px 54px 68px 54px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.55;
            margin: 0;
        }

        .top-corner-navy {
            border-left: 82px solid transparent;
            border-top: 82px solid #083344;
            height: 0;
            position: fixed;
            right: -54px;
            top: -34px;
            width: 0;
        }

        .top-corner-primary {
            border-left: 46px solid transparent;
            border-top: 46px solid #6ab45b;
            height: 0;
            position: fixed;
            right: -54px;
            top: -34px;
            width: 0;
        }

        .letterhead {
            border-bottom: 1.4px solid #31542a;
            margin: 0 0 20px;
            padding-bottom: 12px;
            width: 100%;
        }

        .letterhead,
        .letterhead td,
        .meta-table,
        .meta-table td,
        .score-summary,
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
            width: 210px;
        }

        .company-name {
            color: #111827;
            font-size: 15.5px;
            font-weight: 700;
            letter-spacing: .01em;
            margin: 0 0 2px;
            text-transform: uppercase;
        }

        .company-address {
            color: #4b5563;
            font-size: 9.2px;
            line-height: 1.35;
            margin: 0;
        }

        .company-mark {
            color: #57944a;
            font-size: 8.2px;
            font-weight: 700;
            letter-spacing: .18em;
            margin: 3px 0 0;
            text-transform: uppercase;
        }

        .document-title {
            color: #083344;
            font-size: 15.5px;
            font-weight: 700;
            letter-spacing: .04em;
            line-height: 1.35;
            margin: 0;
            text-transform: uppercase;
        }

        .document-meta {
            color: #31542a;
            font-size: 9.5px;
            font-weight: 700;
            margin: 3px 0 0;
            text-transform: uppercase;
        }

        .meta-table {
            margin: 0 0 18px;
            width: 100%;
        }

        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-label {
            color: #31542a;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            width: 120px;
        }

        .meta-value {
            color: #111827;
            font-size: 10.5px;
            font-weight: 700;
        }

        .status-badge,
        .score-box {
            border-radius: 5px;
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            padding: 3px 8px;
        }

        .status-badge {
            background: #f0f9ee;
            color: #31542a;
        }

        .score-good {
            background: #dcfce7;
            color: #166534;
        }

        .score-watch {
            background: #fef3c7;
            color: #92400e;
        }

        .score-risk {
            background: #fee2e2;
            color: #991b1b;
        }

        .section-title {
            color: #083344;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            margin: 16px 0 7px;
            text-transform: uppercase;
        }

        .kpi-table {
            border-collapse: collapse;
            margin: 0 0 12px;
            width: 100%;
        }

        .kpi-table th {
            background: #083344;
            border: 1px solid #083344;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            padding: 7px;
            text-align: left;
            text-transform: uppercase;
        }

        .kpi-table td {
            border: 1px solid #d1d5db;
            padding: 7px;
            vertical-align: top;
        }

        .score-card {
            background: #f0f9ee;
            border: 1.5px solid #6ab45b;
            border-radius: 8px;
            margin: 16px 0 14px;
            padding: 13px 14px;
        }

        .score-summary {
            width: 100%;
        }

        .score-label {
            color: #31542a;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .score-value {
            color: #083344;
            font-size: 28px;
            font-weight: 700;
            text-align: right;
        }

        .score-grade {
            color: #4b5563;
            font-size: 10px;
            margin-top: 4px;
        }

        .notes-box {
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-left: 3px solid #6ab45b;
            border-radius: 6px;
            color: #4b5563;
            font-size: 10px;
            margin: 0 0 10px;
            padding: 9px 10px;
        }

        .signature-area {
            margin-top: 38px;
            page-break-inside: avoid;
        }

        .signature-table {
            table-layout: fixed;
            width: 100%;
        }

        .signature-table td {
            padding: 0 8px;
            text-align: center;
            vertical-align: top;
            width: 33.33%;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            margin: 52px auto 8px;
            width: 78%;
        }

        .signature-name {
            color: #111827;
            font-size: 10.5px;
            font-weight: 700;
        }

        .signature-role {
            color: #6b7280;
            font-size: 8.8px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .acknowledged {
            color: #166534;
            font-size: 8.5px;
            margin-top: 3px;
        }

        .footer {
            border-top: 1px solid #badcb3;
            bottom: -54px;
            color: #6b7280;
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
                <h2 class="document-title">{{ __('Performance Appraisal Report') }}</h2>
                <p class="document-meta">{{ $documentId }} · {{ now()->translatedFormat('d M Y H:i') }}</p>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ __('Employee Information') }}</div>
    <table class="meta-table">
        <tr>
            <td class="meta-label">{{ __('Employee Name') }}</td>
            <td class="meta-value">{{ $appraisal->user->name }}</td>
            <td class="meta-label">{{ __('Appraisal Period') }}</td>
            <td class="meta-value">{{ $periodLabel }}</td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('NIP / Employee ID') }}</td>
            <td class="meta-value">{{ $appraisal->user->nip ?? '-' }}</td>
            <td class="meta-label">{{ __('Status') }}</td>
            <td class="meta-value"><span class="status-badge">{{ __(str((string) $appraisal->status)->headline()->toString()) }}</span></td>
        </tr>
        <tr>
            <td class="meta-label">{{ __('Department') }}</td>
            <td class="meta-value">{{ $appraisal->user->division->name ?? '-' }}</td>
            <td class="meta-label">{{ __('Position') }}</td>
            <td class="meta-value">{{ $appraisal->user->jobTitle->name ?? '-' }}</td>
        </tr>
        @if ($appraisal->calibration_status)
            <tr>
                <td class="meta-label">{{ __('Calibration') }}</td>
                <td class="meta-value" colspan="3">
                    <span class="status-badge">{{ __(ucfirst((string) $appraisal->calibration_status)) }}</span>
                    @if ($appraisal->calibrator)
                        {{ __('by') }} {{ $appraisal->calibrator->name }}
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <div class="section-title">{{ __('Attendance Score') }} · {{ __('Weight') }} 30%</div>
    <table class="meta-table">
        <tr>
            <td class="meta-label">{{ __('System Attendance Score') }}</td>
            <td class="meta-value">
                <span class="score-box {{ $scoreClass($appraisal->attendance_score) }}">
                    {{ $appraisal->attendance_score }} / 100
                </span>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ __('KPI Performance Evaluation') }} · {{ __('Weight') }} 70%</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th style="width: 30%">{{ __('KPI Indicator') }}</th>
                <th style="width: 12%">{{ __('Weight') }}</th>
                <th style="width: 12%">{{ __('Self Score') }}</th>
                <th style="width: 12%">{{ __('Manager Score') }}</th>
                <th style="width: 34%">{{ __('Comments') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appraisal->evaluations as $evaluation)
                <tr>
                    <td><strong>{{ $evaluation->kpiTemplate->name ?? '-' }}</strong></td>
                    <td>{{ $evaluation->kpiTemplate->weight ?? 0 }}%</td>
                    <td>
                        @if ($evaluation->self_score)
                            <span class="score-box {{ $scoreClass($evaluation->self_score) }}">{{ $evaluation->self_score }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($evaluation->manager_score)
                            <span class="score-box {{ $scoreClass($evaluation->manager_score) }}">{{ $evaluation->manager_score }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $evaluation->comments ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('No KPI evaluations recorded.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="score-card">
        <table class="score-summary">
            <tr>
                <td>
                    <div class="score-label">{{ __('Final Weighted Score') }}</div>
                    <div class="score-grade">{{ $gradeLabel($appraisal->final_score) }}</div>
                </td>
                <td class="score-value">{{ $appraisal->final_score ?? __('N/A') }}</td>
            </tr>
        </table>
    </div>

    @if ($appraisal->notes)
        <div class="section-title">{{ __('Manager Notes') }}</div>
        <div class="notes-box">{{ $appraisal->notes }}</div>
    @endif

    @if ($appraisal->calibration_notes)
        <div class="section-title">{{ __('Calibration Notes') }} · {{ __('HR Director') }}</div>
        <div class="notes-box">{{ $appraisal->calibration_notes }}</div>
    @endif

    @if ($appraisal->meeting_date)
        <div class="section-title">{{ __('1-on-1 Session') }}</div>
        <table class="meta-table">
            <tr>
                <td class="meta-label">{{ __('Meeting Date') }}</td>
                <td class="meta-value">{{ $appraisal->meeting_date->translatedFormat('d M Y') }}</td>
            </tr>
            @if ($appraisal->meeting_link)
                <tr>
                    <td class="meta-label">{{ __('Virtual Meeting Link') }}</td>
                    <td class="meta-value">{{ $appraisal->meeting_link }}</td>
                </tr>
            @endif
        </table>
    @endif

    <div class="signature-area">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $appraisal->user->name }}</div>
                    <div class="signature-role">{{ __('Employee') }}</div>
                    @if ($appraisal->employee_acknowledgement)
                        <div class="acknowledged">{{ __('Acknowledged') }}</div>
                    @endif
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $appraisal->evaluator->name ?? '-' }}</div>
                    <div class="signature-role">{{ __('Direct Manager') }}</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $appraisal->calibrator->name ?? '-' }}</div>
                    <div class="signature-role">{{ __('HR Director / Calibrator') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ __('This document is system-generated by :company HR System.', ['company' => $companyName]) }}
        {{ __('Document ID') }}: {{ $documentId }} · {{ __('Printed') }}: {{ now()->translatedFormat('d M Y H:i') }}
    </div>
</body>
</html>
