<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg bg-sky-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-slate-950 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
