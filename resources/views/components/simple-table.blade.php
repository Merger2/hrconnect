@props(['headers' => [], 'striped' => true, 'hover' => true])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-outline-variant shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            @if (count($headers))
                <thead class="bg-surface-dim">
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col" class="px-4 py-3 font-semibold text-on-surface-variant {{ $loop->first ? '' : '' }}">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-outline-variant/50 bg-canvas">
                @if (isset($rows) && $rows->isNotEmpty())
                    @foreach ($rows as $row)
                        <tr @class([
                            'transition-colors',
                            'hover:bg-surface-dim' => $hover,
                            'even:bg-surface-dim/30' => $striped,
                        ])>
                            {{ $row }}
                        </tr>
                    @endforeach
                @elseif (isset($slot))
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="{{ count($headers) ?: '99' }}" class="px-4 py-12 text-center text-sm text-on-surface-variant">
                            {{ __('No data') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if (isset($footer))
        <div class="border-t border-outline-variant/50 bg-canvas px-4 py-3">{{ $footer }}</div>
    @endif
</div>
