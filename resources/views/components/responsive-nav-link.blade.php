@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-sky-400 text-start text-base font-medium text-sky-300 bg-slate-800 focus:outline-none focus:text-sky-200 focus:bg-slate-700 focus:border-sky-300 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-400 hover:text-slate-100 hover:bg-slate-800 hover:border-slate-600 focus:outline-none focus:text-slate-100 focus:bg-slate-800 focus:border-slate-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
