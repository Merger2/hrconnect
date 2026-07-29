<x-admin.page-shell
    :title="__('Command Center')"
    :description="__('One operational cockpit for approvals, field work, forms, pipeline, and invoice risk.')"
    :show-description="true"
>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                @php
                    $toneClass = match($card['tone']) {
                        'warning' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100',
                        'danger' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-100',
                        'primary' => 'border-primary-200 bg-primary-50 text-primary-900 dark:border-primary-900/40 dark:bg-primary-950/30 dark:text-primary-100',
                        'info' => 'border-cyan-200 bg-cyan-50 text-cyan-900 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-100',
                        default => 'border-slate-200 bg-white text-slate-950 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                    };
                    $value = in_array($card['tone'], ['primary', 'info'], true)
                        ? \Illuminate\Support\Number::currency((float) $card['value'], 'IDR', 'id')
                        : $card['value'];
                @endphp

                <a
                    href="{{ $card['href'] ?? '#' }}"
                    class="block rounded-xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $toneClass }}"
                >
                    <div class="text-xs font-bold uppercase tracking-wide opacity-70">{{ $card['label'] }}</div>
                    <div class="mt-2 text-2xl font-black">{{ $value }}</div>
                    <p class="mt-2 text-sm leading-5 opacity-75">{{ $card['description'] }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
            <x-admin.panel>
                <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Action Queues') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Start from the queues with the highest operational risk.') }}</p>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($queues as $queue)
                        @php
                            $queueTone = match($queue['tone']) {
                                'cyan' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/50 dark:text-cyan-300',
                                'teal' => 'bg-teal-100 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300',
                                'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
                                'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            };
                        @endphp

                        <a href="{{ $queue['href'] }}" class="flex items-center gap-4 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-900/60">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-black {{ $queueTone }}">
                                {{ $queue['count'] > 99 ? '99+' : $queue['count'] }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-slate-950 dark:text-white">{{ $queue['title'] }}</span>
                                <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">{{ $queue['description'] }}</span>
                            </span>
                            <x-heroicon-o-chevron-right class="h-5 w-5 text-slate-400" />
                        </a>
                    @endforeach
                </div>
            </x-admin.panel>

            <x-admin.panel>
                <div class="border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Workspace Snapshot') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Scoped to companies this admin can access.') }}</p>
                </div>
                <dl class="grid grid-cols-2 gap-3 p-4">
                    @foreach ([
                        __('Companies') => $summary['companies'],
                        __('Active Projects') => $summary['active_projects'],
                        __('Pending Forms') => $summary['pending_forms'],
                        __('Pending WFH') => $summary['pending_wfh'],
                        __('Low Stock') => $summary['low_stock_products'],
                        __('Overdue HR Tasks') => $summary['overdue_hr_tasks'],
                        __('Overdue Invoices') => $summary['overdue_invoices'],
                    ] as $label => $value)
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-950/50">
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            <dd class="mt-1 text-xl font-black text-slate-950 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    <div class="rounded-xl bg-slate-950 p-4 text-white dark:bg-black">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('Weighted Pipeline') }}</div>
                        <div class="mt-2 text-2xl font-black">{{ \Illuminate\Support\Number::currency((float) $summary['weighted_pipeline'], 'IDR', 'id') }}</div>
                        <p class="mt-2 text-sm text-slate-300">{{ __('Probability-adjusted sales value for active opportunities.') }}</p>
                    </div>
                </div>
            </x-admin.panel>
        </div>
    </div>
</x-admin.page-shell>
