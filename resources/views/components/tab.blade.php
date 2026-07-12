@props([
    'tabs' => [],
    'active' => null,
    'variant' => 'pill',
])

@php
$activeKey = $active ?? ($tabs[0]['key'] ?? null);
@endphp

<div x-data="{ activeTab: '{{ $activeKey }}' }" @click.outside="">
<div @class([
        'flex gap-1 overflow-x-auto',
        $variant === 'underline' ? 'border-b border-outline-variant' : '',
    ])>
        @foreach ($tabs as $tab)
        @php
            $key = $tab['key'] ?? '';
            $label = $tab['label'] ?? '';
            $icon = $tab['icon'] ?? null;
            $badge = $tab['badge'] ?? null;
        @endphp

        <button
            type="button"
            @click="activeTab = '{{ $key }}'; $dispatch('tab-changed', '{{ $key }}')"
            @class([
                'flex shrink-0 items-center gap-2 text-sm font-medium transition-smooth whitespace-nowrap',
                $variant === 'pill' => 'px-5 py-2',
                $variant === 'underline' => 'pb-2 px-1',
            ])
            :class="@js($variant === 'pill'
                ? (activeTab === '{{ $key }}' ? 'rounded-pill bg-ink text-on-primary' : 'rounded-pill text-ink hover:bg-cloud')
                : (activeTab === '{{ $key }}' ? 'border-b-2 border-primary text-primary' : 'border-b-2 border-transparent text-on-surface-variant hover:text-ink')
            )"
        >
            @if ($icon)
                <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
            @endif
            {{ $label }}
            @if ($badge !== null)
                <span class="ml-0.5 inline-flex min-w-[1.25rem] justify-center rounded-pill bg-error/15 px-1.5 py-0.5 text-caption-sm font-bold text-error">{{ $badge }}</span>
            @endif
        </button>
        @endforeach
    </div>
</div>