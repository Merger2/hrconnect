<x-layouts::app.sidebar :title="__('Knowledge Base')">
    <div class="px-4 py-6 lg:px-6">
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-ink">{{ __('Knowledge Base') }}</h1>
            <p class="mt-1 text-sm text-muted-soft">Tanya AI tentang kebijakan HR, cuti, BPJS, payroll, dan aturan perusahaan.</p>
        </div>

        <livewire:knowledge-base-chat />
    </div>
</x-layouts::app.sidebar>
