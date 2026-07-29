<div class="user-content-panel">

    {{-- Header --}}
    <div class="user-content-panel__header">
        <h3 class="user-content-panel__title">
            <span class="user-content-panel__mark">
                <x-heroicon-o-calendar-days class="h-4 w-4" />
            </span>
            {{ __('Upcoming Events') }}
        </h3>
    </div>

    <div class="p-4 relative z-10 min-h-[100px]">
        @if(!$hasEvents)
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                    <x-heroicon-o-calendar-days class="h-6 w-6 text-gray-300 dark:text-gray-600" />
                </div>
                <p class="text-xs text-gray-400">{{ __('No upcoming events.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                
                {{-- Announcements --}}
                @if($announcements->isNotEmpty())
                    @foreach($announcements as $announcement)
                         <button type="button" wire:click="showAnnouncement({{ $announcement->id }})" class="flex w-full items-center gap-2.5 rounded-xl border border-blue-100 bg-blue-50/50 p-2 text-left transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-blue-800/30 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 sm:col-span-2" aria-label="{{ __('View event details') }}: {{ $announcement->title }}">
                            <div class="flex h-8 w-8 shrink-0 flex-col items-center justify-center rounded-full border border-blue-100 bg-white text-blue-500 dark:border-blue-800/30 dark:bg-gray-800">
                                <x-heroicon-o-megaphone class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $announcement->title }}</p>
                                    @if($announcement->priority === 'high')
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ Str::limit(strip_tags($announcement->content), 40) }}</p>
                            </div>
                            <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-blue-300 dark:text-blue-500" />
                        </button>
                    @endforeach
                @endif

                {{-- Holidays --}}
                @if($holidays->isNotEmpty())
                    @foreach($holidays as $holiday)
                        <button type="button" wire:click="showHoliday({{ $holiday->id }})" class="flex w-full items-center gap-2.5 rounded-xl border border-rose-100 bg-rose-50/50 p-2 text-left transition hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-rose-800/30 dark:bg-rose-900/20 dark:hover:bg-rose-900/30" aria-label="{{ __('View event details') }}: {{ $holiday->name }}">
                            <div class="flex h-8 w-8 shrink-0 flex-col items-center justify-center rounded-full border border-rose-100 bg-white dark:border-rose-800/30 dark:bg-gray-800">
                                <span class="text-[8px] font-bold text-rose-500 uppercase tracking-tighter leading-none mb-0.5">{{ $holiday->date->shortMonthName }}</span>
                                <span class="text-xs font-black text-gray-900 dark:text-white leading-none">{{ $holiday->date->day }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $holiday->name }}</p>
                                <span class="text-[9px] font-medium text-rose-500 bg-rose-100 dark:bg-rose-900/50 px-1.5 py-0.5 rounded">{{ __('Holiday') }}</span>
                            </div>
                            <x-heroicon-o-chevron-right class="ml-auto h-4 w-4 shrink-0 text-rose-300 dark:text-rose-500" />
                        </button>
                    @endforeach
                @endif

                {{-- Birthdays --}}
                @if($birthdays->isNotEmpty())
                    @foreach($birthdays as $user)
                        <button type="button" wire:click="showBirthday('{{ $user->id }}')" class="flex w-full items-center gap-2.5 rounded-xl border border-amber-100 bg-amber-50/50 p-2 text-left transition hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-amber-800/30 dark:bg-amber-900/20 dark:hover:bg-amber-900/30" aria-label="{{ __('View event details') }}: {{ $user->name }}">
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-lg object-cover border border-amber-100 dark:border-amber-800/30">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[140px]">{{ $user->name }}</p>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <x-heroicon-o-cake class="h-3.5 w-3.5 text-amber-500" />
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($user->birth_date)->format('d M') }}
                                    </span>
                                </div>
                            </div>
                            <x-heroicon-o-chevron-right class="ml-auto h-4 w-4 shrink-0 text-amber-300 dark:text-amber-500" />
                        </button>
                    @endforeach
                @endif
            </div>
        @endif
    </div>

    @if($selectedEvent)
        <div class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/45 px-4 py-5 backdrop-blur-sm sm:items-center" role="dialog" aria-modal="true" aria-labelledby="upcoming-event-detail-title" wire:click.self="closeEvent">
            <div class="w-full max-w-md overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-3 border-b border-slate-200/80 p-5 dark:border-slate-800">
                    <span @class([
                        'grid h-11 w-11 shrink-0 place-items-center rounded-full',
                        'bg-amber-50 text-amber-700 dark:bg-amber-950/45 dark:text-amber-200' => $selectedEvent['tone'] === 'warning',
                        'bg-rose-50 text-rose-700 dark:bg-rose-950/45 dark:text-rose-200' => $selectedEvent['tone'] === 'danger',
                        'bg-sky-50 text-sky-700 dark:bg-sky-950/45 dark:text-sky-200' => $selectedEvent['tone'] === 'info',
                    ])>
                        <x-heroicon-o-calendar-days class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">{{ $selectedEvent['type'] }}</p>
                        <h4 id="upcoming-event-detail-title" class="mt-1 text-lg font-bold leading-tight text-slate-950 dark:text-white">{{ $selectedEvent['title'] }}</h4>
                        @if($selectedEvent['subtitle'])
                            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-300">{{ $selectedEvent['subtitle'] }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closeEvent" class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="{{ __('Close') }}">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                @if($selectedEvent['body'])
                    <div class="p-5">
                        <p class="whitespace-pre-line text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $selectedEvent['body'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
