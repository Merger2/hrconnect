{{-- Livewire toast notification — dispatch via $this->dispatch('toast', variant: 'success', text: '...') --}}
{{-- JS listener in app.js: Livewire.on('toast', ...) --}}
<div
    x-data="{
        show: false,
        variant: 'success',
        text: '',
        icon: 'check_circle',
        timeout: null,
        init() {
            Livewire.on('toast', (event) => {
                this.variant = event.variant || 'success';
                this.text = event.text || '';
                this.icon = this.getIcon(this.variant);
                this.show = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => this.show = false, 3200);
            });
        },
        getIcon(v) {
            return {
                success: 'check_circle',
                error: 'error',
                warning: 'warning',
                info: 'info',
            }[v] || 'check_circle';
        },
        getStyles(v) {
            return {
                success: 'border-success/30 bg-success/10 text-success',
                error: 'border-error/30 bg-error/10 text-error',
                warning: 'border-warning/30 bg-warning/10 text-warning',
                info: 'border-info/30 bg-info/10 text-info',
            }[v] || 'border-success/30 bg-success/10 text-success';
        },
    }"
    x-init="init()"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-4 right-4 z-[200] flex items-center gap-3 rounded-xl border px-4 py-3 shadow-modal glass lg:max-w-md"
    :class="getStyles(variant)"
    style="position: fixed;"
>
    <span class="material-symbols-outlined text-xl shrink-0" x-text="icon" :class="'text-' + variant"></span>
    <p class="text-sm font-medium text-ink" x-text="text"></p>
    <button type="button" @click="show = false" class="ml-auto shrink-0 text-on-surface-variant hover:text-ink">
        <span class="material-symbols-outlined text-base">close</span>
    </button>
</div>