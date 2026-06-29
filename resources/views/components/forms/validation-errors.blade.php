@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-error/30 bg-error/5 px-4 py-3']) }} role="alert" aria-live="assertive">
        <div class="font-semibold text-error">{{ __('Ada masalah dengan input Anda.') }}</div>

        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-error/90">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
