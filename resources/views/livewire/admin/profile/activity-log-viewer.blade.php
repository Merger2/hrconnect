<x-form-section submit="">
    <x-slot name="title">
        {{ __('Audit Trails & Activity Logs') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Review your recent actions and activities in the system. All logs are cryptographically hashed to ensure integrity.') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6">
            @if($logs->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center dark:border-slate-700/50 dark:bg-slate-800/50">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No activity logs found.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/50">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm dark:divide-slate-700/50">
                        <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Action') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Description') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('IP Address') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700/50 dark:bg-slate-900/50">
                            @foreach ($logs as $log)
                                <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @if($log->hasValidIntegrityHash())
                                                <x-heroicon-s-check-badge class="h-4 w-4 text-emerald-500" title="{{ __('Hash valid') }}" />
                                            @else
                                                <x-heroicon-s-exclamation-triangle class="h-4 w-4 text-rose-500" title="{{ __('Integrity compromised') }}" />
                                            @endif
                                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ Str::headline($log->action) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                        {{ $log->description ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-mono text-xs">
                                        {{ $log->ip_address ?: 'Unknown' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-slate-500">
                                        {{ $log->created_at->format('M d, Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </div>
    </x-slot>
</x-form-section>
