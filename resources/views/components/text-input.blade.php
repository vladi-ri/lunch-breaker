@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-slate-700 bg-slate-900 text-slate-100 placeholder-slate-500 focus:border-sky-500 focus:ring-sky-500 disabled:opacity-50']) }}>
