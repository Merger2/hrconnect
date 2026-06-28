@props(['headers' => [], 'key' => 'id'])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if (isset($rows) && $rows->isNotEmpty())
        @foreach ($rows as $row)
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm">
                @if (count($headers))
                    <div class="border-b border-outline-variant/50 bg-surface-container-low px-4 py-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">
                                #{{ $row[$key] ?? $loop->iteration }}
                            </span>
                        </div>
                    </div>
                @endif
                <div class="divide-y divide-outline-variant/30 px-4 py-2 text-sm">
                    {{ $row }}
                </div>
            </div>
        @endforeach
    @elseif (isset($slot))
        {{ $slot }}
    @else
        <div class="rounded-xl border border-dashed border-outline-variant bg-canvas px-4 py-12 text-center text-sm text-on-surface-variant">
            {{ __('No data') }}
        </div>
    @endif
</div>
