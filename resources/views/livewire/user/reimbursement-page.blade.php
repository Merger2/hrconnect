<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="reimbursement-page-title" class="user-page-surface" @unless($isCreating) wire:poll.visible.20s @endunless>
            <x-user.page-header :back-href="!$isCreating ? route('home') : null" :title="$isCreating ? __('New Claim') : __('Reimbursement')" title-id="reimbursement-page-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-credit-card class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    @if ($isCreating)
                        <button type="button" wire:click="cancel" aria-label="{{ __('Back') }}"
                            class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                            <span>{{ __('Back') }}</span>
                        </button>
                    @else
                        <button type="button" wire:click="create" aria-label="{{ __('New Request') }}"
                            class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                            <x-heroicon-o-plus class="h-5 w-5" />
                            <span>{{ __('New Request') }}</span>
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">

                @if ($isCreating)
                    {{-- CREATE FORM --}}
                    <div
                        class="user-native-form mx-auto max-w-2xl p-4 sm:p-5">
                        <form wire:submit.prevent="save" class="space-y-4">

                            {{-- Date & Type --}}
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {{-- Date --}}
                                <x-user.native-date-field
                                    id="reimbursement-date"
                                    :label="__('Transaction Date')"
                                    model="date"
                                    error="date"
                                    modifier="default"
                                />

                                {{-- Type --}}
                                <div>
                                    <label
                                        class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Claim Type') }}</label>
                                    <div wire:ignore>
                                        <x-user.tom-select-user id="type" wire:model="type"
                                            placeholder="{{ __('Select Type') }}" class="block w-full">
                                            <option value="" disabled>{{ __('Select Type') }}</option>
                                            <option value="medical">{{ __('Medical') }}</option>
                                            <option value="transport">{{ __('Transport') }}</option>
                                            <option value="project">{{ __('Project') }}</option>
                                            <option value="optical">{{ __('Optical') }}</option>
                                            <option value="dental">{{ __('Dental') }}</option>
                                            <option value="other">{{ __('Other') }}</option>
                                        </x-user.tom-select-user>
                                    </div>
                                    <x-forms.input-error for="type" class="mt-2" />
                                </div>
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label
                                    class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Amount') }}</label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <span class="text-gray-500 dark:text-gray-400 font-bold">{{ __('Rp') }}</span>
                                    </div>
                                    <x-forms.input type="text"
                                        class="block w-full rounded-xl border-gray-200 bg-gray-50 py-3 pl-12 text-lg font-bold dark:border-gray-700 dark:bg-gray-900/50"
                                        x-data x-mask:dynamic="$money($input, '.', ',')" wire:model="amount"
                                        placeholder="0" />
                                </div>
                                <x-forms.input-error for="amount" class="mt-2" />
                            </div>

                            {{-- Description --}}
                            <div>
                                <label
                                    class="mb-2 block font-bold text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                                <x-forms.textarea wire:model="description" rows="3"
                                    class="block w-full rounded-xl border-gray-200 bg-gray-50 py-3 dark:border-gray-700 dark:bg-gray-900/50"
                                    placeholder="{{ __('Explain details...') }}" />
                                <x-forms.input-error for="description" class="mt-2" />
                            </div>

                            {{-- Attachment --}}
                            <div
                                class="user-upload-dropzone">
                                <label class="mb-3 font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <x-heroicon-o-paper-clip class="h-5 w-5 text-gray-400" />
                                    {{ __('Attachment (Recall/Bill)') }}
                                </label>

                                <div class="mt-2 flex justify-center px-4 py-4">
                                    <div class="space-y-1 text-center">
                                        @if ($attachment)
                                            <div
                                                class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400 font-bold bg-green-50 dark:bg-green-900/20 py-2 px-4 rounded-full inline-block break-all max-w-full">
                                                <x-heroicon-o-check class="h-4 w-4 shrink-0" />
                                                <span
                                                    class="truncate">{{ $attachment->getClientOriginalName() }}</span>
                                            </div>
                                        @else
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                                <label for="reimbursement-attachment-upload"
                                                    class="relative cursor-pointer rounded-md font-bold text-primary-600 hover:text-primary-500 focus-within:outline-none">
                                                    <span>{{ __('Upload a file') }}</span>
                                                </label>
                                                <input id="reimbursement-attachment-upload" wire:model="attachment" type="file"
                                                    class="sr-only" accept="image/png,image/jpeg,image/jpg,application/pdf"
                                                    aria-label="{{ __('Attachment (Recall/Bill)') }}">
                                                <p class="pl-1 hidden sm:inline">{{ __('or drag and drop') }}</p>
                                            </div>
                                            <p class="sr-only">{{ __('PNG, JPG, PDF up to 10MB') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <x-forms.input-error for="attachment" class="mt-2" />
                            </div>

                            <div class="flex flex-col-reverse items-stretch justify-end gap-2 pt-3 sm:flex-row">
                                <button type="button" wire:click="cancel"
                                    class="user-secondary-action">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit"
                                    class="user-primary-action flex-1 sm:flex-none">
                                    {{ __('Submit Claim') }}
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    {{-- LIST VIEW --}}

                    <div
                        class="user-compact-filter mb-4">
                        <div class="space-y-2">
                            <label class="sr-only" for="reimbursement-search">{{ __('Search') }}</label>
                            <x-forms.input id="reimbursement-search" type="search" wire:model.live.debounce.300ms="search"
                                class="block w-full"
                                placeholder="{{ __('Search reimbursement history') }}" />

                            <div class="user-filter-chip-row" role="tablist" aria-label="{{ __('Status') }}">
                                @foreach ([
                                    'all' => __('All'),
                                    'pending' => __('Pending'),
                                    'approved' => __('Approved'),
                                    'rejected' => __('Rejected'),
                                ] as $statusKey => $statusLabel)
                                    <button type="button" wire:click="setStatusFilter('{{ $statusKey }}')"
                                        aria-pressed="{{ $statusFilter === $statusKey ? 'true' : 'false' }}"
                                        class="user-filter-chip">
                                        {{ $statusLabel }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="user-filter-chip-row" role="tablist" aria-label="{{ __('Type') }}">
                                @foreach ([
                                    'all' => __('All Types'),
                                    'medical' => __('Medical'),
                                    'transport' => __('Transport'),
                                    'project' => __('Project'),
                                    'optical' => __('Optical'),
                                    'dental' => __('Dental'),
                                    'other' => __('Other'),
                                ] as $typeKey => $typeLabel)
                                    <button type="button" wire:click="setTypeFilter('{{ $typeKey }}')"
                                        aria-pressed="{{ $typeFilter === $typeKey ? 'true' : 'false' }}"
                                        class="user-filter-chip">
                                        {{ $typeLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @if ($claims->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-document-text class="h-8 w-8 text-gray-300 dark:text-gray-600" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No Claims Found') }}</h3>
                            <p class="user-empty-state__copy">
                                {{ __('You haven\'t submitted any reimbursement claims yet.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($claims as $claim)
                                <div
                                    class="user-list-card group">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-3 overflow-hidden sm:gap-4">
                                            {{-- Icon --}}
                                            <div
                                                class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110
                                                @if ($claim->type == 'medical') bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400
                                                @elseif($claim->type == 'transport') bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400
                                                @else bg-gray-50 text-gray-600 dark:bg-gray-700/50 dark:text-gray-400 @endif">

                                                @if ($claim->type == 'medical')
                                                    <x-heroicon-o-heart class="h-5 w-5 sm:h-6 sm:w-6" />
                                                @elseif($claim->type == 'transport')
                                                    <x-heroicon-o-paper-airplane class="h-5 w-5 sm:h-6 sm:w-6" />
                                                @else
                                                    <x-heroicon-o-document-text class="h-5 w-5 sm:h-6 sm:w-6" />
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-0.5">
                                                    <h4
                                                        class="font-bold text-gray-900 dark:text-white capitalize truncate text-sm sm:text-base">
                                                        {{ ucfirst($claim->type) }}</h4>
                                                    <span
                                                        class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wide
                                                        @if ($claim->status === 'approved') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                                        @elseif($claim->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                                        @else bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                                        {{ ucfirst($claim->status) }}
                                                    </span>
                                                </div>
                                                <p
                                                    class="sr-only">
                                                    {{ $claim->description }}</p>
                                                <div
                                                    class="text-[10px] text-gray-400 mt-0.5 sm:mt-1 flex items-center gap-1">
                                                    <x-heroicon-o-calendar-days class="h-3 w-3" />
                                                    {{ $claim->date->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="shrink-0 pl-0 text-left sm:pl-4 sm:text-right">
                                            <p
                                                class="text-sm sm:text-lg font-black text-gray-900 dark:text-white tracking-tight">
                                                <span
                                                    class="text-[10px] sm:text-xs text-gray-400 font-normal mr-0.5">{{ __('Rp') }}</span>{{ number_format($claim->amount, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Load More / Archive Button --}}
                        @if ($totalClaims > $limit)
                            <div class="mt-6 text-center">
                                <button wire:click="loadMore"
                                    class="px-6 py-2 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-600 transition shadow-sm">
                                    {{ __('View Older History') }} ({{ $totalClaims - $limit }} {{ __('more') }})
                                </button>
                            </div>
                        @endif
                    @endif
                @endif

            </div>
        </section>
    </div>
</div>
