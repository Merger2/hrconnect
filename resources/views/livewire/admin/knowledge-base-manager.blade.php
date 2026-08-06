<x-admin.page-shell :title="__('Knowledge Base')" :description="__('Upload dan kelola dokumen knowledge base untuk RAG chatbot AI.')">
    <x-slot name="actions">
        <x-actions.button wire:click="showUpload" size="icon" label="{{ __('Upload PDF') }}">
            <x-heroicon-m-plus class="h-5 w-5" />
        </x-actions.button>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-4">
                <x-forms.label for="kb-search" value="{{ __('Cari dokumen') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input id="kb-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Cari judul dokumen...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-2">
                <x-forms.label for="kb-category-filter" value="{{ __('Kategori') }}" class="mb-1.5 block" />
                <x-forms.select id="kb-category-filter" wire:model.live="categoryFilter" class="w-full">
                    <option value="all">{{ __('Semua kategori') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->value }}">{{ __(ucfirst($cat->value)) }}</option>
                    @endforeach
                </x-forms.select>
            </div>

            <div class="xl:col-span-2">
                <x-forms.label for="kb-status-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.select id="kb-status-filter" wire:model.live="statusFilter" class="w-full">
                    <option value="all">{{ __('Semua status') }}</option>
                    @foreach($statusOptions as $st)
                        <option value="{{ $st->value }}">{{ __(ucfirst($st->value)) }}</option>
                    @endforeach
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <!-- Document List -->
    <x-admin.panel>
        @if ($documents->isEmpty())
            <div class="px-6 py-8">
                <div class="flex flex-col items-center justify-center text-center">
                    <x-heroicon-o-document-text class="h-12 w-12 text-gray-300" />
                    <h3 class="mt-4 text-lg font-bold text-gray-900">{{ __('Belum ada dokumen') }}</h3>
                    <p class="sr-only">{{ __('Upload PDF pertama untuk memulai knowledge base.') }}</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Judul') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Kategori') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Chunk') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Diupload') }}</th>
                            <th scope="col" class="relative px-4 py-3"><span class="sr-only">{{ __('Aksi') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($documents as $doc)
                            @php
                                $statusStyles = match($doc->status?->value) {
                                    'completed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-600/20'],
                                    'processing' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-600/20'],
                                    'failed' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'ring' => 'ring-rose-600/20'],
                                    default => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'ring' => 'ring-slate-600/20'],
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-heroicon-o-document-text class="h-5 w-5 shrink-0 text-gray-400" />
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $doc->title }}</div>
                                            @if($doc->source_document)
                                                <div class="text-xs text-gray-500">{{ $doc->source_document }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">
                                        {{ $doc->category?->value ?? '-' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusStyles['bg'] }} {{ $statusStyles['text'] }} {{ $statusStyles['ring'] }}">
                                        @if($doc->status?->value === 'processing')
                                            <x-heroicon-o-arrow-path class="mr-1 h-3 w-3 animate-spin" />
                                        @endif
                                        {{ __(ucfirst($doc->status?->value ?? 'unknown')) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                    {{ $doc->chunk_count ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                    {{ $doc->created_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-actions.button type="button" wire:click="showDetail({{ $doc->id }})" variant="soft-secondary" size="sm">
                                            <x-heroicon-m-eye class="h-4 w-4" />
                                        </x-actions.button>
                                        @if($doc->status?->value === 'failed' || $doc->status?->value === 'processing')
                                            <x-actions.button type="button" wire:click="reindex({{ $doc->id }})" variant="soft-warning" size="sm">
                                                <x-heroicon-m-arrow-path class="h-4 w-4" />
                                            </x-actions.button>
                                        @endif
                                        <x-actions.button type="button" wire:click="delete({{ $doc->id }})" wire:confirm="{{ __('Hapus dokumen ini?') }}" variant="soft-danger" size="sm">
                                            <x-heroicon-m-trash class="h-4 w-4" />
                                        </x-actions.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200/60 bg-gray-50/70 px-4 py-2.5">
                {{ $documents->links() }}
            </div>
        @endif
    </x-admin.panel>

    <!-- Upload Modal -->
    <x-overlays.dialog-modal wire:model="showUploadModal">
        <x-slot name="title">{{ __('Upload Dokumen PDF') }}</x-slot>
        <x-slot name="content">
            <form wire:submit="upload" class="space-y-4">
                <div>
                    <x-forms.label for="uploadTitle" value="{{ __('Judul Dokumen') }}" />
                    <x-forms.input id="uploadTitle" type="text" class="mt-1 block w-full" wire:model="uploadTitle" required />
                    <x-forms.input-error for="uploadTitle" class="mt-2" />
                </div>
                <div>
                    <x-forms.label for="uploadCategory" value="{{ __('Kategori') }}" />
                    <x-forms.select id="uploadCategory" wire:model="uploadCategory" class="mt-1 block w-full">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}">{{ __(ucfirst($cat->value)) }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.input-error for="uploadCategory" class="mt-2" />
                </div>
                <div>
                    <x-forms.label for="uploadFile" value="{{ __('File PDF (max 10MB)') }}" />
                    <input id="uploadFile" type="file" accept="application/pdf" wire:model="uploadFile"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100" />
                    <x-forms.input-error for="uploadFile" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-400">Format: PDF, maksimal 10 MB</p>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showUploadModal', false)" wire:loading.attr="disabled">
                {{ __('Batal') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="upload" wire:loading.attr="disabled">
                {{ __('Upload') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Detail Modal -->
    <x-overlays.dialog-modal wire:model="showDetailModal" maxWidth="2xl">
        <x-slot name="title">{{ $detailDoc?->title ?? __('Detail Dokumen') }}</x-slot>
        <x-slot name="content">
            @if($detailDoc)
                <div class="mb-4 grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('Kategori') }}</div>
                        <div class="mt-0.5 font-medium text-gray-900">{{ $detailDoc->category?->value ?? '-' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('Status') }}</div>
                        <div class="mt-0.5 font-medium text-gray-900">{{ __(ucfirst($detailDoc->status?->value ?? 'unknown')) }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('Total Chunk') }}</div>
                        <div class="mt-0.5 font-medium text-gray-900">{{ $detailDoc->chunk_count ?? '-' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('Diindex') }}</div>
                        <div class="mt-0.5 font-medium text-gray-900">{{ $detailDoc->is_indexed ? __('Ya') : __('Tidak') }}</div>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <div class="text-xs text-gray-500">{{ __('Nama File') }}</div>
                    <div class="mt-0.5 font-medium text-gray-900">{{ $detailDoc->source_document ?? '-' }}</div>
                </div>

                @if($detailDoc->relationLoaded('chunks') && $detailDoc->chunks->isNotEmpty())
                    <div class="mt-6">
                        <h4 class="mb-3 text-sm font-semibold text-gray-900">{{ __('Daftar Chunk') }} ({{ $detailDoc->chunks->count() }})</h4>
                        <div class="max-h-80 space-y-2 overflow-y-auto">
                            @foreach($detailDoc->chunks as $chunk)
                                <div class="rounded-lg border border-gray-100 bg-white p-3">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                            #{{ $chunk->page_number ?? $loop->iteration }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ $chunk->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="line-clamp-3 text-sm text-gray-600">{{ $chunk->content }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showDetailModal', false)">
                {{ __('Tutup') }}
            </x-actions.secondary-button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
