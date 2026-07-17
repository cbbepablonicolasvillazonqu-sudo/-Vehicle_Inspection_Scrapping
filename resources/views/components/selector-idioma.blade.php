@props(['align' => 'center'])

{{-- Selector de idioma ES | EN. Enlaces GET que vuelven a la página actual. --}}
@php
    $actual = app()->getLocale();
    $idiomas = ['es' => 'ES', 'en' => 'EN'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg border border-gray-300 overflow-hidden text-sm font-semibold']) }}>
    @foreach ($idiomas as $codigo => $etiqueta)
        <a href="{{ route('idioma.cambiar', $codigo) }}"
           aria-label="{{ $codigo === 'es' ? 'Español' : 'English' }}"
           @class([
               'px-3 py-1.5 transition',
               'bg-blue-700 text-white' => $actual === $codigo,
               'bg-white text-gray-600 hover:bg-gray-100' => $actual !== $codigo,
           ])>
            {{ $etiqueta }}
        </a>
    @endforeach
</div>
