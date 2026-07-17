@props(['align' => 'center'])

{{-- Selector de idioma ES | EN. Enlaces GET que vuelven a la página actual. --}}
@php
    $actual = app()->getLocale();
    $idiomas = ['es' => 'ES', 'en' => 'EN'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-slate-100 p-0.5 text-xs font-bold']) }}>
    @foreach ($idiomas as $codigo => $etiqueta)
        <a href="{{ route('idioma.cambiar', $codigo) }}"
           aria-label="{{ $codigo === 'es' ? 'Español' : 'English' }}"
           @class([
               'px-2.5 py-1 rounded-full transition',
               'bg-white text-blue-700 shadow-sm' => $actual === $codigo,
               'text-slate-500 hover:text-slate-700' => $actual !== $codigo,
           ])>
            {{ $etiqueta }}
        </a>
    @endforeach
</div>
