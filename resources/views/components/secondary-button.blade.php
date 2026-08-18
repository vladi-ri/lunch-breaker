<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-slate-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-slate-950 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
