@php
    $primaryAction = $primaryAction ?? null;
    $secondaryAction = $secondaryAction ?? null;
    $details = array_values(array_filter($details ?? []));

    $tone = $tone ?? 'primary';
    $toneClasses = match ($tone) {
        'amber' => [
            'badge' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
            'icon' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
            'dot' => 'bg-amber-500',
            'panel' => 'border-amber-100 bg-amber-50/50',
        ],
        'red' => [
            'badge' => 'bg-rose-50 text-rose-800 ring-1 ring-rose-200',
            'icon' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200',
            'dot' => 'bg-red-600',
            'panel' => 'border-rose-100 bg-rose-50/50',
        ],
        'blue' => [
            'badge' => 'bg-sky-50 text-sky-800 ring-1 ring-sky-200',
            'icon' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200',
            'dot' => 'bg-sky-600',
            'panel' => 'border-sky-100 bg-sky-50/50',
        ],
        'slate' => [
            'badge' => 'bg-slate-100 text-slate-800 ring-1 ring-slate-200',
            'icon' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
            'dot' => 'bg-slate-600',
            'panel' => 'border-slate-200 bg-slate-50/70',
        ],
        default => [
            'badge' => 'bg-primary-50 text-primary-800 ring-1 ring-primary-200',
            'icon' => 'bg-primary-100 text-primary-800 ring-1 ring-primary-200',
            'dot' => 'bg-primary-700',
            'panel' => 'border-primary-100 bg-primary-50/50',
        ],
    };

    $icon = match ($tone) {
        'amber' => 'heroicon-o-exclamation-triangle',
        'red' => 'heroicon-o-x-circle',
        'blue' => 'heroicon-o-clock',
        'slate' => 'heroicon-o-lock-closed',
        default => 'heroicon-o-information-circle',
    };
@endphp

<div class="flex min-h-full flex-col justify-center gap-6">
    <div class="flex items-start justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <span class="inline-grid h-14 w-14 shrink-0 place-items-center rounded-[1.35rem] {{ $toneClasses['icon'] }}">
                <x-dynamic-component :component="$icon" class="h-6 w-6" />
            </span>

            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $eyebrow ?? __('System notice') }}</p>
                <h1 id="error-page-title" class="mt-2 text-2xl font-semibold leading-tight tracking-tight text-slate-950">
                    {{ $titleText }}
                </h1>
            </div>
        </div>

        <span class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-full px-3 text-sm font-black {{ $toneClasses['badge'] }}">
            {{ $status }}
        </span>
    </div>

    <div class="rounded-[1.5rem] border p-4 {{ $toneClasses['panel'] }}">
        <p class="text-base leading-7 text-slate-700">
            {{ $summary }}
        </p>
    </div>

    @if ($details !== [])
        <section aria-labelledby="error-details-title" class="space-y-3">
            <h2 id="error-details-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                {{ __('What this usually means') }}
            </h2>

            <ul class="space-y-2 text-sm leading-6 text-slate-600">
                @foreach ($details as $detail)
                    <li class="flex gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full {{ $toneClasses['dot'] }}" aria-hidden="true"></span>
                        <span>{{ $detail }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-3" aria-label="{{ __('Available actions') }}">
        @if ($primaryAction)
            <a href="{{ $primaryAction['href'] }}"
               class="wcag-touch-target inline-flex min-h-[3.35rem] items-center justify-center rounded-[1.35rem] bg-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2">
                {{ $primaryAction['label'] }}
            </a>
        @endif

        @if ($secondaryAction)
            <a href="{{ $secondaryAction['href'] }}"
               class="wcag-touch-target inline-flex min-h-[3.35rem] items-center justify-center rounded-[1.35rem] border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
                {{ $secondaryAction['label'] }}
            </a>
        @endif

        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
            class="wcag-touch-target inline-flex min-h-[3.35rem] items-center justify-center gap-2 rounded-[1.35rem] px-4 py-3 text-sm font-semibold text-slate-500 transition hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            <span>{{ __('Go back') }}</span>
        </a>
    </div>
</div>
