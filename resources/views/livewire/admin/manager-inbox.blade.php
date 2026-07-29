<div>
    <x-admin.page-shell :title="__('Manager Inbox')" :description="__('Review and process pending approvals across all modules.')">
        <div class="space-y-4">
            @php
                $tabConfigs = [
                    'leaves' => ['label' => __('Leaves'), 'icon' => 'heroicon-o-calendar', 'color' => 'blue'],
                    'overtime' => ['label' => __('Overtime'), 'icon' => 'heroicon-o-clock', 'color' => 'indigo'],
                    'attendance_corrections' => ['label' => __('Corrections'), 'icon' => 'heroicon-o-adjustments-horizontal', 'color' => 'orange'],
                    'reimbursements' => ['label' => __('Reimbursements'), 'icon' => 'heroicon-o-banknotes', 'color' => 'green'],
                    'cash_advances' => ['label' => __('Cash Advances'), 'icon' => 'heroicon-o-currency-dollar', 'color' => 'emerald'],
                    'shift_swaps' => ['label' => __('Shift Swaps'), 'icon' => 'heroicon-o-arrows-right-left', 'color' => 'purple'],
                    'wfh_requests' => ['label' => __('WFH'), 'icon' => 'heroicon-o-home-modern', 'color' => 'cyan'],
                    'document_requests' => ['label' => __('Documents'), 'icon' => 'heroicon-o-document-text', 'color' => 'gray'],
                    'hr_tasks' => ['label' => __('HR Tasks'), 'icon' => 'heroicon-o-clipboard-document-check', 'color' => 'rose'],
                    'custom_forms' => ['label' => __('Forms'), 'icon' => 'heroicon-o-clipboard-document-list', 'color' => 'teal'],
                ];

                $tabs = array_intersect_key($tabConfigs, array_flip($availableTabs ?? []));
                $activeTabId = 'manager-inbox-tab-'.str_replace('_', '-', $activeTab);
                $summary = $this->summary;
            @endphp

            <dl class="grid grid-cols-3 gap-2">
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Pending') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950 dark:text-white">{{ $summary['pending'] }}</dd>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 shadow-sm dark:border-amber-900/40 dark:bg-amber-900/20">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ __('Overdue') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-amber-900 dark:text-amber-100">{{ $summary['overdue'] }}</dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Workflows') }}</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950 dark:text-white">{{ $summary['workflows'] }}</dd>
                </div>
            </dl>

            <nav
                class="grid grid-cols-2 gap-1.5 rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex sm:flex-wrap"
                role="tablist"
                aria-label="{{ __('Manager Inbox') }}"
            >
                @foreach($tabs as $key => $tab)
                    @php
                        $count = $this->counts[$key] ?? 0;
                        $overdueCount = $this->overdueCounts[$key] ?? 0;
                        $isActive = $activeTab === $key;
                        $tabId = 'manager-inbox-tab-'.str_replace('_', '-', $key);
                        $statusLabel = $count > 0 ? $count.' '.__('pending') : __('Caught up');
                        
                        $iconColors = match($tab['color']) {
                            'blue' => 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30',
                            'indigo' => 'text-indigo-600 bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30',
                            'orange' => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30',
                            'green' => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30',
                            'emerald' => 'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30',
                            'purple' => 'text-purple-600 bg-purple-100 dark:text-purple-400 dark:bg-purple-900/30',
                            'cyan' => 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-cyan-900/30',
                            'rose' => 'text-rose-600 bg-rose-100 dark:text-rose-400 dark:bg-rose-900/30',
                            'teal' => 'text-teal-600 bg-teal-100 dark:text-teal-400 dark:bg-teal-900/30',
                            'gray' => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700',
                            default => 'text-primary-600 bg-primary-100 dark:text-primary-400 dark:bg-primary-900/30'
                        };
                        
                        $inactiveIconColor = 'text-gray-400 bg-gray-100 dark:text-gray-500 dark:bg-gray-800/50';
                    @endphp
                    <button
                        type="button"
                        wire:click="switchTab('{{ $key }}')"
                        id="{{ $tabId }}"
                        role="tab"
                        aria-controls="manager-inbox-panel"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        aria-label="{{ $tab['label'] }}: {{ $statusLabel }}"
                        class="wcag-touch-target inline-flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium leading-tight transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:justify-start sm:gap-2 sm:px-3 sm:text-sm sm:font-semibold
                        {{ $isActive
                            ? 'bg-primary-700 text-white shadow-sm dark:bg-primary-500 dark:text-white'
                            : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 hover:text-gray-950 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700 dark:hover:text-white' }}"
                    >
                        <span class="hidden h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $count > 0 || $isActive ? $iconColors : $inactiveIconColor }} sm:inline-flex" aria-hidden="true">
                            @svg($tab['icon'], 'h-4 w-4')
                        </span>
                        <span class="min-w-0 truncate">{{ $tab['label'] }}</span>
                        @if($count > 0)
                            <span class="ml-0.5 inline-flex min-w-6 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-xs font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800" aria-hidden="true">
                                {{ $count > 99 ? '99+' : $count }}
                            </span>
                        @endif
                        @if($overdueCount > 0)
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 py-0.5 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800" title="{{ __('Overdue') }}" aria-hidden="true">
                                {{ $overdueCount > 9 ? '9+' : $overdueCount }}
                            </span>
                        @endif
                        <span class="sr-only">{{ $statusLabel }}</span>
                    </button>
                @endforeach
            </nav>

            <!-- List Content -->
            <div
                id="manager-inbox-panel"
                role="tabpanel"
                aria-labelledby="{{ $activeTabId }}"
                tabindex="0"
                class="space-y-4"
            >
                <div class="mt-2 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $tabs[$activeTab]['label'] ?? __('Items') }}</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Overdue means pending for more than :days days.', ['days' => $overdueAfterDays]) }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <button type="button" wire:click="setStatusFilter('pending')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $statusFilter === 'pending' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                {{ __('Pending') }}
                            </button>
                            <button type="button" wire:click="setStatusFilter('overdue')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $statusFilter === 'overdue' ? 'bg-amber-500 text-white' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                {{ __('Overdue') }}
                            </button>
                            @if($activeTab === 'hr_tasks')
                                <button type="button" wire:click="setStatusFilter('blocked')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $statusFilter === 'blocked' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                    {{ __('Blocked') }}
                                </button>
                            @endif
                        </div>
                    <div class="relative w-full sm:w-72">
                        <x-forms.input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employee...') }}" class="w-full text-sm pl-10 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm" />
                        <span class="absolute left-3 top-2.5 text-gray-400">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                        </span>
                    </div>
                    </div>
                </div>

                @if($items->isEmpty())
                    <div class="flex min-h-[12rem] w-full items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800 md:min-h-[calc(100vh-19rem)]">
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-50 dark:bg-success-900/20">
                                <x-heroicon-o-check-badge class="h-6 w-6 text-success-500" />
                            </div>
                            <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Inbox Zero!') }}</h3>
                            <p class="sr-only">{{ __('You have caught up with all your pending requests.') }}</p>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($items as $item)
                            @php
                                $detailRoute = match($activeTab) {
                                    'leaves' => route('admin.leaves'),
                                    'overtime' => route('admin.overtime'),
                                    'attendance_corrections' => route('admin.attendance-corrections'),
                                    'reimbursements' => route('admin.reimbursements'),
                                    'cash_advances' => route('admin.manage-kasbon'),
                                    'shift_swaps' => route('admin.shift-swaps'),
                                    'wfh_requests' => route('admin.inbox', ['activeTab' => 'wfh_requests']),
                                    'document_requests' => route('admin.document-requests'),
                                    'hr_tasks' => route('admin.hr-checklists'),
                                    'custom_forms' => route('admin.custom-forms'),
                                    default => '#'
                                };

                                $employee = match($activeTab) {
                                    'hr_tasks' => $item->case?->user,
                                    'custom_forms' => $item->submitter,
                                    default => $item->user,
                                };

                                $accentColor = match($tabs[$activeTab]['color']) {
                                    'blue' => 'bg-blue-500',
                                    'indigo' => 'bg-indigo-500',
                                    'orange' => 'bg-orange-500',
                                    'green' => 'bg-green-500',
                                    'emerald' => 'bg-emerald-500',
                                    'purple' => 'bg-purple-500',
                                    'cyan' => 'bg-cyan-500',
                                    'rose' => 'bg-rose-500',
                                    'teal' => 'bg-teal-500',
                                    'gray' => 'bg-gray-500',
                                    default => 'bg-primary-500'
                                };
                            @endphp
                            <div class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600" wire:key="item-{{ $item->id }}">
                                <!-- Top Accent Bar -->
                                <div class="h-1 w-full {{ $accentColor }}"></div>
                            
                            <div class="flex flex-1 flex-col p-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $employee?->profile_photo_url }}" alt="{{ $employee?->name ?? __('Unknown user') }}" class="h-10 w-10 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-gray-800">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="truncate text-base font-bold text-gray-900 dark:text-white">{{ $employee?->name ?? __('Unknown user') }}</h3>
                                        <p class="truncate text-xs font-medium text-gray-500">{{ $employee?->jobTitle?->name ?? __('Employee') }}</p>
                                    </div>
                                    
                                    <a href="{{ $detailRoute }}" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors dark:bg-gray-800 dark:hover:bg-gray-700" title="{{ __('View Module') }}">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                    </a>
                                </div>
                                
                                <div class="mt-3 flex-1 rounded-lg bg-gray-50/50 p-3 dark:bg-gray-900/20 border border-gray-100 dark:border-gray-700/50">
                                    <div class="flex flex-col gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        @if($activeTab === 'leaves')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Leave Type') }}</span> <span class="font-bold text-primary-600 dark:text-primary-400">{{ $item->leaveType->name }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}</span></div>
                                            @if($item->reason)<div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ $item->reason }}</div>@endif
                                        
                                        @elseif($activeTab === 'overtime')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Time') }}</span> <span>{{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->end_time)->format('H:i') }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Duration') }}</span> <span class="rounded-md bg-indigo-50 px-2 py-0.5 font-bold text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">{{ $item->duration_text }}</span></div>
                                        
                                        @elseif($activeTab === 'attendance_corrections')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Type') }}</span> <span class="font-bold text-orange-600 dark:text-orange-400">{{ $item->requestTypeLabel() }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->attendance_date)->translatedFormat('d M Y') }}</span></div>
                                        
                                        @elseif($activeTab === 'reimbursements')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Type') }}</span> <span class="font-bold text-gray-900 dark:text-white">{{ ucfirst($item->type) }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Amount') }}</span> <span class="font-bold text-green-600 dark:text-green-400 text-lg">{{ \Illuminate\Support\Number::currency($item->amount, 'IDR', 'id') }}</span></div>
                                        
                                        @elseif($activeTab === 'cash_advances')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Amount') }}</span> <span class="font-bold text-emerald-600 dark:text-emerald-400 text-lg">{{ \Illuminate\Support\Number::currency($item->amount, 'IDR', 'id') }}</span></div>
                                            <div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ $item->purpose }}</div>
                                        
                                        @elseif($activeTab === 'shift_swaps')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->schedule->date)->translatedFormat('d M Y') }}</span></div>
                                            <div class="flex justify-between items-center mt-2 p-2 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700">
                                                <span class="text-xs font-semibold text-gray-500">{{ $item->schedule->shift->name }}</span>
                                                <x-heroicon-o-arrow-right class="h-3 w-3 text-gray-400 mx-2" />
                                                <span class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $item->requestedShift->name }}</span>
                                            </div>
                                        @elseif($activeTab === 'wfh_requests')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ $item->date?->translatedFormat('d M Y') }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Time') }}</span> <span>{{ $item->start_time ?: '--:--' }} - {{ $item->end_time ?: '--:--' }}</span></div>
                                            <div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ $item->reason }}</div>
                                            @if($item->location_address)<div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ $item->location_address }}</div>@endif
                                        
                                        @elseif($activeTab === 'document_requests')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Document') }}</span> <span class="font-bold text-gray-900 dark:text-white">{{ $item->documentTypeLabel() }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Due Date') }}</span> <span class="{{ $item->due_date && \Carbon\Carbon::parse($item->due_date)->isPast() ? 'text-danger-600 font-bold' : '' }}">{{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->translatedFormat('d M Y') : 'N/A' }}</span></div>
                                        @elseif($activeTab === 'hr_tasks')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Task') }}</span> <span class="font-bold text-gray-900 dark:text-white">{{ $item->title }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Status') }}</span> <span class="{{ $item->status === \App\Models\HrChecklistTask::STATUS_BLOCKED ? 'font-bold text-rose-600 dark:text-rose-400' : 'font-bold text-amber-600 dark:text-amber-400' }}">{{ $item->statusLabel() }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Due Date') }}</span> <span class="{{ $item->isOverdue() ? 'text-danger-600 font-bold' : '' }}">{{ $item->due_date ? $item->due_date->translatedFormat('d M Y') : 'N/A' }}</span></div>
                                            @if($item->dependency)
                                                <div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ __('Depends on: :task', ['task' => $item->dependency->title]) }}</div>
                                            @endif
                                        @elseif($activeTab === 'custom_forms')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Form') }}</span> <span class="font-bold text-gray-900 dark:text-white">{{ $item->template?->title }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Category') }}</span> <span class="font-bold text-teal-600 dark:text-teal-400">{{ __(str($item->template?->category ?? 'general')->headline()->toString()) }}</span></div>
                                            <div class="mt-2 grid grid-cols-1 gap-1 border-t border-gray-100 pt-2 text-xs dark:border-gray-700">
                                                @foreach(collect($item->payload ?? [])->take(2) as $key => $value)
                                                    <div class="flex justify-between gap-3">
                                                        <span class="truncate text-gray-400">{{ __(str($key)->replace('_', ' ')->headline()->toString()) }}</span>
                                                        <span class="max-w-[9rem] truncate text-gray-600 dark:text-gray-300">{{ is_array($value) ? json_encode($value) : ($value ?: '-') }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-px bg-gray-200 dark:bg-gray-700">
                                @if(! in_array($activeTab, ['document_requests', 'custom_forms'], true))
                                    <button wire:click="confirmReject({{ $item->id }})" class="flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-danger-600 transition-colors hover:bg-danger-50 hover:text-danger-700 focus:outline-none dark:bg-gray-800 dark:text-danger-400 dark:hover:bg-danger-900/20">
                                        <x-heroicon-m-x-mark class="h-5 w-5" /> {{ $activeTab === 'hr_tasks' ? __('Block') : __('Reject') }}
                                    </button>
                                    <button wire:click="approve({{ $item->id }})" class="flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-success-600 transition-colors hover:bg-success-50 hover:text-success-700 focus:outline-none dark:bg-gray-800 dark:text-success-400 dark:hover:bg-success-900/20">
                                        <x-heroicon-m-check class="h-5 w-5" /> {{ $activeTab === 'hr_tasks' ? __('Mark Done') : __('Approve') }}
                                    </button>
                                @elseif($activeTab === 'custom_forms')
                                    <a href="{{ $detailRoute }}" class="flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-primary-600 transition-colors hover:bg-primary-50 hover:text-primary-700 focus:outline-none dark:bg-gray-800 dark:text-primary-400 dark:hover:bg-primary-900/20">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5" /> {{ __('Open') }}
                                    </a>
                                    <button wire:click="approve({{ $item->id }})" class="flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-success-600 transition-colors hover:bg-success-50 hover:text-success-700 focus:outline-none dark:bg-gray-800 dark:text-success-400 dark:hover:bg-success-900/20">
                                        <x-heroicon-m-check class="h-5 w-5" /> {{ __('Mark Reviewed') }}
                                    </button>
                                @else
                                    <a href="{{ $detailRoute }}" class="col-span-2 flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-primary-600 transition-colors hover:bg-primary-50 hover:text-primary-700 focus:outline-none dark:bg-gray-800 dark:text-primary-400 dark:hover:bg-primary-900/20">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5" /> {{ __('Open Document Requests') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($items->hasPages())
                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    {{ $items->links() }}
                </div>
            @endif
            </div>
        </div>

        <!-- Rejection Modal -->
        <x-overlays.dialog-modal wire:model="confirmingRejection">
            <x-slot name="title">{{ __('Reject Request') }}</x-slot>
            <x-slot name="content">
                <p class="sr-only">{{ __('Are you sure you want to reject this request? Please provide a reason.') }}</p>
                <div class="mt-4">
                    <x-forms.label value="{{ __('Rejection Reason') }}" />
                    <x-forms.textarea wire:model="rejectionReason" class="mt-1 w-full" rows="3" placeholder="{{ __('Optional') }}" />
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-actions.secondary-button wire:click="cancelReject">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button wire:click="reject" variant="danger" class="ml-2">{{ __('Reject Request') }}</x-actions.button>
            </x-slot>
        </x-overlays.dialog-modal>
    </x-admin.page-shell>
</div>
