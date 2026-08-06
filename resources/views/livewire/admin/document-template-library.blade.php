<x-admin.page-shell :title="__('Template Library')" :description="__('Review saved templates, check PDF preview, and manage active template versions.')">
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-actions.button href="{{ route('admin.document-templates') }}" variant="secondary">
                <x-heroicon-m-arrow-left class="h-4 w-4" />
                {{ __('Back to Builder') }}
            </x-actions.button>
            @if ($selectedTemplate)
                <x-actions.button type="button" wire:click="downloadPreviewPdf" variant="primary">
                    <x-heroicon-m-document-arrow-down class="h-4 w-4" />
                    {{ __('Download Preview PDF') }}
                </x-actions.button>
            @endif
        </div>

        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <x-admin.panel class="p-4">
                <h2 class="text-base font-semibold text-gray-900">{{ __('Saved Templates') }}</h2>
                <p class="sr-only">{{ __('Select a template to preview or manage it.') }}</p>

                {{-- Desktop: daftar template dalam table hanya di lg ke atas --}}
                <div class="mt-5 hidden overflow-hidden rounded-xl border border-gray-100 lg:block">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($templates as $template)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <button type="button" wire:click="selectTemplate({{ $template->id }})" class="block w-full text-left">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-gray-900">{{ $template->name }}</span>
                                                @if ($template->is_active)
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">{{ __('Active') }}</span>
                                                @else
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">{{ __('Draft') }}</span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $template->documentType?->name }} · {{ strtoupper($template->paper_size) }} · {{ $template->orientation }} ·
                                                {{ trans_choice(':count generated document|:count generated documents', $template->generated_requests_count, ['count' => $template->generated_requests_count]) }}
                                            </div>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <x-actions.icon-button href="{{ route('admin.document-templates', ['template' => $template->id]) }}" variant="primary" label="{{ __('Edit document template') }}: {{ $template->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button wire:click="duplicateTemplate({{ $template->id }})" variant="neutral" label="{{ __('Duplicate template') }}: {{ $template->name }}">
                                                <x-heroicon-m-document-duplicate class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            @if ($template->is_active)
                                                <x-actions.icon-button wire:click="deactivateTemplate({{ $template->id }})" variant="warning" label="{{ __('Deactivate template') }}: {{ $template->name }}">
                                                    <x-heroicon-m-pause class="h-5 w-5" />
                                                </x-actions.icon-button>
                                            @else
                                                <x-actions.icon-button wire:click="activateTemplate({{ $template->id }})" variant="success" label="{{ __('Make active template') }}: {{ $template->name }}">
                                                    <x-heroicon-m-check-circle class="h-5 w-5" />
                                                </x-actions.icon-button>
                                            @endif
                                            <x-actions.icon-button wire:click="confirmDeleteTemplate({{ $template->id }})" variant="danger" label="{{ __('Delete template') }}: {{ $template->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-5 text-center text-sm text-gray-500">{{ __('No templates yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile: kartu stacked --}}
                <div class="mt-5 divide-y divide-gray-100 rounded-xl border border-gray-100 lg:hidden">
                    @forelse ($templates as $template)
                        <div class="px-4 py-3">
                            <button type="button" wire:click="selectTemplate({{ $template->id }})" class="block w-full text-left">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ $template->name }}</span>
                                    @if ($template->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">{{ __('Active') }}</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">{{ __('Draft') }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ $template->documentType?->name }} · {{ strtoupper($template->paper_size) }} · {{ $template->orientation }} ·
                                    {{ trans_choice(':count generated document|:count generated documents', $template->generated_requests_count, ['count' => $template->generated_requests_count]) }}
                                </div>
                            </button>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-actions.button href="{{ route('admin.document-templates', ['template' => $template->id]) }}" variant="primary" size="sm">
                                    <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    {{ __('Edit') }}
                                </x-actions.button>
                                <x-actions.button wire:click="duplicateTemplate({{ $template->id }})" variant="neutral" size="sm">
                                    <x-heroicon-m-document-duplicate class="h-4 w-4" />
                                    {{ __('Duplicate') }}
                                </x-actions.button>
                                @if ($template->is_active)
                                    <x-actions.button wire:click="deactivateTemplate({{ $template->id }})" variant="warning" size="sm">
                                        <x-heroicon-m-pause class="h-4 w-4" />
                                        {{ __('Deactivate') }}
                                    </x-actions.button>
                                @else
                                    <x-actions.button wire:click="activateTemplate({{ $template->id }})" variant="success" size="sm">
                                        <x-heroicon-m-check-circle class="h-4 w-4" />
                                        {{ __('Activate') }}
                                    </x-actions.button>
                                @endif
                                <x-actions.button wire:click="confirmDeleteTemplate({{ $template->id }})" variant="danger" size="sm">
                                    <x-heroicon-m-trash class="h-4 w-4" />
                                    {{ __('Delete') }}
                                </x-actions.button>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-5 text-center text-sm text-gray-500">{{ __('No templates yet.') }}</div>
                    @endforelse
                </div>
            </x-admin.panel>

            <x-admin.panel class="p-4">
                @if ($selectedTemplate)
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">{{ $selectedTemplate->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $selectedTemplate->documentType?->name }} · {{ strtoupper($selectedTemplate->paper_size) }} · {{ $selectedTemplate->orientation }}
                            </p>
                        </div>
                        <x-actions.button href="{{ route('admin.document-templates', ['template' => $selectedTemplate->id]) }}" variant="secondary" size="sm">
                            <x-heroicon-m-pencil-square class="h-4 w-4" />
                            {{ __('Edit') }}
                        </x-actions.button>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-slate-900 shadow-sm">
                        {!! clean($templatePreviewHtml) !!}
                    </div>
                @else
                    <div class="py-8 text-center text-sm text-gray-500">
                        {{ __('No template selected.') }}
                    </div>
                @endif
            </x-admin.panel>
        </div>
    </div>

    <x-overlays.confirmation-modal wire:model.live="confirmingTemplateDeletion">
        <x-slot name="title">{{ __('Delete Template') }}</x-slot>
        <x-slot name="content">
            <p>{{ __('Delete Template') }}?</p>
            <p class="sr-only">{{ __('Unused templates will be deleted. Templates already used by generated documents will be deactivated to preserve document history.') }}</p>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button type="button" wire:click="cancelDeleteTemplate" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" type="button" wire:click="deleteTemplate" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>
</x-admin.page-shell>
