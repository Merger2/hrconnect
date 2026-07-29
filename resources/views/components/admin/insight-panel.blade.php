<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/60 bg-white/70 shadow-sm backdrop-blur-xl transition-all duration-300 hover:shadow-md dark:border-slate-800/60 dark:bg-slate-900/50']) }}>
    {{ $slot }}
</div>
