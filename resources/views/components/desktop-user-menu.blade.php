@props(['dropUp' => true])

<div
    x-data="{ open: false }"
    @click.away="open = false"
    class="relative"
    data-test="sidebar-menu-button"
>
    <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-surface-container-high">
        <div class="flex size-8 items-center justify-center rounded-full bg-surface-container text-sm font-semibold text-ink">
            {{ auth()->user()->initials() }}
        </div>
        <span class="max-w-28 truncate">{{ auth()->user()->name }}</span>
        <span class="material-symbols-outlined text-base text-on-surface-variant">unfold_more</span>
    </button>

    <div x-show="open" x-cloak class="absolute end-0 z-50 w-56 rounded-xl border border-outline-variant bg-canvas py-1 shadow-lg {{ $dropUp ? 'bottom-full mb-1' : 'mt-1' }}">
        <div class="flex items-center gap-2 px-4 py-2 text-sm">
            <div class="flex size-8 items-center justify-center rounded-full bg-surface-container text-sm font-semibold text-ink">
                {{ auth()->user()->initials() }}
            </div>
            <div class="grid flex-1 leading-tight">
                <p class="truncate font-medium text-ink">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-on-surface-variant">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <hr class="border-outline-variant/50" />

        <a href="{{ route('profile.edit') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-ink hover:bg-surface-container-high" wire:navigate>
            <span class="material-symbols-outlined text-base">settings</span>
            {{ __('Pengaturan') }}
        </a>

        <hr class="border-outline-variant/50" />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-ink hover:bg-surface-container-high" data-test="logout-button">
                <span class="material-symbols-outlined text-base">logout</span>
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</div>
