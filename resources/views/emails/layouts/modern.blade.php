@php
    $companyName = \App\Support\MailBranding::companyName();
    $homeUrl = config('app.url', url('/'));
    $emailTitle = $title ?? $companyName;
    $logoUrl = \App\Support\MailBranding::logoMailSource($message ?? null);
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>{{ $emailTitle }}</title>
    <style type="text/css">
        body,
        body *:not(html):not(style):not(br):not(tr):not(code) {
            box-sizing: border-box;
            font-family: Figtree, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            position: relative;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100%;
            -webkit-text-size-adjust: none;
            background-color: {{ design_token('brand-green-50') }};
            color: {{ design_token('brand-green-950') }};
        }

        a {
            color: {{ design_token('brand-green-600') }};
            text-decoration: none;
        }

        p,
        ul,
        ol,
        blockquote {
            margin: 0 0 16px;
            color: {{ design_token('muted-green-700') }};
            font-size: 15px;
            line-height: 1.75;
            text-align: left;
        }

        h1 {
            margin: 0 0 18px;
            color: {{ design_token('brand-green-950') }};
            font-size: 28px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.03em;
            text-align: left;
        }

        h2 {
            margin: 28px 0 12px;
            color: {{ design_token('brand-green-950') }};
            font-size: 18px;
            font-weight: 800;
            line-height: 1.35;
            text-align: left;
        }

        h3 {
            margin: 20px 0 10px;
            color: {{ design_token('brand-green-950') }};
            font-size: 15px;
            font-weight: 700;
            line-height: 1.45;
            text-align: left;
        }

        .wrapper {
            width: 100%;
            background:
                radial-gradient(circle at top left, {{ design_rgba('brand-green-600', 0.10) }}, transparent 32%),
                {{ design_token('brand-green-50') }};
            margin: 0;
            padding: 32px 12px;
        }

        .content {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .brand {
            width: 100%;
            margin-bottom: 22px;
        }

        .brand-card {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .brand-link {
            display: inline-block;
            text-decoration: none;
        }

        .brand-mark {
            height: 42px;
            width: 42px;
            border-radius: 14px;
            display: block;
            object-fit: cover;
            border: 1px solid {{ design_rgba('brand-green-600', 0.15) }};
            box-shadow: 0 16px 28px -24px {{ design_rgba('brand-green-950', 0.45) }};
        }

        .brand-name {
            color: {{ design_token('brand-green-950') }};
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
            padding-left: 12px;
        }

        .body {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .inner-body {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 24px;
            background: {{ design_token('surface') }};
            border: 1px solid {{ design_rgba('brand-green-600', 0.14) }};
            box-shadow: 0 26px 60px -38px {{ design_rgba('brand-green-950', 0.32) }};
            overflow: hidden;
        }

        .body-accent {
            height: 6px;
            background: linear-gradient(90deg, {{ design_token('brand-green-500') }} 0%, {{ design_token('brand-green-600') }} 50%, {{ design_token('brand-green-700') }} 100%);
        }

        .content-cell {
            padding: 34px 36px 30px;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 16px;
            padding: 8px 12px;
            border-radius: 999px;
            background: {{ design_token('brand-green-100') }};
            color: {{ design_token('brand-green-700') }};
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .email-section {
            margin: 22px 0 0;
            padding: 18px 20px;
            border-radius: 18px;
            background: {{ design_token('brand-green-50') }};
            border: 1px solid {{ design_token('brand-green-200') }};
        }

        .email-section--subtle {
            background: {{ design_token('surface') }};
            border-style: dashed;
        }

        .email-data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .email-data-table td {
            padding: 9px 0;
            vertical-align: top;
            border-bottom: 1px solid {{ design_token('brand-green-50') }};
            font-size: 14px;
            line-height: 1.65;
            word-break: break-word;
        }

        .email-data-table tr:last-child td {
            border-bottom: 0;
        }

        .email-data-label {
            width: 150px;
            padding-right: 16px !important;
            color: {{ design_token('muted-green-600') }};
            font-weight: 700;
        }

        .email-data-value {
            color: {{ design_token('brand-green-950') }};
            font-weight: 600;
        }

        .email-note {
            margin: 18px 0 0;
            padding: 16px 18px;
            border-radius: 16px;
            border-left: 4px solid {{ design_token('brand-green-600') }};
            background: {{ design_token('brand-green-50') }};
            color: {{ design_token('muted-green-800') }};
            font-size: 14px;
            line-height: 1.7;
        }

        .email-note--warning {
            border-left-color: {{ design_token('warning') }};
            background: {{ design_token('warning-soft') }};
            color: {{ design_token('warning-deep') }};
        }

        .email-steps {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .email-step-row td {
            padding: 10px 0;
            vertical-align: top;
        }

        .email-step-badge {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-block;
            background: linear-gradient(135deg, {{ design_token('brand-green-500') }} 0%, {{ design_token('brand-green-700') }} 100%);
            color: {{ design_token('surface') }};
            font-size: 14px;
            font-weight: 800;
            line-height: 34px;
            text-align: center;
        }

        .email-step-copy {
            padding-left: 14px !important;
        }

        .email-step-title {
            color: {{ design_token('brand-green-950') }};
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .email-step-text {
            color: {{ design_token('muted-green-700') }};
            font-size: 13px;
            line-height: 1.65;
            margin: 0;
        }

        .button {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, {{ design_token('brand-green-500') }} 0%, {{ design_token('brand-green-700') }} 100%);
            color: {{ design_token('surface') }} !important;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            line-height: 1.15;
            text-transform: uppercase;
            box-shadow: 0 14px 28px -22px {{ design_rgba('brand-green-700', 0.8) }};
            text-decoration: none;
            white-space: nowrap;
            word-break: keep-all;
        }

        .subcopy {
            border-top: 1px solid {{ design_token('brand-green-100') }};
            margin-top: 26px;
            padding-top: 18px;
        }

        .subcopy p {
            margin-bottom: 10px;
            color: {{ design_token('muted-green-600') }};
            font-size: 12px;
            line-height: 1.65;
        }

        .footer {
            width: 100%;
        }

        .footer-card {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .footer p {
            margin: 18px 0 0;
            color: {{ design_token('muted-green-500') }};
            font-size: 12px;
            line-height: 1.7;
            text-align: center;
        }

        @media only screen and (max-width: 620px) {
            .wrapper {
                padding: 20px 10px;
            }

            .content-cell {
                padding: 28px 22px 24px;
            }

            .email-data-label,
            .email-data-value,
            .email-data-table td {
                display: block;
                width: 100%;
            }

            .email-data-label {
                padding-bottom: 2px !important;
            }
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="brand" align="center">
                            <table class="brand-card" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left">
                                        <a href="{{ $homeUrl }}" class="brand-link" target="_blank" rel="noopener">
                                            <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                    <td>
                                                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="brand-mark" width="42" height="42" style="display: block;">
                                                    </td>
                                                    <td class="brand-name">
                                                        {{ $companyName }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="body" align="center">
                            <table class="inner-body" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="body-accent"></td>
                                </tr>
                                <tr>
                                    <td class="content-cell">
                                        @if (!empty($eyebrow))
                                            <div class="eyebrow">{{ $eyebrow }}</div>
                                        @endif

                                        {!! Illuminate\Mail\Markdown::parse($slot) !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="footer" align="center">
                            <table class="footer-card" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="center">
                                        <p>
                                            © {{ date('Y') }} {{ $companyName }}. {{ __('All rights reserved.') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
