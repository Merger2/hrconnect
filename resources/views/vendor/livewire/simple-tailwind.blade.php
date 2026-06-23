@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
            <span>
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-muted bg-canvas border border-hairline cursor-default leading-5 rounded-md dark:text-muted dark:bg-surface-dark dark:border-hairline">
                        {!! __('pagination.previous') !!}
                    </span>
                @else
                    @if(method_exists($paginator,'getCursorName'))
                        <button type="button" dusk="previousPage" wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}" wire:click="setPage('{{$paginator->previousCursor()->encode()}}','{{ $paginator->getCursorName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-body bg-canvas border border-hairline leading-5 rounded-md hover:text-muted focus:outline-none focus:ring ring-accent focus:border-accent active:bg-surface-soft active:text-body transition ease-in-out duration-150 dark:bg-surface-dark dark:border-hairline dark:text-on-dark-soft dark:focus:border-accent dark:active:bg-surface-strong dark:active:text-on-dark-soft">
                                {!! __('pagination.previous') !!}
                        </button>
                    @else
                        <button
                            type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-body bg-canvas border border-hairline leading-5 rounded-md hover:text-muted focus:outline-none focus:ring ring-accent focus:border-accent active:bg-surface-soft active:text-body transition ease-in-out duration-150 dark:bg-surface-dark dark:border-hairline dark:text-on-dark-soft dark:focus:border-accent dark:active:bg-surface-strong dark:active:text-on-dark-soft">
                                {!! __('pagination.previous') !!}
                        </button>
                    @endif
                @endif
            </span>

            <span>
                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    @if(method_exists($paginator,'getCursorName'))
                        <button type="button" dusk="nextPage" wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}" wire:click="setPage('{{$paginator->nextCursor()->encode()}}','{{ $paginator->getCursorName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-body bg-canvas border border-hairline leading-5 rounded-md hover:text-muted focus:outline-none focus:ring ring-accent focus:border-accent active:bg-surface-soft active:text-body transition ease-in-out duration-150 dark:bg-surface-dark dark:border-hairline dark:text-on-dark-soft dark:focus:border-accent dark:active:bg-surface-strong dark:active:text-on-dark-soft">
                                {!! __('pagination.next') !!}
                        </button>
                    @else
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-body bg-canvas border border-hairline leading-5 rounded-md hover:text-muted focus:outline-none focus:ring ring-accent focus:border-accent active:bg-surface-soft active:text-body transition ease-in-out duration-150 dark:bg-surface-dark dark:border-hairline dark:text-on-dark-soft dark:focus:border-accent dark:active:bg-surface-strong dark:active:text-on-dark-soft">
                                {!! __('pagination.next') !!}
                        </button>
                    @endif
                @else
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-muted bg-canvas border border-hairline cursor-default leading-5 rounded-md dark:text-muted dark:bg-surface-dark dark:border-hairline">
                        {!! __('pagination.next') !!}
                    </span>
                @endif
            </span>
        </nav>
    @endif
</div>
