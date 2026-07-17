@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 w-full ps-3 pe-4 py-2.5 border-l-4 border-blue-600 text-start text-base font-semibold text-blue-700 bg-blue-50 focus:outline-none transition duration-150 ease-in-out'
            : 'flex items-center gap-3 w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-50 hover:border-slate-300 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
