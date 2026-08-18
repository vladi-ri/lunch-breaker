@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-sky-400 text-sm font-medium leading-5 text-white focus:outline-none focus:border-sky-300 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-slate-400 hover:text-slate-100 hover:border-slate-600 focus:outline-none focus:text-slate-100 focus:border-slate-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
