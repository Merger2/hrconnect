<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="attendance-correction-title" class="user-page-surface" @unless($showCreateModal) wire:poll.visible.20s @endunless>
            <x-user.page-header :back-href="route('home')" :title="__('Attendance Corrections')" title-id="attendance-correction-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <button type="button" wire:click="create" aria-label="{{ __('New Request') }}"
                        class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Request') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div
                    class="user-compact-filter mb-4">
                    <div class="user-filter-grid">
                        <div>
                            <label
                                class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search') }}</label>
                            <x-forms.input id="correction-search" type="search" wire:model.live.debounce.300ms="search"
                                class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-100"
                                placeholder="{{ __('Reason or type') }}" />
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</label>
                            <x-forms.select id="correction-status" wire:model.live="statusFilter"
                                class="block w-full rounded-xl border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-100">
                                <option value="all">{{ __('All statuses') }}</option>
                                <option value="pending">{{ __('Pending Supervisor Review') }}</option>
                                <option value="pending_admin">{{ __('Waiting Admin Review') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="rejected">{{ __('Rejected') }}</option>
                            </x-forms.select>
                        </div>
                    </div>
                </div>

                <div class="hidden overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 md:block">
                    <div class="user-desktop-table-scroll">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr
                                    class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Requested Change') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950/30">
                                @forelse ($corrections as $correction)
                                    <tr class="align-top">
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="font-semibold">
                                                {{ $correction->attendance_date->translatedFormat('d M Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $correction->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $correction->requestTypeLabel() }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="space-y-1">
                                                @if ($correction->requested_time_in)
                                                    <div>{{ __('Check in') }}:
                                                        {{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}
                                                    </div>
                                                @endif
                                                @if ($correction->requested_time_out)
                                                    <div>{{ __('Check out') }}:
                                                        {{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}
                                                    </div>
                                                @endif
                                                @if ($correction->requestedShift)
                                                    <div>{{ __('Shift') }}: {{ $correction->requestedShift->name }}
                                                    </div>
                                                @endif
                                                @if (!$correction->requested_time_in && !$correction->requested_time_out && !$correction->requestedShift)
                                                    <div class="text-gray-500 dark:text-gray-400">
                                                        {{ __('No detailed change recorded.') }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                {{ $correction->status === 'approved'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                    : ($correction->status === 'rejected'
                                                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                                {{ $correction->statusLabel() }}
                                            </span>
                                            @if ($correction->rejection_note)
                                                <div class="mt-2 text-xs text-rose-600 dark:text-rose-300">
                                                    {{ $correction->rejection_note }}</div>
                                            @endif
                                            @if ($correction->headApprover && $correction->status === 'pending_admin')
                                                <div class="mt-2 text-xs text-sky-600 dark:text-sky-300">
                                                    {{ __('Forwarded by :name', ['name' => $correction->headApprover->name]) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="line-clamp-2 max-w-md whitespace-pre-line">{{ $correction->reason }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No attendance correction requests found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-3 md:hidden">
                    @forelse ($corrections as $correction)
                        <article
                            class="user-list-card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $correction->attendance_date->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $correction->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $correction->status === 'approved'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : ($correction->status === 'rejected'
                                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                    {{ $correction->statusLabel() }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Type') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $correction->requestTypeLabel() }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Requested Change') }}
                                    </p>
                                    <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                        @if ($correction->requested_time_in)
                                            <div>{{ __('Check in') }}:
                                                {{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}
                                            </div>
                                        @endif
                                        @if ($correction->requested_time_out)
                                            <div>{{ __('Check out') }}:
                                                {{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}
                                            </div>
                                        @endif
                                        @if ($correction->requestedShift)
                                            <div>{{ __('Shift') }}: {{ $correction->requestedShift->name }}</div>
                                        @endif
                                        @if (!$correction->requested_time_in && !$correction->requested_time_out && !$correction->requestedShift)
                                            <div class="text-gray-500 dark:text-gray-400">
                                                {{ __('No detailed change recorded.') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Reason') }}</p>
                                <div class="mt-1 line-clamp-2 whitespace-pre-line">{{ $correction->reason }}</div>
                            </div>

                            @if ($correction->rejection_note || ($correction->headApprover && $correction->status === 'pending_admin'))
                                <div class="mt-3 space-y-2 text-xs">
                                    @if ($correction->rejection_note)
                                        <div
                                            class="rounded-xl bg-rose-50 p-3 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                                            {{ $correction->rejection_note }}
                                        </div>
                                    @endif
                                    @if ($correction->headApprover && $correction->status === 'pending_admin')
                                        <div
                                            class="rounded-xl bg-sky-50 p-3 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                                            {{ __('Forwarded by :name', ['name' => $correction->headApprover->name]) }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @empty
                        <div
                            class="user-empty-state">
                            {{ __('No attendance correction requests found.') }}
                        </div>
                    @endforelse
                </div>

                @if ($corrections->hasPages())
                    <div class="mt-4">
                        {{ $corrections->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>

    <x-overlays.dialog-modal wire:model.live="showCreateModal">
        <x-slot name="title">{{ __('New Attendance Correction') }}</x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div class="user-soft-panel relative z-[40]">
                    <p class="sr-only">
                        {{ __('Choose the date first, then fill the corrected times below for that same day.') }}
                    </p>
                    <x-user.native-date-field
                        id="attendance-date"
                        :label="__('Attendance Date')"
                        model="attendanceDate"
                        error="attendanceDate"
                        :max="now()->toDateString()"
                    />
                </div>

                @if ($existingAttendance)
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                        <p class="font-semibold">{{ __('Current Attendance Snapshot') }}</p>
                        <div class="mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                            <div>{{ __('Status') }}: {{ ucfirst($existingAttendance->status) }}</div>
                            <div>{{ __('Shift') }}: {{ $existingAttendance->shift?->name ?? __('Not assigned') }}
                            </div>
                            <div>{{ __('Check in') }}:
                                {{ $snapshotTimeIn?->translatedFormat('d M Y H:i') ?? __('None') }}</div>
                            <div>{{ __('Check out') }}:
                                {{ $snapshotTimeOut?->translatedFormat('d M Y H:i') ?? __('None') }}</div>
                        </div>
                    </div>
                @else
                    <div
                        class="sr-only">
                        {{ __('No attendance snapshot was found for this date yet. You can still request a missing check in or a full correction if needed.') }}
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('What needs to be corrected?') }}</p>
                        <p class="sr-only">
                            {{ __('Choose one or more items below. You can request check in and check out corrections together.') }}
                        </p>
                        <x-forms.input-error for="includeRequestedTimeIn" class="mt-2" />
                    </div>

                    <div class="space-y-4">
                        <div
                            class="user-soft-panel relative z-[30]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Requested Check In Time') }}</h3>
                                    <p class="sr-only">
                                        {{ __('Fill this if your check in was missing or recorded incorrectly.') }}
                                    </p>
                                </div>
                                <label
                                    class="inline-flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <x-forms.checkbox wire:model.live="includeRequestedTimeIn" />
                                    <span>{{ __('Enable') }}</span>
                                </label>
                            </div>

                            @if ($includeRequestedTimeIn)
                                <div
                                    class="mt-4 rounded-[1rem] border border-emerald-100 bg-emerald-50/50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                                    <div class="mb-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="text-xs font-medium text-emerald-800 dark:text-emerald-200">
                                            {{ __('Base date: :date', ['date' => \Illuminate\Support\Carbon::parse($attendanceDate)->translatedFormat('d M Y')]) }}
                                        </div>
                                        <div class="whitespace-nowrap rounded-full bg-emerald-100/80 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                                            {{ __('Date & time') }}
                                        </div>
                                    </div>
                                    <div>
                                        <x-user.native-date-field
                                            id="requested-time-in"
                                            :label="__('Corrected Check In')"
                                            model="requestedTimeIn"
                                            error="requestedTimeIn"
                                            type="datetime-local"
                                        />
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div
                            class="user-soft-panel relative z-[20]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Requested Check Out Time') }}</h3>
                                    <p class="sr-only">
                                        {{ __('Fill this if your check out was missing or recorded incorrectly.') }}
                                    </p>
                                </div>
                                <label
                                    class="inline-flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <x-forms.checkbox wire:model.live="includeRequestedTimeOut" />
                                    <span>{{ __('Enable') }}</span>
                                </label>
                            </div>

                            @if ($includeRequestedTimeOut)
                                <div
                                    class="mt-4 rounded-[1rem] border border-amber-100 bg-amber-50/50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                                    <div class="mb-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="text-xs font-medium text-amber-800 dark:text-amber-200">
                                            {{ __('Base date: :date', ['date' => \Illuminate\Support\Carbon::parse($attendanceDate)->translatedFormat('d M Y')]) }}
                                        </div>
                                        <div class="whitespace-nowrap rounded-full bg-amber-100/80 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">
                                            {{ __('Date & time') }}
                                        </div>
                                    </div>
                                    <div>
                                        <x-user.native-date-field
                                            id="requested-time-out"
                                            :label="__('Corrected Check Out')"
                                            model="requestedTimeOut"
                                            error="requestedTimeOut"
                                            type="datetime-local"
                                        />
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div
                            class="user-soft-panel relative z-[10]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Correct Shift') }}</h3>
                                    <p class="sr-only">
                                        {{ __('Enable this if the assigned shift for that day was wrong.') }}
                                    </p>
                                </div>
                                <label
                                    class="inline-flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <x-forms.checkbox wire:model.live="includeRequestedShift" />
                                    <span>{{ __('Enable') }}</span>
                                </label>
                            </div>

                            @if ($includeRequestedShift)
                                <div class="mt-4">
                                    <x-forms.select id="requested-shift" wire:model.live="requestedShiftId"
                                        class="block w-full">
                                        <option value="">{{ __('Select shift') }}</option>
                                        @foreach ($shifts as $shift)
                                            <option value="{{ $shift->id }}">{{ $shift->name }}
                                                ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</option>
                                        @endforeach
                                    </x-forms.select>
                                </div>
                                <x-forms.input-error for="requestedShiftId" class="mt-2" />
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <x-forms.label for="correction-reason" value="{{ __('Reason') }}" class="mb-1.5 block" />
                    <x-forms.textarea id="correction-reason" wire:model.live="reason" rows="4"
                        class="mt-1 block w-full"
                        placeholder="{{ __('Explain what happened and what should be corrected.') }}" />
                    <x-forms.input-error for="reason" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-actions.secondary-button type="button" wire:click="closeModal">
                    {{ __('Cancel') }}
                </x-actions.secondary-button>
                <x-actions.button type="button" wire:click="save" class="w-full sm:w-auto">
                    {{ __('Submit Request') }}
                </x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
