<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="document-request-title" class="user-page-surface" @unless($showModal) wire:poll.visible.20s @endunless>
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Document Requests')"
                title-id="document-request-title"
                class="border-b-0">
                <x-slot name="actions">
                    <button type="button" wire:click="create" class="wcag-touch-target inline-flex items-center justify-center rounded-full bg-primary-600 p-3 text-white shadow-none transition hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white" aria-label="{{ __('New Request') }}">
                        <x-heroicon-o-plus class="h-5 w-5" />
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div class="document-request-hero">
                    <div class="document-request-hero__icon">
                        <x-heroicon-o-document-text class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="document-request-hero__label">{{ __('Documents') }}</p>
                        <p class="document-request-hero__title">{{ __('Use Document Requests when uploads or generated letters are needed.') }}</p>
                    </div>
                    <div class="document-request-hero__count">
                        <span>{{ $requestStats['total'] }}</span>
                        <small>{{ __('Total') }}</small>
                    </div>
                </div>

                <div class="document-request-stats" aria-label="{{ __('Document Requests') }}">
                    <div class="document-request-stat">
                        <span class="document-request-stat__dot bg-amber-400"></span>
                        <span class="document-request-stat__value">{{ $requestStats['in_progress'] }}</span>
                        <span class="document-request-stat__label">{{ __('Process') }}</span>
                    </div>
                    <div class="document-request-stat">
                        <span class="document-request-stat__dot bg-emerald-400"></span>
                        <span class="document-request-stat__value">{{ $requestStats['ready'] }}</span>
                        <span class="document-request-stat__label">{{ __('Ready') }}</span>
                    </div>
                    <div class="document-request-stat">
                        <span class="document-request-stat__dot bg-sky-400"></span>
                        <span class="document-request-stat__value">{{ $requestStats['needs_upload'] }}</span>
                        <span class="document-request-stat__label">{{ __('Upload') }}</span>
                    </div>
                </div>

                <div class="document-request-list">
                    @forelse ($requests as $request)
                        @php
                            $statusTone = match ($request->status) {
                                \App\Models\EmployeeDocumentRequest::STATUS_READY => 'document-request-status--ready',
                                \App\Models\EmployeeDocumentRequest::STATUS_REJECTED, \App\Models\EmployeeDocumentRequest::STATUS_EXPIRED => 'document-request-status--rejected',
                                \App\Models\EmployeeDocumentRequest::STATUS_UPLOADED => 'document-request-status--upload',
                                default => 'document-request-status--pending',
                            };
                            $note = $request->fulfillment_note ?: $request->rejection_note;
                        @endphp

                        <article class="document-request-card">
                            <div class="document-request-card__top">
                                <div class="document-request-card__icon">
                                    <x-heroicon-o-document-text class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="document-request-card__title">{{ $request->documentTypeLabel() }}</h3>
                                            <p class="document-request-card__meta">
                                                {{ $request->created_at->diffForHumans() }}
                                                @if ($request->due_date)
                                                    <span aria-hidden="true">·</span> {{ __('Due') }} {{ $request->due_date->translatedFormat('d M Y') }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="document-request-status {{ $statusTone }}">
                                            {{ $request->statusLabel() }}
                                        </span>
                                    </div>

                                    <p class="document-request-card__purpose">{{ $request->purpose }}</p>

                                    @if ($request->requester && $request->requested_by !== $request->employee?->user_id)
                                        <p class="document-request-card__meta">{{ __('Requested by') }} {{ $request->requester->name }}</p>
                                    @endif

                                    @if ($request->reviewer || $note)
                                        <div class="document-request-card__note">
                                            @if ($request->reviewer)
                                                <span>{{ __('by') }} {{ $request->reviewer->name }}</span>
                                            @endif
                                            @if ($note)
                                                <span>{{ $note }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($request->details)
                                <p class="sr-only">{{ $request->details }}</p>
                            @endif

                            <div class="document-request-card__actions">
                                @can('upload', $request)
                                    <button type="button" wire:click="prepareUpload({{ $request->id }})" class="document-request-action document-request-action--primary" data-e2e="document-upload-open" data-request-id="{{ $request->id }}">
                                        <x-heroicon-m-arrow-up-tray class="h-4 w-4" />
                                        <span>{{ __('Upload') }}</span>
                                    </button>
                                @endcan
                                @if ($request->generated_path)
                                    <a href="{{ route('document-requests.download', $request) }}" class="document-request-action">
                                        <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                                        <span>{{ __('Generated') }}</span>
                                    </a>
                                @endif
                                @if ($request->uploaded_path)
                                    <a href="{{ route('document-requests.uploaded', $request) }}" class="document-request-action">
                                        <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                                        <span>{{ __('Uploaded') }}</span>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-document-plus class="h-10 w-10" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No document requests yet.') }}</h3>
                            <p class="user-empty-state__copy">{{ __('Create a request when you need an employment letter, salary statement, upload request, or another HR/Finance document.') }}</p>
                            <button type="button" wire:click="create" class="document-request-action document-request-action--primary mx-auto mt-2" aria-label="{{ __('New Request') }}">
                                <x-heroicon-o-plus class="h-4 w-4" />
                                <span>{{ __('New Request') }}</span>
                            </button>
                        </div>
                    @endforelse
                </div>

                @if ($requests->hasPages())
                    <div class="mt-4">{{ $requests->links() }}</div>
                @endif
            </div>
        </section>
    </div>

    @if ($showModal)
        <template x-teleport="body">
            <div class="jetstream-modal fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]" role="dialog" aria-modal="true" aria-labelledby="document-request-modal-title">
                <div class="fixed inset-0 z-0 bg-slate-950/70 backdrop-blur-sm" wire:click="close"></div>
                <form wire:submit="store" class="user-ui document-request-modal relative z-10 w-full max-w-xl sm:mx-auto" wire:click.stop x-trap.inert.noscroll="true">
                    <div class="document-request-modal__header">
                        <div class="min-w-0">
                            <p class="document-request-modal__eyebrow">{{ __('Documents') }}</p>
                            <h2 id="document-request-modal-title" class="document-request-modal__title">{{ __('New Document Request') }}</h2>
                        </div>
                        <button type="button" wire:click="close" class="document-request-modal__close" aria-label="{{ __('Close') }}" wire:loading.attr="disabled">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="document-request-modal__body">
                        <div>
                            <x-forms.label for="document-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                            <x-forms.select id="document-type" wire:model.live="documentType" class="block w-full" aria-label="{{ __('Document Type') }}">
                                @foreach ($documentTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="documentType" class="mt-1" />
                        </div>

                        <x-user.native-textarea-field
                            id="document-purpose"
                            :label="__('Purpose')"
                            model="purpose"
                            error="purpose"
                            placeholder="{{ __('Example: bank account opening, visa application, or housing lease.') }}"
                        />

                        <x-user.native-textarea-field
                            id="document-details"
                            :label="__('Additional Details') . ' (' . __('Optional') . ')'"
                            model="details"
                            error="details"
                            placeholder="{{ __('Add recipient name, deadline, required language, or other notes.') }}"
                        />
                    </div>

                    <div class="document-request-modal__footer">
                        <button type="button" wire:click="close" class="document-request-secondary-button" wire:loading.attr="disabled">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="document-request-primary-button" wire:loading.attr="disabled">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </template>
    @endif

    @if ($uploadingRequestId)
        <template x-teleport="body">
            <div class="jetstream-modal fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]" role="dialog" aria-modal="true" aria-labelledby="document-upload-modal-title">
                <div class="fixed inset-0 z-0 bg-slate-950/70 backdrop-blur-sm" wire:click="cancelUpload"></div>
                <form wire:submit="upload" class="user-ui document-request-modal relative z-10 w-full max-w-lg sm:mx-auto" wire:click.stop data-e2e="document-upload-form" x-trap.inert.noscroll="true">
                    <div class="document-request-modal__header">
                        <div class="min-w-0">
                            <p class="document-request-modal__eyebrow">{{ __('Upload') }}</p>
                            <h2 id="document-upload-modal-title" class="document-request-modal__title">{{ __('Upload Document') }}</h2>
                        </div>
                        <button type="button" wire:click="cancelUpload" class="document-request-modal__close" aria-label="{{ __('Close') }}" wire:loading.attr="disabled" wire:target="attachment,upload">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="document-request-modal__body">
                        <label for="document-upload-file" class="document-upload-dropzone">
                            <span class="document-upload-dropzone__icon">
                                <x-heroicon-o-paper-clip class="h-6 w-6" />
                            </span>
                            <span class="document-upload-dropzone__title">{{ __('File') }}</span>
                            <span class="document-upload-dropzone__copy">{{ __('Accepted: PDF, image, Word, or Excel. Maximum 10 MB.') }}</span>
                            <input id="document-upload-file" wire:model="attachment" type="file" data-e2e="document-upload-file" class="sr-only" />
                        </label>
                        <x-forms.input-error for="attachment" class="mt-1" />
                        <p class="text-sm font-semibold text-sky-700" wire:loading wire:target="attachment">{{ __('Uploading file...') }}</p>
                        <p class="text-sm font-semibold text-sky-700" wire:loading wire:target="upload">{{ __('Processing upload...') }}</p>
                    </div>

                    <div class="document-request-modal__footer">
                        <button type="button" wire:click="cancelUpload" class="document-request-secondary-button" wire:loading.attr="disabled" wire:target="attachment,upload">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="document-request-primary-button" wire:loading.attr="disabled" wire:target="attachment,upload" data-e2e="document-upload-submit">
                            <span wire:loading.remove wire:target="upload">{{ __('Upload') }}</span>
                            <span wire:loading wire:target="upload">{{ __('Processing...') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </template>
    @endif
</div>
