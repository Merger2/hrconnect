<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            color: #111827;
            margin: 0;
            padding: 2rem;
            font-size: 13px;
            line-height: 1.6;
        }
        @page {
            margin: 24mm 20mm;
            size: {{ $paperSize ?? 'a4' }} {{ $orientation ?? 'portrait' }};
        }
        .document-header {
            border-bottom: 2px solid #16a34a;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .document-header h1 {
            margin: 0;
            font-size: 18px;
            color: #14532d;
        }
        .document-body {
            margin-bottom: 1.5rem;
        }
        .document-footer {
            border-top: 1px solid #d1d5db;
            padding-top: 0.75rem;
            margin-top: 2rem;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    @if (! empty($header))
        <div class="document-header">{!! $header !!}</div>
    @endif

    <div class="document-body">
        {!! $body !!}
    </div>

    @if (! empty($footer))
        <div class="document-footer">{!! $footer !!}</div>
    @endif
</body>
</html>
